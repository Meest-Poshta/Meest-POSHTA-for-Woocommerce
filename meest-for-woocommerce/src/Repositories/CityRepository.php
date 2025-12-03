<?php

namespace MeestShipping\Repositories;

use MeestShipping\Models\City;

class CityRepository extends Repository
{
    /**
     * @param string $country
     * @param null|string $text
     * @return array
     */
    public function search($country, $text = null): array
    {
        if (!empty($text) && mb_strlen($text) < 2) {
            return [];
        }

        if ($this->options['dictionary']['is_db'] ?? false) {
            return $this->fromDb($text, $country);
        }

        return $this->fromApi($text, $country);
    }

    /**
     * @param string $id
     * @return array
     */
    public function getById(string $id): array
    {
        if (empty($id)) {
            return [];
        }

        if ($this->options['dictionary']['is_db'] ?? false) {
            $item = City::findByUuid($id);

            return $this->mapItemFromDb($item);
        } else {
            $items = meest_init('Api')->searchCity(['cityID' => $id]);

            return !empty($items[0]) ? $this->mapItemFromApi($items[0]) : [];
        }
    }

    function fromApi($text = null, $country = null): array
    {
        $items = meest_init('Api')->searchCity([
            'countryID' => $country,
            'cityDescr' => "%$text%",
        ]);

        return array_map(function ($item) {
            return $this->mapItemFromApi($item);
        }, $items);
    }

    private function mapItemFromApi(array $item): array
    {
        $city = $item['cityDescr']['descr'.$this->meestLocale] ?? null;
        $district = $item['districtDescr']['descr'.$this->meestLocale] ?? null;
        $region = meest_ucfirst($item['regionDescr']['descr'.$this->meestLocale] ?? null);

        return [
            'id' => $item['cityID'],
            'text' => $city . ($city !== $district ? ', ' . $district : '') . ', ' . $region,
            'city' => $city,
            'region' => $region,
            'district' => $district,
            'branch' => $item['isBranchInCity'],
            'zone' => $item['deliveryZone'],
            'latitude' => $item['latitude'],
            'longitude' => $item['longitude'],
        ];
    }

    function fromDb($text = null, $country = null): array
    {
        $items = City::search($text, $country);

        return array_map(function ($item) {
            return $this->mapItemFromDb($item);
        }, $items);
    }

    private function mapItemFromDb(array $item): array
    {
        $city = $item['name_' . $this->locale] ?? $item['name_uk'];
        $district = $item['district_name_' . $this->locale] ?? $item['district_name_uk'];
        $region = $item['region_name_' . $this->locale] ?? $item['region_name_uk'];

        return [
            'id' => $item['city_uuid'],
            'text' => $city . ($city !== $district ? ', ' . $district : '') . ', ' . $region,
            'city' => $city,
            'region' => meest_ucfirst($region),
            'district' => meest_ucfirst($district),
            'branch' => null,
            'zone' => $item['delivery_zone'],
            'latitude' => null,
            'longitude' => null,
        ];
    }
}
