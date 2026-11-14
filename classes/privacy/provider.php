<?php
// This file is part of Moodle - https://moodle.org/
/**
 * File documentation for classes/privacy/provider.php.
 *
 * @package   enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution and circumvention of Pro feature or
// licensing restrictions are prohibited. See LICENSE.md for the full terms.

namespace enrol_storefront\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/** Privacy provider for course-access records created from Storefront orders. */
final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('enrol_storefront_purchase', [
            'userid' => 'privacy:metadata:enrol_storefront_purchase:userid',
            'orderid' => 'privacy:metadata:enrol_storefront_purchase:orderid',
            'orderlineid' => 'privacy:metadata:enrol_storefront_purchase:orderlineid',
            'courseid' => 'privacy:metadata:enrol_storefront_purchase:courseid',
            'status' => 'privacy:metadata:enrol_storefront_purchase:status',
            'manualsuspended' => 'privacy:metadata:enrol_storefront_purchase:manualsuspended',
            'timestart' => 'privacy:metadata:enrol_storefront_purchase:timestart',
            'timeend' => 'privacy:metadata:enrol_storefront_purchase:timeend',
            'timecreated' => 'privacy:metadata:enrol_storefront_purchase:timecreated',
            'timemodified' => 'privacy:metadata:enrol_storefront_purchase:timemodified',
        ], 'privacy:metadata:enrol_storefront_purchase');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {enrol_storefront_purchase} p
                  JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = p.courseid
                 WHERE p.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $records = array_values($DB->get_records('enrol_storefront_purchase', [
                'userid' => $userid,
                'courseid' => $context->instanceid,
            ], 'timecreated ASC'));
            foreach ($records as $record) {
                $record->timestart = transform::datetime($record->timestart);
                $record->timeend = $record->timeend ? transform::datetime($record->timeend) : null;
                $record->timecreated = transform::datetime($record->timecreated);
                $record->timemodified = transform::datetime($record->timemodified);
            }
            writer::with_context($context)->export_data([get_string('pluginname', 'enrol_storefront')],
                (object) ['courseaccess' => $records]);
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel === CONTEXT_COURSE) {
            $DB->delete_records('enrol_storefront_purchase', ['courseid' => $context->instanceid]);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_COURSE) {
                $DB->delete_records('enrol_storefront_purchase', [
                    'courseid' => $context->instanceid,
                    'userid' => $contextlist->get_user()->id,
                ]);
            }
        }
    }

    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $userlist->add_from_sql('userid',
            'SELECT userid FROM {enrol_storefront_purchase} WHERE courseid = :courseid',
            ['courseid' => $userlist->get_context()->instanceid]);
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        if ($userlist->get_context()->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        $userids = $userlist->get_userids();
        if ($userids) {
            [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $params['courseid'] = $userlist->get_context()->instanceid;
            $DB->delete_records_select('enrol_storefront_purchase',
                "courseid = :courseid AND userid $insql", $params);
        }
    }
}
