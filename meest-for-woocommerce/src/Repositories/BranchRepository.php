<?php

namespace MeestShipping\Repositories;

class BranchRepository extends Repository
{
    /**
     * @param $city
     * @param null $text
     * @param array $limit
     * @param string|null $deliveryType - 'branch', 'poshtomat' or null for all
     * @return array
     */
    public function search($city, $text = null, array $limit = [], $deliveryType = null): array
    {
        $data = [
            'cityID' => $city
        ];

        if (!empty($text)) {
            if (is_numeric($text) && strlen($text) >= 4) {
                $data['branchNo'] = $text;
            } else {
                $data['cityID'] = $city;
                $data['branchDescr'] = "%$text%";
            }
        }

        // НЕ передаем branchTypeID в API, будем фильтровать после получения
        $items = meest_init('Api')->searchBranch($data);
        
        // Фильтруем по типу отделения
        if ($deliveryType === 'poshtomat') {
            $poshtomatId = $this->options['branch_type_id']['poshtomat'];
            $items = array_filter($items, function($item) use ($poshtomatId) {
                return $item['branchTypeID'] === $poshtomatId;
            });
        } elseif ($deliveryType === 'branch') {
            $branchIds = $this->options['branch_type_id']['branch'];
            $items = array_filter($items, function($item) use ($branchIds) {
                return in_array($item['branchTypeID'], $branchIds);
            });
        }
        
        $checkLimit = self::checkLimit($limit);

        return array_values(array_map(function ($item) use ($checkLimit) {
            $street = $item['addressDescr']['descr'.$this->meestLocale];
            $branchType = $item['branchTypeAPP'] === "3" ? __('ATM', MEEST_PLUGIN_DOMAIN) : __('Branch', MEEST_PLUGIN_DOMAIN);
            $limits = [];
            if ($this->options['shipping']['branch_limits'] == 1) {
                if (!empty($item['branchLimits']['weightTotalMax'])) {
                    $limits['weight'] = sprintf(__('weight - %s kg', MEEST_PLUGIN_DOMAIN), $item['branchLimits']['weightTotalMax']);
                }
                if (!empty($item['branchLimits']['gabaritesMax']['length'])) {
                    $limits['size'] = sprintf(
                        __('size(lwh) - %sx%sx%s cm', MEEST_PLUGIN_DOMAIN),
                        ...array_values($item['branchLimits']['gabaritesMax'])
                    );
                }
                if (!empty($item['branchLimits']['insuranceTotalMax'])) {
                    $limits['insurance'] = sprintf(__('insurance - %s', MEEST_PLUGIN_DOMAIN), $item['branchLimits']['insuranceTotalMax']);
                }
            }

            return [
                'id' => $item['branchID'],
                'text' => $street.', '.$item['building'].' - '.$branchType.' #'.$item['branchNo']
                    .(!empty($item['addressMoreInformation']) ? ' ('.$item['addressMoreInformation'].')' : ''),
                'description' => !empty($limits) ? (__('Limits', MEEST_PLUGIN_DOMAIN).': '.implode(', ', $limits)) : '',
                'type' => $item['branchType'],
                'type_app' => $item['branchTypeAPP'],
                'weight' => $item['branchLimits']['weightTotalMax'],
                'insurance' => $item['branchLimits']['insuranceTotalMax'],
                'volume' => $item['branchLimits']['volumeTotalMax'],
                'lwh' => [
                    $item['branchLimits']['gabaritesMax']['length'],
                    $item['branchLimits']['gabaritesMax']['width'],
                    $item['branchLimits']['gabaritesMax']['height'],
                ],
                'limit_error' => $checkLimit($item),
            ];
        }, $items));
    }

