<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [];

// Decorative product identity is restricted to the native administration surfaces.
$callbacks[] = [
    'hook' => \core\hook\output\before_standard_head_html_generation::class,
    'callback' => [\enrol_storefront\local\admin_branding::class, 'head'],
];
$callbacks[] = [
    'hook' => \core\hook\output\before_http_headers::class,
    'callback' => [\enrol_storefront\local\admin_branding::class, 'prepare'],
];
