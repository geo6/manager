<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Filesystem;
use Mezzio\Helper\UrlHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FileExtension extends AbstractExtension
{
    /** @var AbstractAdapter */
    private $adapter;

    /** @var Filesystem */
    private $filesystem;

    /** @var array */
    private $routeParams;

    /** @var UrlHelper */
    private $urlHelper;

    public function __construct(UrlHelper $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('file', [$this, 'file'], ['is_safe' => ['html']]),
        ];
    }

    public function file($adapter, $path, array $routeParams, bool $preview, bool $download): string
    {
        $this->filesystem = new Filesystem($adapter);
        $this->routeParams = $routeParams;

        if (!is_null($path)) {
            $path = trim($path);
        }

        if (is_null($path) || strlen($path) === 0 || $this->filesystem->has($path) !== true) {
            $output = '<td';
            $output .= ' class="text-nowrap"';
            $output .= ' colspan="2"';
            $output .= '>';

            if (is_null($path)) {
                $output .= ValueExtension::null();
            } elseif (strlen($path) === 0) {
                $output .= '';
            } elseif ($this->filesystem->has($path) !== true) {
                $output .= self::notexists($path);
            } else {
                $output .= '123';
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

    private function notexists($path): string
    {
        return '<span class="text-muted" title="File does not exists." style="cursor: help; font-style: italic;">'
            . '<i class="fas fa-fw fa-exclamation-circle"></i>'
            . ' ' . basename($path)
            . '</span>';
    }

    private function preview($path, bool $displayFilename): string
    {
        $mime = $this->filesystem->getMimetype($path);
        $href = $this->urlHelper->generate('file.view', $this->routeParams);

        if ($mime === 'image/jpeg' || $mime === 'image/png') {
            return '<a href="' . $href . '" style="text-decoration: none;" data-toggle="overlay">'
            . '<i class="far fa-fw fa-eye"></i> '
            . ($displayFilename ? ' ' . basename($path) : '')
            . '</a>';
        } else {
            return '<a href="' . $href . '" style="text-decoration: none;" target="_blank">'
            . '<i class="far fa-fw fa-file"></i> '
            . ($displayFilename ? ' ' . basename($path) : '')
            . '</a>';
        }
    }

    private function download($path, bool $displayFilename): string
    {
        $href = $this->urlHelper->generate('file.download', $this->routeParams);

        return '<a href="' . $href . '" style="text-decoration: none;">'
            . '<i class="fas fa-fw fa-file-download"></i> '
            . ($displayFilename ? ' ' . basename($path) : '')
            . '</a>';
    }
}
