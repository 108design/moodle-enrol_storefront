<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * @package enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md.
// Public catalogue entry; Storefront and native enrolment policies enforce the audience.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/enrollib.php');
\local_storefront\local\store_access::require_access();
require_capability('local/storefront:viewcatalogue', context_system::instance());
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new moodle_exception('invalidrequest'); }
require_sesskey();
$courseid = required_param('courseid', PARAM_INT);
$offerid = required_param('offerid', PARAM_INT);
\enrol_storefront\local\purchase_entry::select($courseid, $offerid);
redirect(new moodle_url('/local/storefront/checkout.php'));
