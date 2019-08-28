<?php

declare(strict_types=1);

namespace App\Formatter;

use App\Model\Record;

class GeoJSON
{
    public static function format(Record $record, bool $encode = true)
    {
        $id = $record->id;
        $properties = $record->properties;
        $geometry = json_decode($record->geometry->out('json'));

        $feature = [
            'type'       => 'Feature',
            'id'         => $id,
            'properties' => $properties,
            'geometry'   => $geometry,
        ];

        return $encode ? json_encode($feature) : $feature;
    }
}
