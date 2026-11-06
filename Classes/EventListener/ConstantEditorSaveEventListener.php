<?php

declare(strict_types=1);

namespace JambageCom\Tsparser\EventListener;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\DataHandling\Event\BeforeProcessDatamapEvent;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Log\LogManager;

class ConstantEditorSaveEventListener
{
    private LoggerInterface $logger;

    public function __construct(LogManager $logManager)
    {
        $this->logger = $logManager->getLogger(__CLASS__);
    }

    public function __invoke(BeforeProcessDatamapEvent $event): void
    {
        // 1. Establish the local log file target path
        $logFile = (class_exists(\TYPO3\CMS\Core\Core\Environment::class) ? \TYPO3\CMS\Core\Core\Environment::getProjectPath() : dirname(__DIR__, 4)) . '/log/tsparse.log';

        // 2. Fetch the raw incoming server request states safely
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $parsedBody = $request instanceof ServerRequestInterface ? $request->getParsedBody() : [];
        $datamap = $event->getDatamap();

        // 3. Construct a clear diagnostic text block containing the exact payload structures
        $logEntry = sprintf(
            "========================================================================\n" .
            "TIMESTAMP: %s\n" .
            "REQUEST METHOD: %s\n" .
            "--- RAW POST BODY DATA --- \n%s\n" .
            "--- RAW POST BODY CHECK --- \n%s\n" .
            "--- DATAHANDLER DATAMAP STATE IN EVENT --- \n%s\n" .
            "========================================================================\n",
            date('Y-m-d H:i:s'),
            $request instanceof ServerRequestInterface ? $request->getMethod() : 'UNKNOWN',
            print_r($parsedBody['data'] ?? [], true),
            print_r($parsedBody['check'] ?? [], true),
            print_r($datamap['sys_template'] ?? [], true)
        );

        // 4. Force write to the local log/tsparse.log file structure directly
        @file_put_contents($logFile, $logEntry, FILE_APPEND);

        // Keep the event execution pipeline unhindered for analysis
        $event->setDatamap($datamap);
    }
}
