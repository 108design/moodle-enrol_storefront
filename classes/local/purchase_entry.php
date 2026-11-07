<?php
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
namespace enrol_storefront\local;
defined('MOODLE_INTERNAL') || die();
/** Read-only native purchase presentation plus a validated handoff to Storefront. */
final class purchase_entry {
    public static function offers(int $courseid): array {
        global $CFG;
        require_once($CFG->libdir . '/enrollib.php');
        if (!enrol_is_enabled('storefront') || !class_exists(\local_storefront\local\catalogue::class)
                || !class_exists(\local_storefront\local\product_selection::class)
                || !method_exists(\local_storefront\local\product_selection::class, 'add')
                || !method_exists(\local_storefront\local\store_access::class, 'can_view')
                || !\local_storefront\local\store_access::can_view()
                || !has_capability('local/storefront:viewcatalogue', \context_system::instance())) { return []; }
        $context = \context_course::instance($courseid);
        $course = get_course($courseid);
        if (!\core_course_category::can_view_course_info($course) || empty($course->visible)
                || is_enrolled($context, null, '', true) || has_capability('moodle/course:view', $context)) { return []; }
        $result = [];
        foreach (enrol_get_instances($courseid, true) as $instance) {
            if ($instance->enrol !== 'storefront') { continue; }
            $mapping = fulfilment_service::mapping_for_enrol((int)$instance->id, true);
            if (!$mapping) { continue; }
            $product = \local_storefront\local\catalogue::get_public_product($mapping->productidentifier);
            // This surface sells course products, never a software/support-course product by accident.
            if (!$product || $product->producttype !== 'course') { continue; }
            $courses = fulfilment_service::mappings_for_product((int)$product->id, true);
            foreach ($product->offers as $offer) {
                if ((float)$offer->amount <= 0 || (method_exists(\local_storefront\local\product_selection::class, 'allows')
                        && !\local_storefront\local\product_selection::allows($offer, 'paid'))) { continue; }
                $result[(int)$offer->id] = ['instance' => $instance, 'product' => $product, 'offer' => $offer,
                    'courses' => $courses];
            }
        }
        return $result;
    }

    public static function render(int $courseid, \core_renderer $output): string {
        $cards = '';
        foreach (self::offers($courseid) as $entry) {
            $offer = $entry['offer'];
            $product = $entry['product'];
            $card = \html_writer::tag('h3', format_string($product->name), ['class' => 'h5']);
            if (!empty($offer->displayname)) { $card .= \html_writer::tag('p', format_string($offer->displayname)); }
            $card .= \html_writer::tag('p', s(\core_payment\helper::get_cost_as_string((float)$offer->amount,
                $offer->currency)), ['class' => 'h4']);
            $courses = '';
            foreach ($entry['courses'] as $mapping) {
                $duration = $mapping->enrolperiod ? format_time($mapping->enrolperiod)
                    : get_string('unlimitedaccess', 'enrol_storefront');
                $courses .= \html_writer::tag('li', format_string($mapping->coursename) . ' · ' . s($duration));
            }
            $card .= \html_writer::tag('ul', $courses);
            $card .= \html_writer::tag('p', get_string($offer->billingtype === 'subscription'
                ? 'gateway_subscription' : 'gateway_oneoff', 'enrol_storefront'), ['class' => 'small text-muted']);
            $card .= $output->render(new \core\output\single_button(new \moodle_url('/enrol/storefront/purchase.php',
                ['courseid' => $courseid, 'offerid' => $offer->id]), get_string('gateway_buy', 'enrol_storefront'),
                'post', \core\output\single_button::BUTTON_PRIMARY));
            $cards .= \html_writer::div($card, 'card card-body mb-3 enrol-storefront-course-offer');
        }
        return $cards;
    }

    /** Native checkout owns legal acceptance, account binding, payment and eventual enrolment. */
    public static function select(int $courseid, int $offerid): void {
        global $SESSION;
        $entry = self::offers($courseid)[$offerid] ?? null;
        if (!$entry) { throw new \invalid_parameter_exception('The course purchase offer is no longer available.'); }
        if (method_exists(\local_storefront\local\product_selection::class, 'choose')) {
            \local_storefront\local\product_selection::choose($entry['product']->identifier, 'paid',
                $entry['offer']->identifier);
            $SESSION->enrol_storefront_origin = ['courseid' => $courseid, 'time' => time()];
        } else {
            \local_storefront\local\product_selection::add($offerid);
        }
    }

    /** Bind the hint to a real new order; it never constitutes payment proof or grants access. */
    public static function remember_order(int $orderid): void {
        global $SESSION;
        $origin = $SESSION->enrol_storefront_origin ?? null;
        if ($origin && (int)$origin['time'] > time() - 2 * HOURSECS) {
            $orders = (array)($SESSION->enrol_storefront_origins ?? []);
            $orders[$orderid] = (int)$origin['courseid'];
            $SESSION->enrol_storefront_origins = array_slice($orders, -20, null, true);
        }
        unset($SESSION->enrol_storefront_origin);
    }

    /** Return only a delivered course from this order that the current viewer can actually enter. */
    public static function return_url(int $orderid, array $courses): ?\moodle_url {
        global $SESSION, $USER, $DB;
        if (!isloggedin() || isguestuser() || (int)$DB->get_field('local_storefront_order', 'userid',
                ['id' => $orderid]) !== (int)$USER->id) { return null; }
        $id = (int)($SESSION->enrol_storefront_origins[$orderid] ?? 0);
        if (!$id || !in_array($id, array_map('intval', array_column($courses, 'id')), true)) { return null; }
        $context = \context_course::instance($id);
        $course = get_course($id);
        if (empty($course->visible) || !is_enrolled($context, null, '', true)) { return null; }
        return new \moodle_url('/course/view.php', ['id' => $id]);
    }
}
