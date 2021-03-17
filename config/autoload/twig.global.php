<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories'  => [
            App\Twig\ValueExtension::class => Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory::class,
        ],
    ],
    'twig' => [
        'extensions' => [
            App\Twig\ValueExtension::class,
        ],
    ],
];
