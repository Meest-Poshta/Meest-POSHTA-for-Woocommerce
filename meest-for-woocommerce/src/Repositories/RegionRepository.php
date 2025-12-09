<?php

namespace MeestShipping\Repositories;

class RegionRepository extends Repository
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

        $items = meest_init('Api')->searchRegion(['regionID' => $id]);

        return !empty($items[0]) ? $this->mapItemFromApi($items[0]) : [];
    }

    function fromApi($text = null, $country = null): array
    {
        $items = meest_init('Api')->searchRegion([
            'countryID' => $country,
            'regionDescr' => "%$text%",
        ]);

        return array_map(function ($item) {
            return $this->mapItemFromApi($item);
        }, $items);
    }

    private function mapItemFromApi(array $item): array
    {
        $region = meest_ucfirst($item['regionDescr']['descr'.$this->meestLocale] ?? null);

        return [
            'id' => $item['regionID'],
            'text' => $region,
        ];
    }

    function fromDb($text = null, $country = null): array
    {
        // Implement DB search if needed
        return [];
    }
}
