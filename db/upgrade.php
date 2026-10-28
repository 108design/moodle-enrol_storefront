<?php
/**
 * File documentation for db/upgrade.php.
 *
 * @package   enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>

defined('MOODLE_INTERNAL') || die();

function xmldb_enrol_storefront_upgrade(int $oldversion): bool {
    global $DB;
    if ($oldversion < 2026081003) {
        $sql = "UPDATE {enrol} e
                   SET customchar1 = (SELECT p.identifier
                                        FROM {local_storefront_product} p
                                       WHERE p.id = e.customint1)
                 WHERE e.enrol = :enrol AND e.customint1 > 0
                   AND (e.customchar1 IS NULL OR e.customchar1 = '')";
        $DB->execute($sql, ['enrol' => 'storefront']);
        upgrade_plugin_savepoint(true, 2026081003, 'enrol', 'storefront');
    }
    if ($oldversion < 2026082401) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('enrol_storefront_purchase');
        $oldindex = new xmldb_index('orderline_uix', XMLDB_INDEX_UNIQUE, ['orderlineid']);
        if ($dbman->index_exists($table, $oldindex)) {
            $dbman->drop_index($table, $oldindex);
        }
        $newindex = new xmldb_index('orderline_enrol_uix', XMLDB_INDEX_UNIQUE, ['orderlineid', 'enrolid']);
        if (!$dbman->index_exists($table, $newindex)) {
            $dbman->add_index($table, $newindex);
        }
        upgrade_plugin_savepoint(true, 2026082401, 'enrol', 'storefront');
    }
    if ($oldversion < 2026090700) {
        $result = \enrol_storefront\local\purchase_period_repair::repair_all();
        mtrace('Storefront purchase-period repair: ' . $result['repaired'] . ' repaired, '
            . $result['ambiguous'] . ' left unchanged for review.');
        upgrade_plugin_savepoint(true, 2026090700, 'enrol', 'storefront');
    }
    if ($oldversion < 2026090701) {
        // Version-only savepoint for the Storefront 2026090702 dependency floor.
        upgrade_plugin_savepoint(true, 2026090701, 'enrol', 'storefront');
    }
    return true;
}
