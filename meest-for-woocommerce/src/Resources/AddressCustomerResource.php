<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;

class AddressCustomerResource extends Resource
{
    public function toArray(): array
    {
        $type = $this->args[0];

        $data = [
            'delivery_type' => $this->data["{$type}_delivery_type"] ?? 'branch',
            'country' => [
                'code' => $this->data["{$type}_country"] ?? null,
                'id' => $this->data["{$type}_country_id"] ?? null,
                'text' => $this->data["{$type}_country_text"] ?? null
            ],
            'city' => [
                'id' => $this->data["{$type}_city_id"] ?? null,
                'text' => $this->data["{$type}_city_text"] ?? null
            ],
            'region' => [
                'id' => $this->data["{$type}_region_id"] ?? null,
                'text' => $this->data["{$type}_region_text"] ?? null
            ]
        ];

        if ($data['delivery_type'] === 'branch') {
            $data['branch'] = [
                'id' => $this->data["{$type}_branch_id"] ?? null,
                'text' => $this->data["{$type}_branch_text"] ?? null
            ];
        } elseif ($data['delivery_type'] === 'poshtomat') {
            $data['poshtomat'] = [
                'id' => $this->data["{$type}_poshtomat_id"] ?? null,
                'text' => $this->data["{$type}_poshtomat_text"] ?? null
            ];
        } else {
            $data['street'] = [
                'id' => $this->data["{$type}_street_id"] ?? null,
                'text' => $this->data["{$type}_street_text"] ?? null
            ];
            $data['building'] = $this->data["{$type}_building"] ?? null;
            $data['flat'] = $this->data["{$type}_flat"] ?? null;
            $data['postcode'] = $this->data["{$type}_postcode"] ?? null;
        }

        return $data;
    }
}
