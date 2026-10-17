<?php

declare(strict_types=1);

namespace JambageCom\Tsparser\TypoScript\AST\Visitor;

/*
 * This file is part of the TYPO3 CMS project.
 * Licensed under the GNU General Public License v2 or later.
 */

use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\Node\MetadataNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstVisitorInterface;

/**
 * Custom Visitor to inject 'eint+' support into the TypoScript AST without inheritance
 */
class CustomConstantTypeVisitor implements AstVisitorInterface
{
    /**
     * Exact TYPO3 v14 compatible signature for visitBeforeChildren
     */
    public function visitBeforeChildren(
        RootNode $rootNode,
        NodeInterface $node,
        CurrentObjectPath $currentObjectPath,
        int $currentDepth
    ): void {
        // We look for metadata nodes which hold comments and type definitions
        if ($node instanceof MetadataNode && $node->hasAttribute('type')) {
            $comment = $node->getComment();

            if ($comment !== null) {
                // Regex checks flexibly for "type=eint+" or "type = eint+" inside the comment block
                if (preg_match('/type\s*=\s*eint\+/', $comment)) {
                    $node->setAttribute('type', 'eint+');
                }
            }
        }
    }

    public function visit(
        RootNode $rootNode,
        NodeInterface $node,
        CurrentObjectPath $currentObjectPath,
        int $currentDepth
    ): void {
        // Not used
    }

    public function visitAfterChildren(
        RootNode $rootNode,
        NodeInterface $node,
        CurrentObjectPath $currentObjectPath,
        int $currentDepth
    ): void {
        // Not used
    }
}