    /**
     * @param string $id
     * @param array $limit
     * @return array
     */
    public function getById(string $id, array $limit = []): array
    {
        if (empty($id)) {
            return [];
        }

        $items = meest_init('Api')->searchBranch(['branchID' => $id]);
        if (empty($items[0])) {
            return [];
        }

        $item = $items[0];

        $branchType = $item['branchTypeAPP'] === "3" ? __('ATM', MEEST_PLUGIN_DOMAIN) : __('Branch', MEEST_PLUGIN_DOMAIN);

        return [
            'id' => $item['branchID'],
            'region' => meest_ucfirst($item['regionDescr']['descr'.$this->meestLocale]),
            'district' => meest_ucfirst($item['districtDescr']['descr'.$this->meestLocale]),
            'city' => $item['cityDescr']['descr'.$this->meestLocale],
            'street' => $item['addressDescr']['descr'.$this->meestLocale],
            'building' => $item['building'],
            'flat' => $branchType.' #'.$item['branchNo'] . (!empty($item['addressMoreInformation']) ? ' (' . $item['addressMoreInformation'] . ')' : ''),
            'zipCode' => $item['zipCode'],
            'latitude' => $item['latitude'],
            'longitude' => $item['longitude'],
            'phone' => $item['phone'],
            'address' => $item['address'],
            'type' => $item['branchType'],
            'type_app' => $item['branchTypeAPP'],
            'weight' => $item['branchLimits']['weightTotalMax'],
            'insurance' => $item['branchLimits']['insuranceTotalMax'],
            'volume' => $item['branchLimits']['volumeTotalMax'],
            'lwh' => [
                $item['branchLimits']['gabaritesMax']['length'],
                $item['branchLimits']['gabaritesMax']['width'],
                $item['branchLimits']['gabaritesMax']['height'],
            ],
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    public function types(array $data = []): array
    {
        $items = meest_init('Api')->getBranchTypes();
        $checkLimit = self::checkLimit($data);

        return array_filter(array_map(function ($item) use ($checkLimit) {
            if (!$checkLimit($item)) {
                return null;
            }

            return [
                'id' => $item['branchTypeID'],
                'description' => $item['branchTypeDescr']['descrUA'],
                'type' => $item['branchTypeDescr']['type'],
                'type_app' => $item['branchTypeDescr']['typeAPP'],
                'weight' => $item['branchLimits']['weightTotalMax'],
                'insurance' => $item['branchLimits']['insuranceTotalMax'],
                'volume' => $item['branchLimits']['volumeTotalMax'],
                'length' => $item['branchLimits']['gabaritesMax']['length'],
                'width' => $item['branchLimits']['gabaritesMax']['width'],
                'height' => $item['branchLimits']['gabaritesMax']['height'],
            ];
        }, $items));
    }

    /**
     * @param array $data
     * @return \Closure
     */
    public static function checkLimit($data = []): \Closure
    {
        $isLimit = function ($key, $limit) use ($data) {
            return isset($data[$key]) && $limit !== 0 && $data[$key] > $limit;
        };

        return function ($item) use ($isLimit): ?string {
            if ($isLimit('weight', $item['branchLimits']['weightTotalMax'])) {
                return 'weight';
            }
            if ($isLimit('insurance', $item['branchLimits']['insuranceTotalMax'])) {
                return 'insurance';
            }
            if ($isLimit('volume', $item['branchLimits']['volumeTotalMax'])) {
                return 'volume';
            }
            if ($isLimit('length', $item['branchLimits']['gabaritesMax']['length'])) {
                return 'length';
            }
            if ($isLimit('width', $item['branchLimits']['gabaritesMax']['width'])) {
                return 'width';
            }
            if ($isLimit('height', $item['branchLimits']['gabaritesMax']['height'])) {
                return 'height';
            }

            return null;
        };
    }

    /**
     * Поиск поштоматов
     * @param $city
     * @param null $text
     * @param array $limit
     * @return array
     */
    public function searchPoshtomat($city, $text = null, array $limit = []): array
    {
        return $this->search($city, $text, $limit, 'poshtomat');
    }

    /**
     * Поиск обычных отделений (без поштоматов)
     * @param $city
     * @param null $text
     * @param array $limit
     * @return array
     */
    public function searchBranchOnly($city, $text = null, array $limit = []): array
    {
        return $this->search($city, $text, $limit, 'branch');
    }
}
