<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FileExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
        new TwigFunction('file', [$this, 'file'], ['is_safe' => ['html']]),
        ];
    }

    public function file($path, bool $preview, bool $download): string
    {
        if (!is_null($path)) {
            $path = trim($path);
        }

        if (is_null($path) || strlen($path) === 0 || !file_exists($path)) {
            $output = '<td';
            $output .= ' class="text-nowrap"';
            $output .= ' colspan="2"';
            $output .= '>';

            if (is_null($path)) {
                $output .= ValueExtension::null();
            } elseif (strlen($path) === 0) {
                $output .= '';
            } elseif (!file_exists($path)) {
                $output .= self::notexists($path);
            }

            $output .= '</td>';
        } else {
            $output = '';

            if ($preview === true) {
                $output = '<td ';
                $output .= ' class="text-nowrap"';
                $output .= $download !== true ? ' colspan="2"' : '';
                $output .= '>';
                $output .= self::preview($path, true);
                $output .= '</td>';
            }

            if ($download === true) {
                $output .= '<td ';
                $output .= ' class="text-nowrap"';
                $output .= $preview !== true ? ' colspan="2"' : '';
                $output .= '>';
                $output .= self::download($path, $preview && $download ? false : true);
                $output .= '</td>';
            }
        }

        return $output;
    }

    private function notexists($path) : string
    {
        return '<span class="text-muted" title="File does not exists." style="cursor: help; font-style: italic;">'
            .'<i class="fas fa-fw fa-exclamation-circle"></i>'
            .' '.basename($path)
            .'</span>';
    }

    private function preview($path, bool $displayFilename) : string
    {
        return '<a href="#" style="text-decoration: none;">'
            .'<i class="far fa-fw fa-eye"></i> '
            .($displayFilename ? ' '.basename($path) : '')
            .'</a>';
    }

    private function download($path, bool $displayFilename) : string
    {
        return '<a href="#" style="text-decoration: none;">'
            .'<i class="fas fa-fw fa-file-download"></i> '
            .($displayFilename ? ' '.basename($path) : '')
            .'</a>';
    }
}
