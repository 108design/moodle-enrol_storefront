<?php
/**
 * File documentation for classes/plugin.php.
 *
 * @package   enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution and circumvention of Pro feature or
// licensing restrictions are prohibited. See LICENSE.md for the full terms.

use core\output\single_button;
use enrol_storefront\local\fulfilment_service;
use enrol_storefront\local\enrol_page_renderer;

defined('MOODLE_INTERNAL') || die();

/** Course enrolment delivered by a verified 108design Storefront purchase. */
class enrol_storefront_plugin extends enrol_plugin {
    public function roles_protected() {
        return false;
    }

    public function allow_unenrol(stdClass $instance) {
        return true;
    }

    public function allow_manage(stdClass $instance) {
        return true;
    }

    public function show_enrolme_link(stdClass $instance) {
        return $instance->status == ENROL_INSTANCE_ENABLED;
    }

    public function use_standard_editing_ui() {
        return true;
    }

    public function get_instance_name_for_management_page(stdClass $instance): string {
        $name = $this->get_instance_name($instance);
        $mapping = fulfilment_service::mapping_for_enrol((int) $instance->id, false);
        if ($mapping) {
            $context = context_course::instance($instance->courseid);
            $name .= html_writer::empty_tag('br') . html_writer::tag('em',
                format_string($mapping->productname, true, ['context' => $context]));
        } else if (!empty($instance->customchar1)) {
            $name .= html_writer::empty_tag('br') . html_writer::tag('em',
                get_string('restorehint', 'enrol_storefront', s((string) $instance->customchar1)));
        }
        return $name;
    }

    public function add_instance($course, ?array $fields = null) {
        global $DB;
        $fields = $fields ?? [];
        $productid = (int) ($fields['customint1'] ?? 0);
        if ($productid > 0) {
            $identifier = $DB->get_field('local_storefront_product', 'identifier', ['id' => $productid]);
            $fields['customchar1'] = $identifier ?: null;
            if ($identifier && $orphan = $DB->get_record('enrol', [
                    'courseid' => $course->id,
                    'enrol' => 'storefront',
                    'customint1' => 0,
                    'customchar1' => $identifier,
                ])) {
                parent::update_instance($orphan, (object) $fields);
                return (int) $orphan->id;
            }
        }
        return parent::add_instance($course, $fields);
    }

    public function update_instance($instance, $data) {
        global $DB;
        if (!empty($data->customint1)) {
            $data->customchar1 = $DB->get_field('local_storefront_product', 'identifier', [
                'id' => (int) $data->customint1,
            ]) ?: null;
        }
        return parent::update_instance($instance, $data);
    }

    public function get_info_icons(array $instances) {
        foreach ($instances as $instance) {
            if ($instance->status == ENROL_INSTANCE_ENABLED
                    && fulfilment_service::mapping_for_enrol((int) $instance->id, true)) {
                return [new pix_icon('icon', get_string('pluginname', 'enrol_storefront'), 'enrol_storefront')];
            }
        }
        return [];
    }

    public function can_add_instance($courseid) {
        global $DB;
        $context = context_course::instance($courseid, MUST_EXIST);
        if (!has_capability('moodle/course:enrolconfig', $context)
                || !has_capability('enrol/storefront:config', $context)) {
            return false;
        }
        return !empty($this->product_options((int) $courseid));
    }

    public function can_delete_instance($instance) {
        global $DB;
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/storefront:config', $context)
            && !$DB->record_exists('enrol_storefront_purchase', ['enrolid' => $instance->id]);
    }

