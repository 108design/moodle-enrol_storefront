<?php
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. Redistribution and circumvention of Pro feature or
// licensing restrictions are prohibited. See LICENSE.md for the full terms.

namespace enrol_storefront\local;

defined('MOODLE_INTERNAL') || die();

/** Authoritative course-product mapping and idempotent course fulfilment. */
final class fulfilment_service {
    public static function purchase_count(int $productid): int {
        global $DB;
        return $DB->count_records('enrol_storefront_purchase', ['productid' => $productid]);
    }

    /** @return array<int, \stdClass> Mappings keyed by enrolment-instance ID. */
    public static function mappings_for_product(int $productid, bool $publiconly = true): array {
        global $DB;
        $sql = "SELECT e.id AS enrolid, e.courseid, e.roleid, e.enrolperiod, e.status AS enrolstatus,
                       c.fullname AS coursename, c.visible AS coursevisible,
                       p.id AS productid, p.identifier AS productidentifier, p.name AS productname,
                       p.visible AS productvisible
                  FROM {enrol} e
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {local_storefront_product} p ON p.id = e.customint1
                 WHERE e.enrol = :enrol AND e.customint1 = :productid
                   AND p.producttype IN (:licensetype, :softwaretype, :coursetype)
              ORDER BY c.fullname ASC, e.id ASC";
        $records = $DB->get_records_sql($sql, [
            'enrol' => 'storefront',
            'productid' => $productid,
            'licensetype' => 'license',
            'softwaretype' => 'software',
            'coursetype' => 'course',
        ]);
        if (!$publiconly) {
            return $records;
        }
        return array_filter($records, static fn(\stdClass $mapping): bool =>
            $mapping->enrolstatus == ENROL_INSTANCE_ENABLED
            && !empty($mapping->coursevisible)
            && !empty($mapping->productvisible)
            && (int) $mapping->courseid !== SITEID
        );
    }

    /** Backwards-compatible first mapping for presentation callers. */
    public static function mapping_for_product(int $productid, bool $publiconly = true): ?\stdClass {
        $mappings = self::mappings_for_product($productid, $publiconly);
        return $mappings ? reset($mappings) : null;
    }

    public static function mapping_for_enrol(int $enrolid, bool $publiconly = true): ?\stdClass {
        global $DB;
        $instance = $DB->get_record('enrol', ['id' => $enrolid, 'enrol' => 'storefront'], 'id,customint1');
        if (!$instance) {
            return null;
        }
        return self::mappings_for_product((int) $instance->customint1, $publiconly)[$enrolid] ?? null;
    }

    /** @return array<int, array<string, int|string>> */
    public static function snapshots_for_product(int $productid): array {
        $snapshots = [];
        foreach (self::mappings_for_product($productid, true) as $mapping) {
            $snapshots[] = [
                'enrolid' => (int) $mapping->enrolid,
                'courseid' => (int) $mapping->courseid,
                'coursefullname' => (string) $mapping->coursename,
                'productid' => (int) $mapping->productid,
                'productidentifier' => (string) $mapping->productidentifier,
                'roleid' => (int) $mapping->roleid,
                'enrolperiod' => (int) $mapping->enrolperiod,
            ];
        }
        return $snapshots;
    }

    /** Backwards-compatible singular snapshot. */
    public static function snapshot_for_product(int $productid): array {
        $snapshots = self::snapshots_for_product($productid);
        if (!$snapshots) {
            throw new \moodle_exception('mappingunavailable', 'enrol_storefront');
        }
        return reset($snapshots);
    }

