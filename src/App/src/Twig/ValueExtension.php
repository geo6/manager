<?php

declare(strict_types=1);

namespace App\Twig;

use Doctrine\DBAL\Types\Type;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ValueExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('value', [$this, 'value'], ['is_safe' => ['html']]),
        ];
    }

    public function value($value, Type $datatype): string
    {
        $textAlign = 'start';
        if ($datatype->getName() === 'integer') {
            $textAlign = 'end';
        } elseif ($datatype->getName() === 'boolean') {
            $textAlign = 'center';
        }

        $output = '<td';
        $output .= sprintf(' class="text-nowrap text-%s"', $textAlign);
        $output .= sprintf(' data-datatype="%s"', $datatype->getName());
        $output .= '>';

        if (is_null($value)) {
            $output .= self::null();
        } elseif ($datatype->getName() === 'boolean') {
            $output .= self::boolean($value);
        } elseif ($datatype->getName() === 'geometry' || $datatype->getName() === 'geography') {
            $output .= self::geometry($value);
        }  elseif (is_string($value) && self::checkURL($value) === true) {
            $output .= self::varcharLink($value);
        } else {
            $output .= (string) $value;
        }

        $output .= '</td>';

        return $output;
    }

    public static function null(): string
    {
        return '<span class="text-muted fst-italic">NULL</span>';
    }

    private static function boolean(bool $value): string
    {
        if ($value === true) {
            return '<i class="far fa-fw fa-check-circle text-success"></i>';
        } else {
            return '<i class="far fa-fw fa-times-circle text-danger"></i>';
        }
    }

    public static function geometry(string $value): string {
        $json = json_decode($value);

        return '<i class="fas fa-pencil-ruler"></i> ' . $json->type ?? 'Unknown';
    }

    private static function varcharLink(string $value): string
    {
        $host = parse_url($value, PHP_URL_HOST);

        return '<a href="' . $value . '" target="_blank" class="text-decoration-none">'
            . '<i class="fas fa-external-link-alt"></i> '
            . $host
            . '</a>';
    }

    private static function checkURL(string $value): bool
    {
        $url = parse_url($value, PHP_URL_PATH);

        if (is_null($url)) return false;

        $encoded = array_map('urlencode', explode('/', $url));

        $url = str_replace($url, implode('/', $encoded), $value);

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
