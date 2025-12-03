<?php

namespace MeestShipping\Core;

defined( 'ABSPATH' ) || exit;

class Customer
{
    private static $instance;

    private function __construct()
    {
        //
    }

    public static function instance(): Customer
    {
        return static::$instance ?? static::$instance = new static();
    }

    public function getValue($input)
    {
        $woo = WC();
        if (is_callable([$woo->customer, "get_$input"])) {
            return $woo->customer->{"get_$input"}();
        }

        return null;
    }

    public function getMeta($key, $default = null)
    {
        $woo = WC();
        if ($woo->customer->get_id()) {
            return get_user_meta($woo->customer->get_id(), $key, true) ?: $default;
        }

        return $woo->session->get($key, $default);
    }

    public function setMeta($key, $value)
    {
        $woo = WC();
        if ($woo->customer->get_id()) {
            return update_user_meta($woo->customer->get_id(), $key, $value);
        }

        $woo->session->set($key, $value);
    }

    public function deleteMeta($key)
    {
        $woo = WC();
        if ($woo->customer->get_id()) {
            return delete_user_meta($woo->customer->get_id(), $key);
        }

        $woo->session->set($key, null);
    }
}
