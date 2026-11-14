<?php
// This file is part of Moodle - https://moodle.org/
/**
 * File documentation for tests/compatibility_test.php.
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

namespace enrol_storefront;

defined('MOODLE_INTERNAL') || die();

/** Compatibility-boundary regression tests. */
final class compatibility_test extends \basic_testcase {
    public function test_enrol_page_rendering_isolated_behind_feature_detection(): void {
        $plugin = file_get_contents(__DIR__ . '/../classes/plugin.php');
        $renderer = file_get_contents(__DIR__ . '/../classes/local/enrol_page_renderer.php');

        $this->assertStringContainsString('enrol_page_renderer::render', $plugin);
        $this->assertStringNotContainsString('#[\\Override]', $plugin);
        $this->assertStringContainsString('class_exists(\\core_enrol\\output\\enrol_page::class)', $renderer);
        $this->assertStringContainsString('enrol-storefront-enrol-page', $renderer);
    }
}