    public function can_hide_show_instance($instance) {
        return has_capability('enrol/storefront:config', context_course::instance($instance->courseid));
    }

    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'instancename', 'enrol_storefront');

        $mform->addElement('select', 'customint1', get_string('product', 'enrol_storefront'),
            $this->product_options((int) $context->instanceid, (int) ($instance->customint1 ?? 0)));
        $mform->addHelpButton('customint1', 'product', 'enrol_storefront');

        $mform->addElement('static', 'purchasenote', '', get_string('purchasenote', 'enrol_storefront'));

        $mform->addElement('select', 'status', get_string('status'), [
            ENROL_INSTANCE_ENABLED => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no'),
        ]);
        $mform->setDefault('status', ENROL_INSTANCE_ENABLED);
        $mform->addHelpButton('status', 'instancestatus', 'enrol_storefront');

        $roles = get_default_enrol_roles($context, $instance->roleid ?? 0);
        $mform->addElement('select', 'roleid', get_string('role'), $roles);
        $mform->addHelpButton('roleid', 'instancerole', 'enrol_storefront');
        if (empty($instance->id)) {
            $studentroles = get_archetype_roles('student');
            if ($studentroles) {
                $mform->setDefault('roleid', (int) reset($studentroles)->id);
            }
        }

        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol'), [
            'optional' => true,
            'defaultunit' => DAYSECS,
        ]);
        $mform->setDefault('enrolperiod', 0);
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_storefront');
    }

    public function edit_instance_validation($data, $files, $instance, $context) {
        global $DB;
        $errors = $this->validate_param_types($data, [
            'name' => PARAM_TEXT,
            'customint1' => PARAM_INT,
            'status' => [ENROL_INSTANCE_ENABLED, ENROL_INSTANCE_DISABLED],
            'roleid' => array_keys(get_default_enrol_roles($context, $instance->roleid ?? 0)),
            'enrolperiod' => PARAM_INT,
        ]);
        $productid = (int) ($data['customint1'] ?? 0);
        $product = $DB->get_record('local_storefront_product', ['id' => $productid], 'id,producttype');
        if (!$product || !in_array((string) $product->producttype, ['license', 'software', 'course'], true)) {
            $errors['customint1'] = get_string('productmissing', 'enrol_storefront');
        } else {
            $sql = "SELECT id FROM {enrol}
                     WHERE enrol = :enrol AND customint1 = :productid
                       AND courseid = :courseid AND id <> :instanceid";
            if ($DB->record_exists_sql($sql, [
                'enrol' => 'storefront',
                'productid' => $productid,
                'courseid' => (int) $context->instanceid,
                'instanceid' => (int) ($instance->id ?? 0),
            ])) {
                $errors['customint1'] = get_string('productmapped', 'enrol_storefront');
            }
        }
        return $errors;
    }

    /** Restore only a disabled product-identifier hint, never a live mapping. */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        $data->customint1 = 0;
        $data->status = ENROL_INSTANCE_DISABLED;
        $instanceid = parent::add_instance($course, (array) $data);
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /** Purchased access is external commerce history and is never recreated from a course backup. */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance,
            $userid, $oldinstancestatus) {
        // Intentionally empty.
    }

    public function enrol_page_hook(stdClass $instance) {
        global $DB, $USER;
        if ($instance->status != ENROL_INSTANCE_ENABLED
                || $DB->record_exists('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
            return '';
        }
        $mapping = fulfilment_service::mapping_for_enrol((int) $instance->id, true);
        if (!$mapping) {
            return '';
        }
        $url = new moodle_url('/local/storefront/product.php', ['product' => $mapping->productidentifier]);
        $button = new single_button($url, get_string('gotostore', 'enrol_storefront'), 'get',
            single_button::BUTTON_PRIMARY);
        $header = format_string($mapping->productname, true,
            ['context' => context_course::instance($instance->courseid)]);
        return enrol_page_renderer::render(
            $instance,
            $header,
            get_string('purchasenote', 'enrol_storefront'),
            $button
        );
    }

    /** Deliverable Storefront products not already assigned to this course, plus the current selection. */
    private function product_options(int $courseid, int $current = 0): array {
        global $DB;
        $sql = "SELECT p.id, p.name
                  FROM {local_storefront_product} p
             LEFT JOIN {enrol} e ON e.enrol = :enrol AND e.customint1 = p.id AND e.courseid = :courseid
                 WHERE p.producttype IN (:licensetype, :softwaretype, :coursetype)
                   AND (e.id IS NULL OR p.id = :current)
              ORDER BY p.name ASC";
        $records = $DB->get_records_sql($sql, [
            'enrol' => 'storefront',
            'courseid' => $courseid,
            'licensetype' => 'license',
            'softwaretype' => 'software',
            'coursetype' => 'course',
            'current' => $current,
        ]);
        return array_map(static fn(stdClass $product): string => format_string($product->name), $records);
    }
}
