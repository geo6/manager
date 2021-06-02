<?php

declare(strict_types=1);

namespace API\Handler\File;

use API\Middleware\DatabaseMiddleware;
use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Exception;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UploadHandler implements RequestHandlerInterface
{
    const DIRECTORY = 'data/file';
    const CACHE_DIRECTORY = 'data/cache/upload';

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        switch ($request->getMethod()) {
            // Revert upload
            case 'DELETE':
                return self::delete($request);

            // Get uploaded file
            case 'GET':
                return self::get($request);

            // Upload file
            case 'POST':
                return self::post($request);
        }
    }

    private static function delete(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getBody()->getContents();

        try {
            if (is_null($id) || strlen($id) === 0) {
                throw new Exception('Missing "id".', 400);
            }

            $directory = sprintf('%s/%s', self::CACHE_DIRECTORY, $id);

            self::rmdir($directory);

            return new EmptyResponse();
        } catch (Exception $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : 500;

            return new TextResponse($e->getMessage(), $code);
        }
    }

    private static function get(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Connection */
        $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

        /** @var Table */
        $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
        /** @var string[] */
        $fileColumns = $request->getAttribute(TableMiddleware::FILE_ATTRIBUTE);

        $id = $request->getAttribute('id');
        $column = $request->getAttribute('column');

        try {
            if (is_null($id) || is_null($column)) {
                throw new Exception('Missing "id" or "column" parameter.', 400);
            }

            if (in_array($column, array_keys($fileColumns), true) !== true) {
                throw new Exception(sprintf('No file upload possible for column "%s" in table "%s".', $column, $table->getName()), 400);
            }

            $query = $connection->createQueryBuilder();
            $query->select([$column])->from($table->getName(), 'a');
            $query->where('id = ?')->setParameter(0, $id);

            $stmt = $query->executeQuery();
            $path = $stmt->fetchOne();

            if ($path === false) {
                throw new Exception(sprintf('No record #%d in table "%s".', $id, $table->getName()), 404);
            }

            $realpath = sprintf('%s/%s/%s', self::DIRECTORY, $id, $path);
            if (!file_exists($realpath) || !is_readable($realpath)) {
                throw new Exception(sprintf('Path "%s" does not exist or is not readable.', $path), 404);
            }

            $mime = mime_content_type($realpath);
            if ($mime === false) {
                throw new Exception('Unknow mime type.');
            }

            $stream = new Stream($realpath);

            return (new Response())
                ->withBody($stream)
                ->withStatus(200)
                ->withHeader('Content-Disposition', sprintf('inline; filename="%s"', basename($realpath)))
                ->withHeader('Content-Length', (string) $stream->getSize())
                ->withHeader('Content-Type', $mime);
        } catch (Exception $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : 500;

            return new TextResponse($e->getMessage(), $code);
        }
    }

    private static function post(ServerRequestInterface $request): ResponseInterface
    {
        /** @var string[] */
        $fileColumns = $request->getAttribute(TableMiddleware::FILE_ATTRIBUTE);

        /** @var UploadedFileInterface[] */
        $files = $request->getUploadedFiles();
        $body = $request->getParsedBody();

        try {
            foreach (array_keys($fileColumns) as $column) {
                if (isset($files[$column])) {
                    if ($files[$column]->getError() !== UPLOAD_ERR_OK) {
                        throw new Exception(sprintf('Upload error for column "%s"', $column), 500);
                    }

                    $metadata = is_string($body[$column]) ? json_decode($body[$column], true) : [];

                    if (!isset($metadata['id'])) {
                        throw new Exception(sprintf('Missing metadata "id" for column "%s"', $column), 400);
                    }

                    $uniqid = self::uniqid($metadata['id']);

                    $directory = sprintf('%s/%s', self::CACHE_DIRECTORY, $uniqid);
                    if (!file_exists($directory) || !is_dir($directory)) {
                        mkdir($directory, 0777, true);
                    }

                    $files[$column]->moveTo(sprintf('%s/%s', $directory, $files[$column]->getClientFilename()));

                    return new TextResponse($uniqid, 201);
                }
            }

            throw new Exception(sprintf('Invalid column(s): %s', implode(', ', array_keys($files))));
        } catch (Exception $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : 500;

            return new TextResponse($e->getMessage(), $code);
        }
    }

    private static function uniqid(string $id): string
    {
        return uniqid("$id.", true);
    }

    private static function rmdir(string $path): void
    {
        if (!file_exists($path) || !is_dir($path) || !is_readable($path)) {
            throw new Exception(sprintf('Path "%s" does not exist or is not readable.', $path), 404);
        }

        $glob = glob(sprintf('%s/{.[!.],}*', $path), GLOB_BRACE);
        foreach ($glob as $g) {
            if (is_file($g)) {
                unlink($g);
            } else if (is_dir($g)) {
                self::rmdir($g);
            }
        }

        rmdir($path);
    }
}
