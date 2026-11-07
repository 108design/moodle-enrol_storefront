<?php
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md for the full terms.

defined('MOODLE_INTERNAL') || die();

/** Validate the Storefront host at installation. */
function xmldb_enrol_storefront_install(): void {
    require_once(__DIR__ . '/host.php');
    enrol_storefront_require_host();
}
