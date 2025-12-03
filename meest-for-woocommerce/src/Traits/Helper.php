<?php

namespace MeestShipping\Traits;

trait Helper
{
    public static function isMeestShipping(): bool
    {
        // Проверяем POST данные
        if (!empty($_POST['shipping_method'])) {
            return strpos($_POST['shipping_method'][0], MEEST_PLUGIN_NAME) === 0;
        }

        // Проверяем сессию WooCommerce
        if (WC()->session) {
            $chosenShippingMethods = WC()->session->chosen_shipping_methods;
            if (!empty($chosenShippingMethods[0])) {
                return strpos($chosenShippingMethods[0], MEEST_PLUGIN_NAME) === 0;
            }
        }

        // Проверяем выбранные методы доставки через WC
        if (function_exists('WC') && WC()->cart) {
            $packages = WC()->cart->get_shipping_packages();
            if (!empty($packages)) {
                foreach ($packages as $package) {
                    if (!empty($package['rates'])) {
                        foreach ($package['rates'] as $rate) {
                            if (strpos($rate->get_id(), MEEST_PLUGIN_NAME) === 0) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public static function shipToDifferentAddress(): string
    {
        // Проверяем различные варианты значения ship_to_different_address
        $ship_to_different = !empty($_POST['ship_to_different_address']) && 
                           ($_POST['ship_to_different_address'] === '1' || 
                            $_POST['ship_to_different_address'] === 'true' || 
                            $_POST['ship_to_different_address'] === true);
        
        return $ship_to_different ? 'shipping' : 'billing';
    }

    private static function implodePack(&$package, $parcel)
    {
        $packageMax = array_search(max($package), $package);
        $packageMin = array_search(min($package), $package);
        $parcelMax = array_search(max($parcel), $parcel);
        $parcelMin = array_search(min($parcel), $parcel);

        if ($parcel[$parcelMax] > $package[$packageMax]) {
            $package[2] = (int) $parcel[$parcelMax];
        }
        if ($parcel[$parcelMin] > $package[$packageMin]) {
            $package[1] = (int) $parcel[$parcelMin];
        }
        $size = array_diff_key($parcel, array_flip([$parcelMax, $parcelMin]));
        $package[0] += (int) array_shift($size);
    }
}
