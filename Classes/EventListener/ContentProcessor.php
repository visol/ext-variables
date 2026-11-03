<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Sinso\Variables\EventListener;

use Sinso\Variables\Service\VariablesService;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

class ContentProcessor
{
    public function __construct(
        private readonly VariablesService $variablesService,
    ) {
    }

    #[AsEventListener()]
    public function __invoke(AfterCacheableContentIsGeneratedEvent $event): void
    {
        // This listener supports v13 and v14.
        // Remove once TYPO3 v13 support gets dropped
        $version = new Typo3Version();
        $useExplicitContent = $version->getMajorVersion() >= 14;

        // PHPStan sees only the installed Core version; these APIs differ between v13 and v14.
        if ($useExplicitContent) {
            $content = $event->getContent(); // @phpstan-ignore-line
        } else {
            $content = &$event->getController()->content; // @phpstan-ignore-line
        }

        $this->variablesService->replaceMarkersInStructureAndAdjustCaching(
            $event->getRequest(),
            $content,
        );

        if ($useExplicitContent) {
            $event->setContent($content); // @phpstan-ignore-line
        }
    }
}
