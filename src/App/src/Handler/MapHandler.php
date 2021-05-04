<?php

declare(strict_types=1);

namespace App\Handler;

use API\Middleware\DatabaseMiddleware;
use API\Middleware\TableMiddleware;
use Doctrine\DBAL\Schema\Column;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MapHandler implements RequestHandlerInterface
{
    /**
     * @var TemplateRendererInterface
     */
    private $renderer;

    public function __construct(TemplateRendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
/** @var Connection */ $connection = $request->getAttribute(DatabaseMiddleware::CONNECTION_ATTRIBUTE);

/** @var Table */ $table = $request->getAttribute(TableMiddleware::TABLE_ATTRIBUTE);
/** @var string */ $primaryKey = $request->getAttribute(TableMiddleware::PRIMARYKEY_ATTRIBUTE);
/** @var array */ $readonlyColumns = $request->getAttribute(TableMiddleware::READONLY_ATTRIBUTE);

        return new HtmlResponse($this->renderer->render(
            'app::map',
            [
                'table'       => $table,
                'readonly'    => [
                    $primaryKey,
                    ...$readonlyColumns,
                    ...array_values(array_map(
                        function (Column $column) {
                            return $column->getName();
                        },
                        array_filter($table->getColumns(), function (Column $column) {
                            return in_array($column->getType()->getName(), ['geometry', 'geography']);
                        })
                    )),
                ],
            ]
        ));
    }
}
