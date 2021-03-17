<?php

namespace API\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class GeographyType extends Type
{
    const TYPE = 'geography';

    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return self::TYPE;
    }

    public function getName()
    {
        return self::TYPE;
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return sprintf('ST_GeomFromGeoJSON(%s)::geography', $sqlExpr);
    }

    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return sprintf('ST_AsGeoJSON(%s)', $sqlExpr);
    }
}
