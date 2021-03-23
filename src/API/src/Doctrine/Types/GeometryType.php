<?php

namespace API\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class GeometryType extends Type
{
    const TYPE = 'geometry';

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return self::TYPE;
    }

    public function getName()
    {
        return self::TYPE;
    }

    public function canRequireSQLConversion()
    {
        return true;
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return sprintf('ST_GeomFromGeoJSON(%s)', $sqlExpr);
    }

    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return sprintf('ST_AsGeoJSON(%s)', $sqlExpr);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return json_decode($value);
    }
}
