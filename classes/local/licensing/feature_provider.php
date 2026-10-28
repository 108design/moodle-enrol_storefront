<?php
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for classes/local/licensing/feature_provider.php.
 *
 * @package   enrol_storefront
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   108design source-available software license; see LICENSE.md
 */
namespace enrol_storefront\local\licensing;

defined('MOODLE_INTERNAL') || die();

/** The initial course fulfilment adapter is explicitly part of Storefront Free. */
final class feature_provider {
    public static function manifest(): array {
        return ['enrol_storefront/fulfilment' => [
            'label' => get_string('pluginname', 'enrol_storefront'),
            'mode' => 'free', 'entitlements' => [], 'operation' => 'fulfilment',
            'policyarea' => 'storefront', 'policyproduct' => 'coursepurchase',
            'policyproductlabel' => get_string('licensepolicyproduct', 'enrol_storefront'),
            'policyorder' => 40,
            'degradation' => get_string('licensepolicydegradation', 'enrol_storefront'),
            'discovery' => 'Shown as an included fulfilment adapter.',
        ]];
    }
}
