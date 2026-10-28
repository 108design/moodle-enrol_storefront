<?php
/**
 * File documentation for classes/local/enrol_page_renderer.php.
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

namespace enrol_storefront\local;

defined('MOODLE_INTERNAL') || die();

/** Moodle-version adapter for the public enrolment information surface. */
final class enrol_page_renderer {
    public static function render(
        \stdClass $instance,
        string $header,
        string $body,
        \core\output\single_button $button
    ): string {
        global $OUTPUT;

        if (class_exists(\core_enrol\output\enrol_page::class)) {
            return $OUTPUT->render(new \core_enrol\output\enrol_page(
                instance: $instance,
                header: $header,
                body: $body,
                buttons: [$button],
            ));
        }

        // Moodle 4.5 has enrol_page_hook(), but not the enrol_page renderable introduced in Moodle 5.0.
        return \html_writer::div(
            \html_writer::tag('h3', $header, ['class' => 'h4'])
            . \html_writer::tag('p', $body)
            . \html_writer::div($OUTPUT->render($button), 'mt-3'),
            'card card-body mb-3 enrol-storefront-enrol-page'
        );
    }
}
