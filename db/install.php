<?php
// This file is part of Moodle - https://moodle.org/
// @copyright 2026 Andreas Giesen <andreas@108design.com>
// @license 108design source-available software license; see LICENSE.md
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md for the full terms.

defined('MOODLE_INTERNAL') || die();

/** Validate the Storefront host at installation. */
function xmldb_enrol_storefront_install(): void {
    require_once(__DIR__ . '/host.php');
    enrol_storefront_require_host();
}
