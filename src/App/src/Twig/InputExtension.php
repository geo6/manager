<?php

declare(strict_types=1);

namespace App\Twig;

use Doctrine\DBAL\Types\Type;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InputExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('input', [$this, 'input'], ['is_safe' => ['html']]),
        ];
    }

    public function input(string $name, $value, Type $datatype): string
    {
        switch ($datatype->getName()) {
            case 'integer':
                return self::integer($name, $value);
            case 'text':
                return self::text($name, $value);
                break;
            case 'varchar':
            default:
            return self::varchar($name, $value);
                break;
        }
    }

    private static function integer(string $name, $value = ''): string
    {
        return sprintf('<input type="number" step="1" class="form-control" id="form-input-%s" name="%s" value="%s">', $name, $name, $value);
    }

    private static function text(string $name, $value = ''): string
    {
        return sprintf('<textarea class="form-control" id="form-input-%s" name="%s">%s</textarea>', $name, $name, $value);
    }

    private static function varchar(string $name, $value = ''): string
    {
        return sprintf('<input type="text" class="form-control" id="form-input-%s" name="%s" value="%s">', $name, $name, $value);
    }
}
