<?php
/**
 * File documentation for lang/en/enrol_storefront.php.
 *
 * @package   enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
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

// Shared independent signed update protection.
$string['updateguard_heading'] = 'Update stopped';
$string['updateguard_component'] = 'The package for {$a} was rejected.';
$string['updateguard_hint'] = 'Use an approved package for the installed edition. You can review the operation again.';
$string['updateguard_unchanged'] = 'This request did not replace plugin files.';
$string['updateguard_help'] = 'The installed plugin checks the publisher signature, package contents, permitted update channel and compatibility before replacement. A valid signature does not authorise a different edition. If one package is rejected, the whole batch stops.';
$string['updateguard_signature'] = 'The publisher signature or package contents could not be verified.';
$string['updateguard_policy'] = 'This package does not match the installed edition or permitted package profile.';
$string['updateguard_channel'] = 'Updates through this channel are not enabled for this plugin.';
$string['updateguard_metadata'] = 'The version or dependency information is inconsistent.';
$string['updateguard_dependency'] = 'A required plugin version is missing. Update the dependency together with this plugin or beforehand.';
$string['updateguard_approval'] = 'The packages or installed state have changed since the preview. Review the operation again.';
$string['updateguard_busy'] = 'Another installation is running. Try again after it finishes.';
$string['updateguard_installed'] = 'The installed update protection is incomplete. Restore the files of the last approved installed version.';
$string['updateguard_operation'] = 'This combination of installation actions is not supported. Start a single update operation again.';
$string['updateguard_failed'] = 'The update could not be verified. No installation was started.';
$string['updateguard_back'] = 'Return to plugin overview';
// End shared independent signed update protection.

$string['enrolperiod'] = 'Course Access Duration';

$string['enrolperiod_help'] = ('Leave disabled for access without a scheduled end date. A timed period starts '
    . 'when course access is delivered. Changes apply to future orders; saved orders '
    . 'retain their original duration.');

$string['unlimitedaccess'] = 'No fixed end date';
$string['gateway_buy'] = 'Buy course access';
$string['gateway_oneoff'] = 'One-time payment. Access starts when provisioned; the duration is shown per course.';
$string['gateway_subscription'] = 'Subscription. Check the billing interval and terms at checkout.';
$string['gateway_return'] = 'Continue to your course';
