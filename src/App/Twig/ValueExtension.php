<?php

declare(strict_types=1);

namespace App\Twig;

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

    public function value($value, ?string $datatype = null): string
    {
        $textAlign = 'left';
        if ($datatype === 'integer') {
            $textAlign = 'right';
        } elseif ($datatype === 'boolean') {
            $textAlign = 'center';
        }

        $output = '<td';
        $output .= sprintf(' class="text-nowrap text-%s"', $textAlign);
        $output .= sprintf(' data-datatype="%s"', (string) $datatype);
        $output .= '>';

        if (is_null($value)) {
            $output .= self::null();
        } elseif ($datatype === 'boolean') {
            $output .= self::boolean($value);
        } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false) {
            $output .= self::varcharLink($value);
        } else {
            $output .= (string) $value;
        }

        $output .= '</td>';

        return $output;
    }

    public static function null(): string
    {
        return '<span class="text-muted font-italic">NULL</span>';
    }

    private static function boolean(bool $value): string
    {
        if ($value === true) {
            return '<i class="far fa-fw fa-check-circle text-success"></i>';
        } else {
            return '<i class="far fa-fw fa-times-circle text-danger"></i>';
        }
    }

    private static function varcharLink(string $value): string
    {
        $host = parse_url($value, PHP_URL_HOST);

        return '<a href="' . $value . '" target="_blank" style="text-decoration: none;">'
            . '<i class="fas fa-external-link-alt"></i> '
            . $host
            . '</a>';
    }
}
