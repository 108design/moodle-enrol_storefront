<?php
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution and circumvention of Pro feature or
// licensing restrictions are prohibited. See LICENSE.md for the full terms.

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'enrol_storefront';
$plugin->version = 2026090701;
$plugin->requires = 2024100700; // Moodle 4.5.
$plugin->maturity = MATURITY_BETA;
$plugin->release = '0.7.4-beta';
$plugin->dependencies = [
    'local_storefront' => 2026090702,
];