    public static function fulfil(
        int $orderid,
        int $orderlineid,
        int $userid,
        array $snapshot,
        string $billingtype,
        ?string $subscriptionreference,
        ?int $subscriptionexpiry
    ): void {
        global $DB;
        if ($DB->record_exists('enrol_storefront_purchase', [
                'orderlineid' => $orderlineid,
                'enrolid' => (int) ($snapshot['enrolid'] ?? 0),
            ])) {
            return;
        }
        foreach (['enrolid', 'courseid', 'productid', 'roleid', 'enrolperiod'] as $required) {
            if (!array_key_exists($required, $snapshot)) {
                throw new \coding_exception('Incomplete Storefront course delivery snapshot: ' . $required);
            }
        }
        $instance = $DB->get_record('enrol', [
            'id' => (int) $snapshot['enrolid'],
            'courseid' => (int) $snapshot['courseid'],
            'enrol' => 'storefront',
            'customint1' => (int) $snapshot['productid'],
        ], '*', MUST_EXIST);
        if ($instance->status != ENROL_INSTANCE_ENABLED
                || !self::mapping_for_enrol((int) $snapshot['enrolid'], true)) {
            throw new \moodle_exception('mappingunavailable', 'enrol_storefront');
        }
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id', MUST_EXIST);
        unset($user);
        $now = time();
        $timeend = 0;
        if ($billingtype === 'subscription') {
            if (empty($subscriptionexpiry) || $subscriptionexpiry <= $now) {
                throw new \moodle_exception('subscriptionexpiryinvalid', 'enrol_storefront');
            }
            $timeend = $subscriptionexpiry;
        } else if ((int) $snapshot['enrolperiod'] > 0) {
            $timeend = $now + (int) $snapshot['enrolperiod'];
        }

        $transaction = $DB->start_delegated_transaction();
        $plugin = enrol_get_plugin('storefront');
        $existing = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid]);
        if ($existing) {
            $effectiveend = self::later_end((int) $existing->timeend, $timeend);
            $plugin->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE,
                min((int) $existing->timestart, $now), $effectiveend);
            $timeend = $effectiveend;
        } else {
            $plugin->enrol_user($instance, $userid, (int) $snapshot['roleid'], $now, $timeend, ENROL_USER_ACTIVE);
        }
        $DB->insert_record('enrol_storefront_purchase', (object) [
            'enrolid' => (int) $instance->id,
            'userid' => $userid,
            'orderid' => $orderid,
            'orderlineid' => $orderlineid,
            'productid' => (int) $snapshot['productid'],
            'courseid' => (int) $snapshot['courseid'],
            'roleid' => (int) $snapshot['roleid'],
            'billingtype' => $billingtype,
            'subscriptionref' => $subscriptionreference,
            'status' => 'active',
            'timestart' => $now,
            'timeend' => $timeend,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $transaction->allow_commit();
    }

    public static function renew_order(int $orderid, int $validuntil): void {
        global $DB;
        if ($validuntil <= time()) {
            return;
        }
        $plugin = enrol_get_plugin('storefront');
        foreach ($DB->get_records('enrol_storefront_purchase', [
            'orderid' => $orderid,
            'billingtype' => 'subscription',
        ]) as $purchase) {
            $instance = $DB->get_record('enrol', ['id' => $purchase->enrolid, 'enrol' => 'storefront']);
            $userenrolment = $DB->get_record('user_enrolments', [
                'enrolid' => $purchase->enrolid,
                'userid' => $purchase->userid,
            ]);
            if (!$instance || !$userenrolment) {
                continue;
            }
            $timeend = self::later_end((int) $userenrolment->timeend, $validuntil);
            $plugin->update_user_enrol($instance, (int) $purchase->userid, ENROL_USER_ACTIVE,
                (int) $userenrolment->timestart, $timeend);
            $purchase->status = 'active';
            $purchase->timeend = $timeend;
            $purchase->timemodified = time();
            $DB->update_record('enrol_storefront_purchase', $purchase);
        }
    }

    /** Extend only the course purchase belonging to one verified subscription line. */
    public static function renew_line(int $orderlineid, int $validuntil): void {
        global $DB;
        if ($validuntil <= time()) {
            return;
        }
        $purchases = $DB->get_records('enrol_storefront_purchase', [
            'orderlineid' => $orderlineid,
            'billingtype' => 'subscription',
        ]);
        foreach ($purchases as $purchase) {
            $instance = $DB->get_record('enrol', ['id' => $purchase->enrolid, 'enrol' => 'storefront']);
            $userenrolment = $DB->get_record('user_enrolments', [
                'enrolid' => $purchase->enrolid,
                'userid' => $purchase->userid,
            ]);
            if (!$instance || !$userenrolment) {
                continue;
            }
            $timeend = self::later_end((int) $userenrolment->timeend, $validuntil);
            enrol_get_plugin('storefront')->update_user_enrol($instance, (int) $purchase->userid, ENROL_USER_ACTIVE,
                (int) $userenrolment->timestart, $timeend);
            $purchase->status = 'active';
            $purchase->timeend = $timeend;
            $purchase->timemodified = time();
            $DB->update_record('enrol_storefront_purchase', $purchase);
        }
    }

    /** Fail-safe entry point for future verified refunds, revocations and unpaid expiries. */
    public static function suspend_order(int $orderid): void {
        global $DB;
        $affected = [];
        foreach ($DB->get_records('enrol_storefront_purchase', ['orderid' => $orderid]) as $purchase) {
            $instance = $DB->get_record('enrol', ['id' => $purchase->enrolid, 'enrol' => 'storefront']);
            $purchase->status = 'suspended';
            $purchase->timemodified = time();
            $DB->update_record('enrol_storefront_purchase', $purchase);
            if ($instance) {
                $affected[$purchase->enrolid . ':' . $purchase->userid] = [$instance, (int) $purchase->userid];
            }
        }
        foreach ($affected as [$instance, $userid]) {
            self::sync_effective_access($instance, $userid);
        }
    }

    /** Restore purchases from one order after a recovered payment or won dispute. */
    public static function resume_order(int $orderid): void {
        global $DB;
        $affected = [];
        foreach ($DB->get_records('enrol_storefront_purchase', ['orderid' => $orderid]) as $purchase) {
            $instance = $DB->get_record('enrol', ['id' => $purchase->enrolid, 'enrol' => 'storefront']);
            $purchase->status = 'active';
            $purchase->timemodified = time();
            $DB->update_record('enrol_storefront_purchase', $purchase);
            if ($instance) {
                $affected[$purchase->enrolid . ':' . $purchase->userid] = [$instance, (int) $purchase->userid];
            }
        }
        foreach ($affected as [$instance, $userid]) {
            self::sync_effective_access($instance, $userid);
        }
    }

    /** Suspend exactly one delivered order line and then recalculate overlapping course access. */
    public static function suspend_line(int $orderlineid): void {
        self::set_line_status($orderlineid, 'suspended');
    }

    /** Resume exactly one delivered order line and then recalculate overlapping course access. */
    public static function resume_line(int $orderlineid): void {
        self::set_line_status($orderlineid, 'active');
    }

    /** Remove course delivery created by explicitly verified sandbox orders. */
    public static function purge_test_orders(array $orderids, int $productid): void {
        global $DB;
        $orderids = array_values(array_unique(array_map('intval', $orderids)));
        if ($orderids === []) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($orderids, SQL_PARAMS_NAMED, 'sandboxorder');
        $params['productid'] = $productid;
        $purchases = $DB->get_records_select('enrol_storefront_purchase',
            "productid = :productid AND orderid {$insql}", $params);
        $affected = [];
        foreach ($purchases as $purchase) {
            $instance = $DB->get_record('enrol', ['id' => $purchase->enrolid, 'enrol' => 'storefront']);
            if ($instance) {
                $affected[$purchase->enrolid . ':' . $purchase->userid] = [$instance, (int) $purchase->userid];
            }
        }
        if ($purchases !== []) {
            $DB->delete_records_list('enrol_storefront_purchase', 'id', array_keys($purchases));
        }
        $plugin = enrol_get_plugin('storefront');
        foreach ($affected as [$instance, $userid]) {
            if (!$DB->record_exists('enrol_storefront_purchase', [
                'enrolid' => $instance->id,
                'userid' => $userid,
            ])) {
                $plugin->unenrol_user($instance, $userid);
            } else {
                self::sync_effective_access($instance, $userid);
            }
        }
    }

    /** Delete the Storefront enrolment mapping once no delivered purchases remain. */
    public static function unmap_product(int $productid): void {
        global $DB;
        $mappings = self::mappings_for_product($productid, false);
        if (!$mappings) {
            return;
        }
        if ($DB->record_exists('enrol_storefront_purchase', ['productid' => $productid])) {
            throw new \moodle_exception('mappinglocked', 'enrol_storefront');
        }
        foreach ($mappings as $mapping) {
            $instance = $DB->get_record('enrol', ['id' => $mapping->enrolid, 'enrol' => 'storefront'], '*', MUST_EXIST);
            enrol_get_plugin('storefront')->delete_instance($instance);
        }
    }

    private static function sync_effective_access(\stdClass $instance, int $userid): void {
        global $DB;
        $plugin = enrol_get_plugin('storefront');
        $userenrolment = $DB->get_record('user_enrolments', [
            'enrolid' => $instance->id,
            'userid' => $userid,
        ]);
        if (!$userenrolment) {
            return;
        }
        $sql = "SELECT * FROM {enrol_storefront_purchase}
                 WHERE enrolid = :enrolid AND userid = :userid AND status = :status
                   AND (timeend = 0 OR timeend > :now)";
        $active = $DB->get_records_sql($sql, [
            'enrolid' => $instance->id,
            'userid' => $userid,
            'status' => 'active',
            'now' => time(),
        ]);
        if (!$active) {
            $plugin->update_user_enrol($instance, $userid, ENROL_USER_SUSPENDED);
            return;
        }
        $timeends = array_map(static fn(\stdClass $record): int => (int) $record->timeend, $active);
        $timeend = in_array(0, $timeends, true) ? 0 : max($timeends);
        $timestart = min(array_map(static fn(\stdClass $record): int => (int) $record->timestart, $active));
        $plugin->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE, $timestart, $timeend);
    }

    private static function set_line_status(int $orderlineid, string $status): void {
        global $DB;
        $affected = [];
        foreach ($DB->get_records('enrol_storefront_purchase', ['orderlineid' => $orderlineid]) as $purchase) {
            $instance = $DB->get_record('enrol', ['id' => $purchase->enrolid, 'enrol' => 'storefront']);
            $purchase->status = $status;
            $purchase->timemodified = time();
            $DB->update_record('enrol_storefront_purchase', $purchase);
            if ($instance) {
                $affected[$purchase->enrolid . ':' . $purchase->userid] = [$instance, (int) $purchase->userid];
            }
        }
        foreach ($affected as [$instance, $userid]) {
            self::sync_effective_access($instance, $userid);
        }
    }

    private static function later_end(int $current, int $candidate): int {
        if ($current === 0 || $candidate === 0) {
            return 0;
        }
        return max($current, $candidate);
    }
}
