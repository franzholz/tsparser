<?php
namespace JambageCom\Tsparser\TsTemplate\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;

class ConstantEditorController
{
    public function __construct(
        protected readonly ConnectionPool $connectionPool
    ) {}

    /**
     * Centralized processing logic called by the PSR-14 Event Listener
     */
    public function processFormInterception(ServerRequestInterface $request): ServerRequestInterface
    {
        $logPath = Environment::getVarPath() . '/log/tsparser_debug.txt';
        $parsedBody = $request->getParsedBody() ?? [];
        $queryParams = $request->getQueryParams() ?? [];

        $pageId = (int)($queryParams['id'] ?? $parsedBody['id'] ?? 0);

        // STAGE 1 & 2: Entry Tracing
        debug('B');
        debug("STAGE 1 & 2: Controller entered. Page ID: " . $pageId, 'tsparser');
        debug('E');

        if ($pageId > 0) {
            try {
                // 1. Fetch all templates matching the page context and the rootline inheritance
                $templateRecords = $this->getActiveTemplatesForCurrentPage($pageId);

                // Deep Tracing for the database rows
                debug('B');
                debug("DB-TRACE: Number of active rootline sys_template rows found: " . count($templateRecords), 'tsparser');
                foreach ($templateRecords as $index => $tpl) {
                    debug("TEMPLATE #" . $index . " (PID: " . $tpl['pid'] . ") -> UID: " . $tpl['uid'] . " | Title: " . $tpl['title'], 'tsparser');
                }
                debug('E');

                // 2. Extract eint+ fields strictly from the active page Typoscript
                $statistics = $this->analyzeActiveEintPlusFields($templateRecords);

                // 3. Write statistical tracking line to the text file
                $this->logStatistics($statistics, $pageId, $logPath);

                debug('B');
                debug("STAGE 3 & 4: Active Typoscript Scan completed. Fields found: " . $statistics['count'], 'tsparser');
                if (!empty($statistics['fields'])) {
                    debug("DYNAMICALLY DETECTED FIELDS: " . implode(', ', $statistics['fields']), 'tsparser');
                }
                debug('E');

                // 4. Clean request form data exclusively during a save action (_savedok = 1)
                if (isset($parsedBody['_savedok']) && !empty($statistics['fields']) && isset($parsedBody['data']) && is_array($parsedBody['data'])) {
                    $modified = false;
                    foreach ($statistics['fields'] as $field) {
                        if (isset($parsedBody['data'][$field]) && trim($parsedBody['data'][$field]) === '') {
                            $parsedBody['data'][$field] = '';
                            $modified = true;
                        }
                    }
                    if ($modified) {
                        $request = $request->withParsedBody($parsedBody);
                        debug('B');
                        debug("STAGE 5: Form data successfully cleaned. Empty entries protected.", 'tsparser');
                        debug('E');
                    }
                }
            } catch (\Throwable $e) {
                $errorMsg = sprintf("ERROR IN CONTROLLER: %s on line %d", $e->getMessage(), $e->getLine());
                @file_put_contents($logPath, sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $errorMsg), FILE_APPEND);
                debug('B');
                debug($errorMsg, 'tsparser');
                debug('E');
            }
        }

        return $request;
    }

    /**
     * Fetches all root templates and local templates actively affecting the page inheritance tree.
     */
    protected function getActiveTemplatesForCurrentPage(int $pageId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        return $queryBuilder
            ->select('uid', 'pid', 'constants', 'title', 'include_static_file')
            ->from('sys_template')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId)),
                    $queryBuilder->expr()->eq('root', $queryBuilder->createNamedParameter(1))
                ),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
            )
            ->orderBy('root', 'ASC')
            ->addOrderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Scans the active constants-text blocks for type=eint+ attributes.
     */
    protected function analyzeActiveEintPlusFields(array $templates): array
    {
        $fields = [];
        foreach ($templates as $template) {
            $constantsText = $template['constants'] ?? '';

            if (empty($constantsText)) {
                continue;
            }

            $pattern = '/#\s*cat=([^;\s\/]+)[^#]*?type\s*=\s*eint\+.*?[\r\n]+\s*([a-zA-Z0-9_-]+)\s*=/is';

            if (preg_match_all($pattern, $constantsText, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    // FIXED: Accessing the explicit capture groups indices 1 and 2 from the match array
                    $category = isset($match[1]) ? trim($match[1]) : '';
                    $variable = isset($match[2]) ? trim($match[2]) : '';
                    if ($category !== '' && $variable !== '') {
                        $fields[] = $category . '.' . $variable;
                    }
                }
            }
        }

        return [
            'fields' => array_unique($fields),
            'count' => count(array_unique($fields))
        ];
    }

    protected function logStatistics(array $statistics, int $pageId, string $logPath): void
    {
        $logContent = sprintf(
            "[%s] Statistics - Unique eint+ fields detected: %d (%s)\n",
            date('Y-m-d H:i:s'),
            $pageId,
            $statistics['count'],
            !empty($statistics['fields']) ? implode(', ', $statistics['fields']) : 'None'
        );
        @file_put_contents($logPath, $logContent, FILE_APPEND);
    }
}
