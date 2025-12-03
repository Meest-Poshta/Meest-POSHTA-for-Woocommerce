<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;

class CostApiResource extends Resource
{
    protected $sanitize = false;

    public function toArray(): array
    {
        $items = $this->getItems($this->data['items']);

        return [
            'sendingDate' => date('d.m.Y'),
            'COD' => $this->options['shipping']['auto_cod'] == 1 && $this->data['payment_method'] === 'cod'
                ? $this->data['totals']['total']
                : null,
            'sender' => $this->getSenderAddress($this->options['address']),
            'receiver' => $this->getReceiverAddress(meest_sanitize_text_field($this->data)),
            'placesItems' => $this->getPlaces($items),
        ];
    }

    public static function check($data): bool
    {
        $self = new static($data);

        $type = empty($self->data['ship_to_different_address']) ? 'billing' : 'shipping';

        if (isset($self->data["{$type}_delivery_type"]) && $self->data["{$type}_delivery_type"] === 'branch') {
            return !empty($self->data["{$type}_branch_id"]);
        } else {
            if (isset($self->data["{$type}_country_id"]) && $self->data["{$type}_country_id"] === $self->options["country_id"]['ua']) {
                return !empty($self->data["{$type}_city_id"]);
            } else {
                return !empty($self->data["{$type}_country_id"]) && !empty($self->data["{$type}_region_text"]) && !empty($self->data["{$type}_city_text"]);
            }
        }
    }

    private function getSenderAddress($data): array
    {
        $arr = [
            'countryId' => $data['country']['id']
        ];

        if ($data['delivery_type'] === 'branch') {
            $arr['service'] = 'Branch';
            $arr['branchId'] = $data['branch']['id'];
        } else {
            $arr['service'] = 'Door';
            if ($data['country']['id'] === $this->options['country_id']['ua']) {
                $arr['cityId'] = $data['city']['id'];
            } else {
                $arr['countryId'] = $data['country']['id'];
                $arr['regionDescr'] = $data['region']['text'];
                $arr['cityDescr'] = $data['city']['text'];
            }
            $arr['building'] = $data['building'];
            $arr['flat'] = $data['flat'];
        }

        return $arr;
    }

    private function getReceiverAddress($data): array
    {
        $type = empty($data['ship_to_different_address']) ? 'billing' : 'shipping';

        $arr = [
            'countryId' => $data["{$type}_country_id"] ?? null
        ];

        if (isset($data["{$type}_delivery_type"]) && $data["{$type}_delivery_type"] === 'branch') {
            $arr['service'] = 'Branch';
            $arr['branchId'] = $data["{$type}_branch_id"];
        } else {
            $arr['service'] = 'Door';
            if (isset($data["{$type}_country_id"]) && $data["{$type}_country_id"] === $this->options['country_id']['ua']) {
                $arr['cityId'] = $data["{$type}_city_id"];
            } else {
                $arr['regionDescr'] = $data["{$type}_region_text"];
                $arr['cityDescr'] = $data["{$type}_city_text"];
            }
            $arr['building'] = $data["{$type}_building"];
            $arr['flat'] = $data["{$type}_flat"];
        }

        return $arr;
    }

    private function getItems($items): array
    {
        $arr = [];
        foreach ($items as $item) {
            $arr[] = [
                'contentName' => $item['data']->name,
                'quantity' => $item['quantity'],
                'weight' => $item['data']->weight,
                'value' => $item['line_total'],
                //'customsCode' => '',
                'length' => $item['data']->length,
                'width' => $item['data']->width,
                'height' => $item['data']->height,
            ];
        }

        return $arr;
    }

    private function getPlaces($items): array
    {
        $weight = !empty($this->options['parcel']['weight']) ? $this->options['parcel']['weight'] : array_sum(array_column($items, 'weight'));
        $length = $this->options['parcel']['lwh'][0] ?: null;
        $width = $this->options['parcel']['lwh'][1] ?: null;
        $height = $this->options['parcel']['lwh'][2] ?: null;
        $volume = array_sum(array_column($items, 'value'));
        $insurance = $this->options['parcel']['is_insurance'] ? ($this->options['parcel']['insurance'] ?: $volume) : null;

        return [
            [
                'quantity' => 1,
                'weight' => $weight,
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'insurance' => $insurance,
            ]
        ];
    }
}
