<?php

declare(strict_types=1);

namespace API\Handler;

use API\Middleware\DatabaseMiddleware;
use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Exception;
use Intervention\Image\ImageManagerStatic;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FileHandler implements RequestHandlerInterface
{
    const DIRECTORY = 'data/file';
    const CACHE_DIRECTORY = 'data/cache/file';
    const THUMBNAIL_DIRECTORY = 'data/cache/file/thumbnails';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Connection */
        $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
        /** @var string[] */
        $fileColumns = $request->getAttribute(TableMiddleware::FILE_ATTRIBUTE);

        /** @var int */
        $id = $request->getAttribute('id');
        /** @var string */
        $column = $request->getAttribute('column');
        /** @var string */
        $action = $request->getAttribute('action');

        try {
            if (in_array($column, $fileColumns, true) !== true) {
                throw new Exception(sprintf('No preview possible for column "%s" in table "%s".', $column, $table->getName()), 400);
            }

            $query = $connection->createQueryBuilder();
            $query->select([$column])->from($table->getName(), 'a');
            $query->where('id = ?')->setParameter(0, $id);

            $stmt = $query->executeQuery();
            $path = $stmt->fetchOne();

            if ($path === false) {
                throw new Exception(sprintf('No record #%d in table "%s".', $id, $table->getName()), 404);
            }

            $realpath = sprintf('%s/%s', self::DIRECTORY, $path);
            if (!file_exists($realpath) || !is_readable($realpath)) {
                throw new Exception(sprintf('Path "%s" does not exist or is not readable.', $path), 404);
            }

            $mime = mime_content_type($realpath);
            if ($mime === false || preg_match('/^image\/.+$/', $mime) !== 1) {
                throw new Exception(sprintf('Thumbnail is only available for images, "%s" is "%s".', $path, $mime ?: 'unknown'), 501);
            }

            switch ($action) {
                case 'info': {
                        return new JsonResponse(self::info($realpath));
                    }
                case 'thumbnail': {
                        $stream = new Stream(self::thumbnail($realpath));

                        return (new Response())
                            ->withBody($stream)
                            ->withStatus(200)
                            ->withHeader('Content-Length', (string) $stream->getSize())
                            ->withHeader('Content-Type', $mime);
                    }
                default: {
                        return new EmptyResponse(400);
                    }
            }
        } catch (Exception $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : 500;
            return new TextResponse($e->getMessage(), $code);
        }
    }

    private static function info(string $path): array
    {
        setlocale(LC_ALL, 'C.UTF-8'); // Required for path containing special characters

        $fname = basename($path);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $info = sprintf('%s/%s.info', self::CACHE_DIRECTORY, $filename);

        if (!file_exists($info) || !is_readable($info) || self::checkMD5($path) !== true) {
            $data = [
                'path'     => $path,
                'filename' => basename($path),
            ];

            if (file_exists($path)) {
                $mime = mime_content_type($path);

                $data['mime'] = $mime;

                if ($mime !== false && preg_match('/^image\/.+$/', $mime) === 1) {
                    $exif = @exif_read_data($path, 'ANY_TAG', true);

                    $data['exif'] = $exif ?: null;

                    if (!is_null($data['exif'])) {
                        foreach ($data['exif'] as &$section) {
                            $section = array_filter($section, function ($key): bool {
                                $keep =
                                    preg_match('/^UndefinedTag:/', $key) !== 1 &&
                                    !in_array($key, ['UserComment'], true);

                                return $keep;
                            }, ARRAY_FILTER_USE_KEY);
                        }
                    }
                }
            } else {
                $data['mime'] = null;
            }

            file_put_contents($info, json_encode($data));
        } else {
            $data = json_decode(file_get_contents($info), true);
        }

        return $data;
    }

    private static function thumbnail(string $path): string
    {
        setlocale(LC_ALL, 'C.UTF-8'); // Required for path containing special characters

        $fname = basename($path);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $thumbnail = sprintf('%s/%s', self::THUMBNAIL_DIRECTORY, $fname);

        if (!file_exists($thumbnail) || !is_readable($thumbnail) || self::checkMD5($path) !== true) {
            $image = ImageManagerStatic::make($path);
            $image->orientate();

            if ($image->height() > $image->width()) {
                $image->heighten(640, function ($constraint): void {
                    $constraint->upsize();
                });
            } else {
                $image->widen(640, function ($constraint): void {
                    $constraint->upsize();
                });
            }

            if (!file_exists(dirname($thumbnail)) || !is_dir(dirname($thumbnail))) {
                mkdir(dirname($thumbnail), 0777, true);
            }

            $image->save($thumbnail);

            $md5 = sprintf('%s/%s.md5', self::CACHE_DIRECTORY, $filename);
            file_put_contents($md5, md5_file($path));
        }

        return $thumbnail;
    }

    private static function checkMD5(string $path): bool
    {
        $fname = pathinfo($path, PATHINFO_FILENAME);

        $md5 = sprintf('%s/%s.md5', self::CACHE_DIRECTORY, $fname);

        if (!file_exists($md5)) {
            return false;
        }

        return md5_file($path) === file_get_contents($md5);
    }
}
