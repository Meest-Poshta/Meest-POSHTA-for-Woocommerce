<?php
namespace MeestShipping\Resources;

use MeestShipping\Core\Resource;

class ParcelApiResource extends Resource
{
    public function toArray(): array
    {
        $places = $this->getPlaces();
        $codAmount = $this->options['shipping']['auto_cod'] == 1 ? $this->data['parcel']['cod'] : null;

        $result = [
            'parcelNumber' => '',
            'sendingDate' => date('d.m.Y'),
            'contractID' => $this->options['credential']['contract_id'] ?? '',
            'COD' => $codAmount,
            'placesItems' => $places,
            'payType' => $this->data['parcel']['pay_type'] == 1 ? 'cash' : 'nonCash',
            'orderNumber' => $this->data['order']['id'] ?? '',
            'receiverPay' => (bool) $this->data['parcel']['payer'],
            'info4Sticker' => true,
            'sender' => array_merge(
                $this->getUser($this->data, 'sender'),
                $this->getAddress($this->data, 'sender')
            ),
            'receiver' => array_merge(
                $this->getUser($this->data, 'receiver'),
                $this->getAddress($this->data, 'receiver')
            ),
        ];

        // Добавляем notation если есть
        if (!empty($this->data['parcel']['notation'])) {
            $result['notation'] = $this->data['parcel']['notation'];
        }

        // Добавляем cardForCOD только если есть COD, указана карта И нет contractID
        // API не принимает cardForCOD если есть contractID
        if ($codAmount && !empty($this->data['parcel']['card_number']) && empty($result['contractID'])) {
            $result['cardForCOD'] = [
                'number' => $this->data['parcel']['card_number'],
                'ownername' => $this->data['parcel']['card_ownername'] ?? '',
                'ownermobile' => $this->data['parcel']['card_ownermobile'] ?? '',
            ];
        }

        return $result;
    }

    private function getUser($data, $type): array
    {
        return [
            'name' => $data[$type]['last_name'].' '.$data[$type]['first_name']
                .($data[$type]['middle_name'] ? ' '.$data[$type]['middle_name'] : null),
            'phone' => $data[$type]['phone']
        ];
    }

    private function getAddress($data, $type): array
    {
        $arr = [];

        if ($data[$type]['delivery_type'] === 'branch') {
            $arr['service'] = 'Branch';
            $arr['branchID'] = $data[$type]['branch']['id'];
        } elseif ($data[$type]['delivery_type'] === 'poshtomat') {
            // Поштомат обрабатывается как Branch
            $arr['service'] = 'Branch';
            $arr['branchID'] = $data[$type]['poshtomat']['id'];
        } else {
            // Курьерская доставка (address)
            $arr['service'] = 'Door';
            if ($data[$type]['country']['id'] === $this->options['country_id']['ua']) {
                $arr['cityId'] = $data[$type]['city']['id'];
                $arr['addressId'] = $data[$type]['street']['id'];
            } else {
                $arr['regionDescr'] = $data[$type]['region']['text'];
                $arr['cityDescr'] = $data[$type]['city']['text'];
                $arr['addressDescr'] = $data[$type]['street']['text'];
            }
            $arr['building'] = $data[$type]['building'];
            $arr['flat'] = $data[$type]['flat'];
        }

        return $arr;
    }

    private function getPlaces(): array
    {
        $length = floatval($this->data['parcel']['lwh'][0]);
        $width = floatval($this->data['parcel']['lwh'][1]);
        $height = floatval($this->data['parcel']['lwh'][2]);
        
        $arr[] = [
            'formatID' => '',
            'insurance' => $this->data['parcel']['insurance'],
            'height' => number_format($height, 2, '.', ''),
            'length' => number_format($length, 2, '.', ''),
            'quantity' => '1',
            'width' => number_format($width, 2, '.', ''),
            'weight' => number_format(floatval($this->data['parcel']['weight']), 2, '.', ''),
            'volume' => $length * $width * $height,
        ];

        return $arr;
    }
}
