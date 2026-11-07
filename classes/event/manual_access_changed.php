<?php
/**
 * A shop operator changed a purchased course access.
 *
 * @package enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace enrol_storefront\event;

/** Log the operator's decision even when another purchase keeps access active. */
final class manual_access_changed extends \core\event\base {
    /** Set the event's classification. */
    protected function init(): void {
        $this->data['objecttable'] = 'enrol_storefront_purchase';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /** Return the event's display name. */
    public static function get_name(): string {
        return get_string('eventmanualaccesschanged', 'enrol_storefront');
    }

    /** Describe the recorded decision. */
    public function get_description(): string {
        $action = $this->other['suspended'] ? 'suspended' : 'released the hold on';
        return "The user with id '$this->userid' $action purchased course access with id '$this->objectid' "
            . "for the user with id '$this->relateduserid'.";
    }
}
