<?php
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
