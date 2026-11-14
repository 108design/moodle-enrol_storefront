<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Native backup and restore integration for Storefront Enrolment.
 *
 * @package enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Keep disabled product hints and exclude purchase-owned user enrolments from native backups.
 */
final class backup_enrol_storefront_plugin extends backup_enrol_plugin {
    /**
     * Back up only the mapping hint, excluding purchased enrolments.
     *
     * @return void
     */
    public function define_enrol_plugin_structure(): void {
        global $DB;
        $enrol = $this->optigroup->get_parent();
        $columns = ['e.id'];
        foreach ($enrol->get_final_elements() as $name => $element) {
            if ($name === 'status') {
                $columns[] = "CASE WHEN e.enrol='storefront' THEN 1 ELSE e.status END AS status";
            } else if ($name === 'customint1') {
                $columns[] = "CASE WHEN e.enrol='storefront' THEN 0 ELSE e.customint1 END AS customint1";
            } else {
                $columns[] = 'e.' . $name;
            }
        }
        // Core sources the shared parent from a table. Clear it before choosing SQL.
        $enrol->set_source_table(null, []);
        $enrol->set_source_sql(
            'SELECT ' . implode(',', $columns) . ' FROM {enrol} e WHERE e.courseid=? ORDER BY e.sortorder',
            [backup::VAR_COURSEID]
        );
        $purchases = $enrol->get_child('user_enrolments')->get_child('enrolment');
        $purchases->set_source_table(null, []);
        $users = $this->get_setting_value('users');
        $roles = $this->task->get_kept_roles();
        if (!$users && !$roles) {
            return;
        }
        $sql = "SELECT ue.* FROM {user_enrolments} ue JOIN {enrol} e ON e.id=ue.enrolid
            WHERE ue.enrolid=? AND e.enrol<>'storefront'";
        $params = [backup::VAR_PARENTID];
        if ($roles) {
            [$insql, $inparams] = $DB->get_in_or_equal($roles);
            $sql .= " AND EXISTS (SELECT 1 FROM {role_assignments} ra WHERE ra.userid=ue.userid
                AND ra.contextid=? AND ra.roleid $insql)";
            $params[] = backup::VAR_CONTEXTID;
            foreach ($inparams as $param) {
                $params[] = backup_helper::is_sqlparam($param);
            }
        }
        $purchases->set_source_sql($sql, $params);
    }
}
