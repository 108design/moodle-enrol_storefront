<?php
// Copyright (C) 2026 Andreas Giesen. See LICENSE.md for the full terms.
defined('MOODLE_INTERNAL') || die();

/** Full-owned in-place bridge. Never invokes runtime services, seeds content or acquires a licence. */
function xmldb_enrol_storefront_upgrade_le(int $oldversion): bool {
    global $CFG, $DB;
    if (get_config('enrol_storefront', 'distribution') !== 'course' && $oldversion !== 2026010101) { return false; }
    $component = 'enrol_storefront';
    $root = __DIR__ . '/..';
    $receipt = json_decode((string)get_config($component, 'editiontransition'), true);
    $contract = json_decode((string)file_get_contents($root . '/edition-migrations.json'), true);
    $raw = (string)file_get_contents($root . '/release-manifest.json');
    $manifest = json_decode($raw, true);
    if (!is_array($receipt) || ($receipt['schema'] ?? null) !== 1
            || !in_array($receipt['status'] ?? '', ['prepared', 'completed'], true)
            || ($receipt['component'] ?? '') !== $component || ($receipt['site'] ?? '') !== $CFG->wwwroot
            || ($receipt['sourceversion'] ?? 0) !== $oldversion
            || ($receipt['targetversion'] ?? 0) !== ($manifest['version'] ?? -1)
            || ($receipt['manifestsha256'] ?? '') !== hash('sha256', $raw)
            || ($manifest['component'] ?? '') !== $component || ($manifest['edition'] ?? '') !== 'free'
            || empty($receipt['consentedat']) || empty($receipt['ready'])
            || !in_array($oldversion, $contract['source_versions'] ?? [], true)) {
        throw new upgrade_exception($component, $oldversion,
            'Use the confirmed Bulk Installer edition transition. Restore the unchanged LE package or resume the exact confirmed target with its receipt; do not uninstall the plugin.');
    }
    foreach ($manifest['files'] as $file => $sha256) {
        if (!is_file($root . '/' . $file) || hash_file('sha256', $root . '/' . $file) !== $sha256) {
            throw new upgrade_exception($component, $oldversion, 'Confirmed migration target bytes changed. Restore the exact package and resume.');
        }
    }
    foreach ($contract['tables'] as $table => $expected) {
        $actual = array_keys($DB->get_columns($table));
        sort($actual); sort($expected);
        if ($actual !== $expected) { throw new upgrade_exception($component, $oldversion, 'Unexpected LE source schema.'); }
    }
    $schema = new xmldb_file(__DIR__ . '/install.xml');
    if (!$schema->loadXMLStructure()) { throw new upgrade_exception($component, $oldversion, 'Invalid target schema.'); }
    $dbman = $DB->get_manager();
    foreach ($schema->getStructure()->getTables() as $table) {
        if (!$dbman->table_exists($table)) { $dbman->create_table($table); }
        else {
            $actual = array_keys($DB->get_columns($table->getName()));
            $expected = array_map(static fn(xmldb_field $field): string => $field->getName(), $table->getFields());
            sort($actual); sort($expected);
            if ($actual !== $expected) { throw new upgrade_exception($component, $oldversion, 'Unexpected target schema; resume after reconciliation.'); }
        }
    }
    // IDs, contexts, publication pointers, JSON/CSS, file areas, completion and resource ownership are untouched.
    set_config('distribution', 'full', $component);
    set_config('distributionorigin', 'full', $component);
    $receipt['status'] = 'completed';
    $receipt['completedat'] = $receipt['completedat'] ?? time();
    set_config('editiontransition', json_encode($receipt, JSON_THROW_ON_ERROR), $component);
    upgrade_plugin_savepoint(true, 2026110600, 'enrol', 'storefront');
    return true;
}
