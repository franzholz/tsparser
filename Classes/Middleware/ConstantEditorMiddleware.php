<?php
namespace JambageCom\Tsparser\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use JambageCom\Tsparser\TsTemplate\Controller\ConstantEditorController;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConstantEditorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var ConstantEditorController $controller */
        $controller = GeneralUtility::makeInstance(ConstantEditorController::class);

        // Intercept and potentially modify the request before passing it down the chain
        $request = $controller->processFormInterception($request);

        return $handler->handle($request);
    }
}
