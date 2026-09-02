<?php
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>

$string['pluginname'] = 'Storefront course purchase';
$string['licensepolicyproduct'] = 'Course purchase';
$string['pluginname_desc'] = 'Enrol users after a verified course purchase in 108design Storefront.';
$string['storefront:config'] = 'Configure Storefront course purchase enrolment';
$string['storefront:manage'] = 'Manage Storefront course purchase enrolments';
$string['storefront:unenrol'] = 'Unenrol Storefront course customers';
$string['product'] = 'Storefront product';
$string['product_help'] = 'Links this course to a Storefront license, software or course product. One product may include multiple courses and one course may belong to multiple products.';
$string['productmissing'] = 'Select an available Storefront course product.';
$string['productmapped'] = 'This product is already linked to this course.';
$string['instanceexists'] = 'This course already has a Storefront purchase enrolment method.';
$string['restorehint'] = 'Restored inactive Storefront hint: {$a}';
$string['purchasenote'] = 'Customers buy this course in Storefront. A verified paid order grants access after it has been linked to their Moodle account.';
$string['gotostore'] = 'View in Store';
$string['courselink'] = '{$a->product} · {$a->course}';
$string['mappingunavailable'] = 'This Storefront product is not linked to an available course enrolment method.';
$string['subscriptionexpiryinvalid'] = 'The verified subscription period does not grant current course access.';
$string['privacy:metadata:enrol_storefront_purchase'] = 'Stores the relationship between a verified Storefront order and course access.';
$string['privacy:metadata:enrol_storefront_purchase:userid'] = 'The customer who received course access.';
$string['privacy:metadata:enrol_storefront_purchase:orderid'] = 'The Storefront order that granted access.';
$string['privacy:metadata:enrol_storefront_purchase:orderlineid'] = 'The immutable Storefront order line fulfilled by this access record.';
$string['privacy:metadata:enrol_storefront_purchase:courseid'] = 'The course made accessible.';
$string['privacy:metadata:enrol_storefront_purchase:status'] = 'The current delivery status.';
$string['privacy:metadata:enrol_storefront_purchase:timestart'] = 'When access began.';
$string['privacy:metadata:enrol_storefront_purchase:timeend'] = 'When access ends, or zero for unlimited access.';
$string['privacy:metadata:enrol_storefront_purchase:timecreated'] = 'When the delivery record was created.';
$string['privacy:metadata:enrol_storefront_purchase:timemodified'] = 'When the delivery record was last changed.';
$string['licensepolicydegradation'] = 'Course fulfilment is included in Storefront and has no separate license gate. Normal dependency preflight remains in place.';
