<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Error;
use MeestShipping\Core\Resource;

class SettingResource extends Resource
{
    public function toArray(): array
    {
        if ($this->args[0] === 'general') {
            return $this->generalArray();
        } elseif ($this->args[0] === 'delivery_cost') {
            return $this->deliveryCostArray();
        } elseif ($this->args[0] === 'delivery_api') {
            return $this->apiArray();
        }

        return [];
    }

    public function generalArray(): array
    {
        $data =  [
            'contact' => [
                'first_name' => $this->data['contact']['first_name'] ?? null,
                'last_name' => $this->data['contact']['last_name'] ?? null,
                'middle_name' => $this->data['contact']['middle_name'] ?? null,
                'phone' => $this->data['contact']['phone'] ?? null,
            ],
            'address' => [
                'delivery_type' => $this->data['address']['delivery_type'] ?? null,
                'country' => [
                    'id' => $this->data['address']['country']['id'] ?? null,
                    'text' => $this->data['address']['country']['text'] ?? null,
                    'code' => $this->data['address']['country']['code'] ?? null,
                ],
                'region' => [
                    'id' => $this->data['address']['region']['id'] ?? null,
                    'text' => $this->data['address']['region']['text'] ?? null,
                ],
                'city' => [
                    'id' => $this->data['address']['city']['id'] ?? null,
                    'text' => $this->data['address']['city']['text'] ?? null,
                ],
                'street' => [
                    'id' => $this->data['address']['street']['id'] ?? null,
                    'text' => $this->data['address']['street']['text'] ?? null,
                ],
                'building' => $this->data['address']['building'] ?? null,
                'flat' => $this->data['address']['flat'] ?? null,
                'postcode' => $this->data['address']['postcode'] ?? null,
                'branch' => [
                    'id' => $this->data['address']['branch']['id'] ?? null,
                    'text' => $this->data['address']['branch']['text'] ?? null,
                ],
                'poshtomat' => [
                    'id' => $this->data['address']['poshtomat']['id'] ?? null,
                    'text' => $this->data['address']['poshtomat']['text'] ?? null,
                ],
            ],
            'shipping' => [
                'delivery_type' => $this->data['shipping']['delivery_type'] ?? null,
                'delivery_type_branch' => $this->data['shipping']['delivery_type_branch'] ?? 0,
                'delivery_type_poshtomat' => $this->data['shipping']['delivery_type_poshtomat'] ?? 0,
                'delivery_type_address' => $this->data['shipping']['delivery_type_address'] ?? 0,
                'branch_limits' => $this->data['shipping']['branch_limits'] ?? 0,
                'package' => $this->data['shipping']['package'] ?? 0,
                'send_email' => $this->data['shipping']['send_email'] ?? 0,
            ],
            'parcel' => [
                'is_insurance' => $this->data['parcel']['is_insurance'] ?? false,
                'insurance' => $this->data['parcel']['is_insurance'] ? $this->data['parcel']['insurance'] : null,
                'weight' => $this->data['parcel']['weight'] ?? 0.1,
                'lwh' => [
                    $this->data['parcel']['lwh'][0] ?? 10,
                    $this->data['parcel']['lwh'][1] ?? 10,
                    $this->data['parcel']['lwh'][2] ?? 10,
                ],
            ],
        ];

        return $data;
    }

    public function deliveryCostArray(): array
    {
        $deliveryCostType = $this->data['shipping']['delivery_cost_type'];
        if ($deliveryCostType === 'fixed') {
            $deliveryCost = $this->data['shipping']['delivery_cost_fixed'] ?? null;
            $deliveryCosts = [
                [null, null, $deliveryCost]
            ];
        } elseif ($deliveryCostType === 'range') {
            $deliveryCosts = $this->data['shipping']['delivery_cost'] ?? [];
            if (!empty($deliveryCosts)) {
                usort($deliveryCosts, function ($current, $next) {
                    if ($current[0] == $next[0]) {
                        return 0;
                    }

                    return ($current[0] < $next[0]) ? -1 : 1;
                });


                foreach ($deliveryCosts as $key => &$deliveryCost) {
                    $deliveryCost[0] = (float)$deliveryCost[0];
                    $deliveryCost[1] = (float)$deliveryCost[1];
                    $deliveryCost[2] = (float)$deliveryCost[2];

                    if (array_key_exists($key + 1, $deliveryCosts) && !empty($deliveryCosts[$key + 1][0]) && $deliveryCost[0] >= (float)$deliveryCosts[$key + 1][0]) {
                        Error::add('bad-request', 'Incorrect min value range!', 'error');
                    }
                    if (array_key_exists($key + 1, $deliveryCosts) && !empty($deliveryCosts[$key + 1][0]) && $deliveryCost[1] >= (float)$deliveryCosts[$key + 1][0]) {
                        Error::add('bad-request', 'Incorrect max value range!', 'error');
                    }
                }
            }
        } else {
            $deliveryCosts = [];
        }

        return [
            'shipping' => [
                'delivery_cost_type' => $deliveryCostType,
                'delivery_cost' => $deliveryCosts,
                'calc_cost' => $this->data['shipping']['calc_cost'] ?? 0,
                'auto_cod' => $this->data['shipping']['auto_cod'] ?? 0,
            ],
        ];
    }

    public function apiArray(): array
    {
        $data = [
            'credential' => [
                'username' => $this->data['credential']['username'] ?? null,
                'password' => $this->data['credential']['password'] ?? null,
                'token' => $this->data['credential']['token'] ?? null,
                'contract_id' => $this->data['credential']['contract_id'] ?? null,
            ],
            'dictionary' => [
                'is_db' => $this->data['dictionary']['is_db'] ?? false,
                'auto_update' => $this->data['dictionary']['auto_update'] ?? false,
            ],
        ];

        if ($this->data['url'] !== $this->options['url']) {
            $data['url'] = $this->data['url'];
        }

        return $data;
    }
}
