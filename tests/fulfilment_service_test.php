<?php
/**
 * File documentation for tests/fulfilment_service_test.php.
 *
 * @package   enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>

namespace enrol_storefront;

defined('MOODLE_INTERNAL') || die();

use enrol_storefront\local\fulfilment_service;

/** Course mapping and idempotent purchase-delivery coverage. */
final class fulfilment_service_test extends \advanced_testcase {
    public function test_mapping_fulfilment_renewal_and_suspension(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['visible' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $now = time();
        $productid = (int) $DB->insert_record('local_storefront_product', (object) [
            'identifier' => 'course-test',
            'producttype' => 'course',
            'name' => 'Course test',
            'visible' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $plugin = enrol_get_plugin('storefront');
        $enrolid = (int) $plugin->add_instance($course, [
            'status' => ENROL_INSTANCE_ENABLED,
            'customint1' => $productid,
            'roleid' => $studentrole->id,
            'enrolperiod' => WEEKSECS,
        ]);
        $this->assertSame('course-test', $DB->get_field('enrol', 'customchar1', ['id' => $enrolid]));

        $mapping = fulfilment_service::mapping_for_product($productid, true);
        $this->assertNotNull($mapping);
        $this->assertSame((int) $course->id, (int) $mapping->courseid);
        $snapshot = fulfilment_service::snapshot_for_product($productid);

        fulfilment_service::fulfil(7001, 8001, $user->id, $snapshot, 'oneoff', null, null);
        fulfilment_service::fulfil(7001, 8001, $user->id, $snapshot, 'oneoff', null, null);
        $this->assertEquals(1, $DB->count_records('enrol_storefront_purchase', ['orderlineid' => 8001]));
        $userenrolment = $DB->get_record('user_enrolments', ['enrolid' => $enrolid, 'userid' => $user->id],
            '*', MUST_EXIST);
        $this->assertEquals(ENROL_USER_ACTIVE, $userenrolment->status);
        $this->assertGreaterThan($now + WEEKSECS - 10, $userenrolment->timeend);

        $subscriptionend = $now + 60 * DAYSECS;
        fulfilment_service::fulfil(7002, 8002, $user->id, $snapshot, 'subscription', 'sub_test', $subscriptionend);
        fulfilment_service::renew_line(8002, $now + 90 * DAYSECS);
        $purchase = $DB->get_record('enrol_storefront_purchase', ['orderlineid' => 8002], '*', MUST_EXIST);
        $this->assertEquals($now + 90 * DAYSECS, (int) $purchase->timeend);
        $userenrolment = $DB->get_record('user_enrolments', ['enrolid' => $enrolid, 'userid' => $user->id],
            '*', MUST_EXIST);
        $this->assertEquals($now + 90 * DAYSECS, (int) $userenrolment->timeend);

        fulfilment_service::suspend_line(8002);
        $userenrolment = $DB->get_record('user_enrolments', ['enrolid' => $enrolid, 'userid' => $user->id],
            '*', MUST_EXIST);
        $this->assertEquals(ENROL_USER_ACTIVE, (int) $userenrolment->status);

        fulfilment_service::resume_line(8002);
        $userenrolment = $DB->get_record('user_enrolments', ['enrolid' => $enrolid, 'userid' => $user->id],
            '*', MUST_EXIST);
        $this->assertEquals(ENROL_USER_ACTIVE, (int) $userenrolment->status);
        $this->assertEquals($now + 90 * DAYSECS, (int) $userenrolment->timeend);

        fulfilment_service::suspend_order(7001);
        $userenrolment = $DB->get_record('user_enrolments', ['enrolid' => $enrolid, 'userid' => $user->id],
            '*', MUST_EXIST);
        // The independent second purchase still grants access.
        $this->assertEquals(ENROL_USER_ACTIVE, (int) $userenrolment->status);
        fulfilment_service::suspend_line(8002);
        $this->assertEquals(ENROL_USER_SUSPENDED, (int) $DB->get_field('user_enrolments', 'status',
            ['enrolid' => $enrolid, 'userid' => $user->id]));

        fulfilment_service::resume_line(8002);
        $userenrolment = $DB->get_record('user_enrolments', ['enrolid' => $enrolid, 'userid' => $user->id],
            '*', MUST_EXIST);
        $this->assertEquals(ENROL_USER_ACTIVE, (int) $userenrolment->status);
        $this->assertEquals($now + 90 * DAYSECS, (int) $userenrolment->timeend);
    }

    public function test_hidden_course_is_not_public(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['visible' => 0]);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $now = time();
        $productid = (int) $DB->insert_record('local_storefront_product', (object) [
            'identifier' => 'hidden-course-test',
            'producttype' => 'course',
            'name' => 'Hidden course test',
            'visible' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        enrol_get_plugin('storefront')->add_instance($course, [
            'status' => ENROL_INSTANCE_ENABLED,
            'customint1' => $productid,
            'roleid' => $studentrole->id,
        ]);

        $this->assertNotNull(fulfilment_service::mapping_for_product($productid, false));
        $this->assertNull(fulfilment_service::mapping_for_product($productid, true));
    }

    public function test_one_software_position_can_deliver_multiple_support_courses(): void {
        global $DB;
        $this->resetAfterTest();

        $courses = [
            $this->getDataGenerator()->create_course(['visible' => 1]),
            $this->getDataGenerator()->create_course(['visible' => 1]),
        ];
        $user = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $now = time();
        $productid = (int) $DB->insert_record('local_storefront_product', (object) [
            'identifier' => 'software-support-bundle',
            'producttype' => 'software',
            'name' => 'Software support bundle',
            'visible' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $plugin = enrol_get_plugin('storefront');
        foreach ($courses as $course) {
            $plugin->add_instance($course, [
                'status' => ENROL_INSTANCE_ENABLED,
                'customint1' => $productid,
                'roleid' => $studentrole->id,
                'enrolperiod' => 0,
            ]);
        }

        $snapshots = fulfilment_service::snapshots_for_product($productid);
        $this->assertCount(2, $snapshots);
        foreach ($snapshots as $snapshot) {
            fulfilment_service::fulfil(7100, 8100, $user->id, $snapshot, 'oneoff', null, null);
        }
        $this->assertSame(2, $DB->count_records('enrol_storefront_purchase', ['orderlineid' => 8100]));
        $this->assertSame(2, $DB->count_records('user_enrolments', ['userid' => $user->id]));

        fulfilment_service::suspend_line(8100);
        $this->assertSame(2, $DB->count_records('user_enrolments', [
            'userid' => $user->id,
            'status' => ENROL_USER_SUSPENDED,
        ]));
        fulfilment_service::resume_line(8100);
        $this->assertSame(2, $DB->count_records('user_enrolments', [
            'userid' => $user->id,
            'status' => ENROL_USER_ACTIVE,
        ]));
    }

    public function test_each_purchase_keeps_its_own_end_while_effective_access_is_aggregated(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['visible' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $now = time();
        $productid = (int) $DB->insert_record('local_storefront_product', (object) [
            'identifier' => 'independent-purchase-periods',
            'producttype' => 'course',
            'name' => 'Independent purchase periods',
            'visible' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $enrolid = (int) enrol_get_plugin('storefront')->add_instance($course, [
            'status' => ENROL_INSTANCE_ENABLED,
            'customint1' => $productid,
            'roleid' => $studentrole->id,
            'enrolperiod' => 0,
        ]);
        $unlimited = fulfilment_service::snapshot_for_product($productid);
        $finite = $unlimited;
        $finite['enrolperiod'] = 2 * DAYSECS;

        fulfilment_service::fulfil(7201, 8201, $user->id, $unlimited, 'oneoff', null, null);
        fulfilment_service::fulfil(7202, 8202, $user->id, $finite, 'oneoff', null, null);
        $unlimitedpurchase = $DB->get_record('enrol_storefront_purchase', ['orderlineid' => 8201], '*', MUST_EXIST);
        $finitepurchase = $DB->get_record('enrol_storefront_purchase', ['orderlineid' => 8202], '*', MUST_EXIST);
        $this->assertSame(0, (int) $unlimitedpurchase->timeend);
        $this->assertGreaterThan($now + 2 * DAYSECS - 10, (int) $finitepurchase->timeend);
        $this->assertSame(0, (int) $DB->get_field('user_enrolments', 'timeend', [
            'enrolid' => $enrolid,
            'userid' => $user->id,
        ]));

        fulfilment_service::suspend_line(8201);
        $this->assertSame((int) $finitepurchase->timeend, (int) $DB->get_field('user_enrolments', 'timeend', [
            'enrolid' => $enrolid,
            'userid' => $user->id,
        ]));
        fulfilment_service::suspend_line(8202);
        $this->assertSame(ENROL_USER_SUSPENDED, (int) $DB->get_field('user_enrolments', 'status', [
            'enrolid' => $enrolid,
            'userid' => $user->id,
        ]));

        fulfilment_service::resume_line(8202);
        $this->assertSame(ENROL_USER_ACTIVE, (int) $DB->get_field('user_enrolments', 'status', [
            'enrolid' => $enrolid,
            'userid' => $user->id,
        ]));
        $DB->set_field('enrol_storefront_purchase', 'timeend', time() - 1, ['id' => $finitepurchase->id]);
        fulfilment_service::resume_line(8202);
        $this->assertSame(ENROL_USER_SUSPENDED, (int) $DB->get_field('user_enrolments', 'status', [
            'enrolid' => $enrolid,
            'userid' => $user->id,
        ]));
        fulfilment_service::resume_line(8201);
        $this->assertSame(ENROL_USER_ACTIVE, (int) $DB->get_field('user_enrolments', 'status', [
            'enrolid' => $enrolid,
            'userid' => $user->id,
        ]));
        $this->assertSame(0, (int) $DB->get_field('user_enrolments', 'timeend', [
            'enrolid' => $enrolid,
            'userid' => $user->id,
        ]));
    }

    public function test_access_lock_is_reentrant_for_the_same_effective_enrolment(): void {
        $this->resetAfterTest();
        $visited = [];
        $result = \enrol_storefront\local\access_lock::with_pairs([[7, 9], [3, 4], [7, 9]],
            static function() use (&$visited): string {
                $visited[] = 'outer';
                return \enrol_storefront\local\access_lock::with_pairs([[3, 4], [7, 9]],
                    static function() use (&$visited): string {
                        $visited[] = 'inner';
                        return 'ok';
                    });
            });
        $this->assertSame('ok', $result);
        $this->assertSame(['outer', 'inner'], $visited);
    }

    public function test_upgrade_repair_reconstructs_oneoff_period_from_immutable_snapshot(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['visible' => 1]);
        $user = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $now = time();
        $productid = (int) $DB->insert_record('local_storefront_product', (object) [
            'identifier' => 'period-repair-product',
            'producttype' => 'course',
            'name' => 'Period repair product',
            'visible' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $enrolid = (int) enrol_get_plugin('storefront')->add_instance($course, [
            'status' => ENROL_INSTANCE_ENABLED,
            'customint1' => $productid,
            'roleid' => $studentrole->id,
            'enrolperiod' => 2 * DAYSECS,
        ]);
        $snapshot = fulfilment_service::snapshot_for_product($productid);
        $offerid = (int) $DB->insert_record('local_storefront_offer', (object) [
            'productid' => $productid,
            'identifier' => 'period-repair-offer',
            'entitlementsjson' => '[]',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $orderid = (int) $DB->insert_record('local_storefront_order', (object) [
            'orderkey' => '00000000-0000-4000-8000-000000007301',
            'status' => 'paid',
            'channel' => 'direct',
            'paymentenvironment' => 'sandbox',
            'currency' => 'EUR',
            'total' => 1,
            'accessstatus' => 'active',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $offeringjson = json_encode([
            'producttype' => 'course',
            'billingtype' => 'oneoff',
            'courses' => [$snapshot],
        ], JSON_THROW_ON_ERROR);
        $orderlineid = (int) $DB->insert_record('local_storefront_orderline', (object) [
            'orderid' => $orderid,
            'offerid' => $offerid,
            'productname' => 'Period repair product',
            'offeringjson' => $offeringjson,
            'snapshotversion' => 2,
            'snapshothash' => hash('sha256', $offeringjson),
            'amount' => 1,
        ]);
        fulfilment_service::fulfil($orderid, $orderlineid, $user->id, $snapshot, 'oneoff', null, null);
        $purchase = $DB->get_record('enrol_storefront_purchase', ['orderlineid' => $orderlineid], '*', MUST_EXIST);
        $DB->set_field('enrol_storefront_purchase', 'timeend', 0, ['id' => $purchase->id]);
        $DB->set_field('user_enrolments', 'timeend', 0, ['enrolid' => $enrolid, 'userid' => $user->id]);

        $result = \enrol_storefront\local\purchase_period_repair::repair_all();
        $this->assertSame(['repaired' => 1, 'ambiguous' => 0], $result);
        $expected = (int) $purchase->timestart + 2 * DAYSECS;
        $this->assertSame($expected, (int) $DB->get_field('enrol_storefront_purchase', 'timeend', [
            'id' => $purchase->id,
        ]));
        $this->assertSame($expected, (int) $DB->get_field('user_enrolments', 'timeend', [
            'enrolid' => $enrolid,
            'userid' => $user->id,
        ]));
    }
}
