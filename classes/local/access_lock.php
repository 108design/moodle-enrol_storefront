<?php
/**
 * File documentation for classes/local/access_lock.php.
 *
 * @package   enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
namespace enrol_storefront\local;

defined('MOODLE_INTERNAL') || die();

/** Serialises purchase projection and effective enrolment changes for one user and enrolment instance. */
final class access_lock {
    /** @var array<string, int> */
    private static array $held = [];

    /**
     * @template T
     * @param array<int, array{0:int,1:int}> $pairs enrolment-instance and user IDs
     * @param callable():T $operation
     * @return T
     */
    public static function with_pairs(array $pairs, callable $operation): mixed {
        $keys = [];
        foreach ($pairs as $pair) {
            $enrolid = (int) ($pair[0] ?? 0);
            $userid = (int) ($pair[1] ?? 0);
            if ($enrolid > 0 && $userid > 0) {
                $keys[$enrolid . ':' . $userid] = true;
            }
        }
        $keys = array_keys($keys);
        sort($keys, SORT_STRING);
        if ($keys === []) {
            return $operation();
        }

        $factory = \core\lock\lock_config::get_lock_factory('enrol_storefront_access');
        $locks = [];
        try {
            foreach ($keys as $key) {
                if (!empty(self::$held[$key])) {
                    self::$held[$key]++;
                    $locks[] = [$key, null];
                    continue;
                }
                $lock = $factory->get_lock('access:' . $key, 30);
                if (!$lock) {
                    throw new \moodle_exception('locktimeout', 'error');
                }
                self::$held[$key] = 1;
                $locks[] = [$key, $lock];
            }
            return $operation();
        } finally {
            foreach (array_reverse($locks) as [$key, $lock]) {
                self::$held[$key]--;
                if (self::$held[$key] === 0) {
                    unset(self::$held[$key]);
                    if ($lock !== null) {
                        $lock->release();
                    }
                }
            }
        }
    }
}
