<?php
// This file is part of Moodle - https://moodle.org/
// @copyright 2026 Andreas Giesen <andreas@108design.com>
// @license 108design source-available software license; see LICENSE.md
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md for the full terms.

defined('MOODLE_INTERNAL') || die();

/** Check the host contract that Moodle's single numeric dependency cannot express. */
function enrol_storefront_require_host(): void {
    global $CFG;
    $guard = \design108\update_guard\v1\guard::class;
    if (!class_exists($guard, false)) {
        require_once(__DIR__ . '/../update-guard.php');
    }
    $root = $CFG->dirroot . '/local/storefront';
    try {
        $policy = $guard::load_policy($root, 'local_storefront');
        $host = $guard::metadata((string)file_get_contents($root . '/version.php'));
        $contract = json_decode(file_get_contents(__DIR__ . '/../host-compatibility.json'), true, 16, JSON_THROW_ON_ERROR);
        $minimum = ($contract['schema'] ?? null) === 1 && ($contract['component'] ?? '') === 'enrol_storefront'
            ? ($contract['host_minimums'][$policy['edition']] ?? null) : null;
        if ($host['component'] === 'local_storefront' && is_int($minimum) && $host['version'] >= $minimum) {
            return;
        }
    } catch (\Throwable $e) {
        // A host whose installed contract cannot be identified is not compatible.
    }
    throw new moodle_exception('hostunsupported', 'enrol_storefront');
}
