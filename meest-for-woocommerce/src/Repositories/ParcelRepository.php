<?php

namespace MeestShipping\Repositories;

use MeestShipping\Models\Parcel;

class ParcelRepository extends Repository
{
    public static function findByPickup($id)
    {
        global $wpdb;
        
        $self = new Parcel();
        $mpTable = $self->getTable();
        $mppTable = $self->getTable('meest_pickup_parcel');
        
        // Use $wpdb->prepare() to prevent SQL injection
        $query = $wpdb->prepare(
            "SELECT * FROM $mpTable LEFT JOIN $mppTable AS mpp ON mpp.parcel_id = id WHERE mpp.pickup_id = %d",
            $id
        );

        $results = $self->getResults($query);

        $objects = [];
        foreach ($results as $result) {
            $object = new Parcel();
            $object->fill($result);
            $objects[] = $object;
        }

        return $objects;
    }
}
