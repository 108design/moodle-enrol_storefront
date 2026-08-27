<?php
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
        $this->assertSame($course->id, (int) $mapping->courseid);
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
        $this->assertEquals(ENROL_USER_SUSPENDED, (int) $userenrolment->status);

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
}
