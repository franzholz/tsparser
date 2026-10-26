<?php
declare(strict_types=1);

namespace JambageCom\Tsparser\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Core\Environment;

class ConstantEditorActionSanitizerMiddleware implements MiddlewareInterface
{
    private string $logPath;

    public function __construct()
    {
        $this->logPath = Environment::getPublicPath() . '/log/tsparser.log';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Wir fangen ausschließlich POST-Requests im TypoScript-Modul-Kontext ab
        if ($request->getMethod() === 'POST' && str_contains((string)$request->getUri(), 'web_typoscript')) {

            $parsedBody = $request->getParsedBody();

            if (is_array($parsedBody) && isset($parsedBody['data'])) {
                $submittedData = $parsedBody['data'];
                $submittedCheck = $parsedBody['check'] ?? [];
                $requestModified = false;

                $timestamp = date('Y-m-d H:i:s');
                file_put_contents($this->logPath, sprintf("[%s] [MIDDLEWARE] Abfangen vor Controller-Einstieg!\n", $timestamp), FILE_APPEND);

                // Bereinigung basierend auf den flachen Keys aus dem Core-Log
                foreach ($submittedData as $key => $value) {
                    $trimmedKey = trim((string)$key);
                    $trimmedValue = trim((string)$value);

                    // Erkennung für eint- oder tt_products-Felder, die geleert oder auf 0 gesetzt wurden
                    if ((str_contains($trimmedKey, 'eint') || str_contains($trimmedKey, 'tt_products'))
                        && ($trimmedValue === '' || $trimmedValue === '0')) {

                        file_put_contents($this->logPath, sprintf("[%s] [MIDDLEWARE PURGE] Entferne Feld: %s\n", $timestamp, $trimmedKey), FILE_APPEND);

                        unset($submittedData[$key]);
                        if (isset($submittedCheck[$key])) {
                            unset($submittedCheck[$key]);
                        }
                        $requestModified = true;
                    }
                }

                if ($requestModified) {
                    $parsedBody['data'] = $submittedData;
                    $parsedBody['check'] = $submittedCheck;

                    // PSR-7-Mutation: Wir überschreiben den Request für die nachfolgende Pipeline
                    $request = $request->withParsedBody($parsedBody);

                    file_put_contents($this->logPath, sprintf("[%s] [MIDDLEWARE SUCCESS] Request erfolgreich mutiert!\n", $timestamp), FILE_APPEND);
                }
            }
        }

        // Reicht den Request an den nächsten Schritt weiter (den Controller)
        return $handler->handle($request);
    }
}
