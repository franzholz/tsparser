<?php
namespace JambageCom\Tsparser\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use JambageCom\Tsparser\TsTemplate\Controller\ConstantEditorController;

class ConstantEditorInterceptor implements MiddlewareInterface
{
    public function __construct(
        protected readonly ConstantEditorController $customController
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Route the request through your fully-featured custom controller
        $request = $this->customController->processFormInterception($request);

        return $handler->handle($request);
    }
}
