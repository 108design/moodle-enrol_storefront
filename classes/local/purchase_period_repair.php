<?php
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
namespace enrol_storefront\local;

defined('MOODLE_INTERNAL') || die();

/** Reconstructs purchase-owned course periods that older code may have copied from aggregate enrolments. */
final class purchase_period_repair {
    /** @return array{repaired:int,ambiguous:int} */
    public static function repair_all(): array {
        global $DB;
        $updates = [];
        $ambiguous = 0;
        foreach ($DB->get_records('enrol_storefront_purchase') as $purchase) {
            $expected = self::expected_end($purchase);
            if ($expected === null) {
                $ambiguous++;
                continue;
            }
            if ($expected !== (int) $purchase->timeend) {
                $updates[(int) $purchase->id] = [
                    'timeend' => $expected,
                    'enrolid' => (int) $purchase->enrolid,
                    'userid' => (int) $purchase->userid,
                ];
            }
        }
        $pairs = array_map(static fn(array $update): array => [$update['enrolid'], $update['userid']], $updates);
        access_lock::with_pairs($pairs, static function() use ($DB, $updates): void {
            $transaction = $DB->start_delegated_transaction();
            $affected = [];
            foreach ($updates as $id => $update) {
                if (!$DB->record_exists('enrol_storefront_purchase', ['id' => $id])) {
                    continue;
                }
                $DB->update_record('enrol_storefront_purchase', (object) [
                    'id' => $id,
                    'timeend' => $update['timeend'],
                    'timemodified' => time(),
                ]);
                $affected[$update['enrolid'] . ':' . $update['userid']] = [
                    $update['enrolid'],
                    $update['userid'],
                ];
            }
            foreach ($affected as [$enrolid, $userid]) {
                fulfilment_service::reconcile_effective_access($enrolid, $userid);
            }
            $transaction->allow_commit();
        });
        return ['repaired' => count($updates), 'ambiguous' => $ambiguous];
    }

    private static function expected_end(\stdClass $purchase): ?int {
        global $DB;
        if ((string) $purchase->billingtype === 'subscription') {
            $contracts = $DB->get_records('local_storefront_billctr', [
                'orderlineid' => $purchase->orderlineid,
                'iscurrent' => 1,
            ]);
            if (count($contracts) !== 1) {
                return null;
            }
            $contract = reset($contracts);
            return !empty($contract->currentperiodend) ? (int) $contract->currentperiodend : null;
        }
        if ((string) $purchase->billingtype !== 'oneoff') {
            return null;
        }
        $line = $DB->get_record('local_storefront_orderline', ['id' => $purchase->orderlineid], 'offeringjson');
        if (!$line) {
            return null;
        }
        $offer = json_decode((string) $line->offeringjson, true);
        if (!is_array($offer)) {
            return null;
        }
        $courses = is_array($offer['courses'] ?? null) ? $offer['courses'] : [];
        if ($courses === [] && is_array($offer['course'] ?? null)) {
            $courses[] = $offer['course'];
        }
        $matches = array_values(array_filter($courses, static function($course) use ($purchase): bool {
            if (!is_array($course)) {
                return false;
            }
            if (!empty($course['enrolid'])) {
                return (int) $course['enrolid'] === (int) $purchase->enrolid;
            }
            return !empty($course['courseid']) && (int) $course['courseid'] === (int) $purchase->courseid;
        }));
        if (count($matches) !== 1 || !array_key_exists('enrolperiod', $matches[0])) {
            return null;
        }
        $period = max(0, (int) $matches[0]['enrolperiod']);
        return $period === 0 ? 0 : (int) $purchase->timestart + $period;
    }
}
