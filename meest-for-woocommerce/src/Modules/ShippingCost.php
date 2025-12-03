<?php

namespace MeestShipping\Modules;

use MeestShipping\Resources\CostApiResource;

class ShippingCost
{
    private $options;
    private $cost;

    public function __construct($cost = null)
    {
        $this->options = meest_init('Option')->all();
        $this->cost = $cost;
    }

    public function calc()
    {
        if ($this->options['shipping']['calc_cost'] == 0) {
            return $this->cost;
        }

        if (
            !empty($_GET['wc-ajax'])
            && in_array($_GET['wc-ajax'], ['update_order_review', 'update_shipping_method'])
            && !empty($_POST['post_data'])
        ) {
            parse_str($_POST['post_data'], $post);

            $cart = WC()->cart;
            $post['items'] = $cart->get_cart_contents();
            $post['totals'] = $cart->get_totals();

            if (!empty($this->options['shipping']['delivery_cost'][0][2])) {
                return self::getDeliveryCost($this->options['shipping']['delivery_cost'], $post['totals']['subtotal']);
            }

            if (CostApiResource::check($post)) {
                $costApiData = CostApiResource::make($post);
                $response = meest_init('Api')->calculate($costApiData);

                return (float)$response['costServices'];
            }
        } else {
            $cart = WC()->cart;
            $post['totals'] = $cart->get_totals();

            if (!empty($this->options['shipping']['delivery_cost'][0][2])) {
                return self::getDeliveryCost($this->options['shipping']['delivery_cost'], $post['totals']['subtotal']);
            }
        }

        return (float)$this->cost;
    }

    private static function getDeliveryCost(array $costs, $price)
    {
        foreach ($costs as $cost) {
            if ((empty($cost[0]) || $price >= $cost[0]) && (empty($cost[1]) || $price <= $cost[1])) {
                return $cost[2];
            }
        }

        return 0;
    }
}
