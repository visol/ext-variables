<?php

declare(strict_types=1);

namespace Sinso\Variables\EventListener;

use Sinso\Variables\Service\VariablesService;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\ModifyCacheLifetimeForPageEvent;

final class ModifyCacheLifetime
{
    public function __construct(
        private readonly VariablesService $variablesService,
    ) {
    }

    /**
     * Calculate shortest lifetime (aka duration) respecting data from
     * markers
     */
    #[AsEventListener()]
    public function __invoke(ModifyCacheLifetimeForPageEvent $event): void
    {
        $event->setCacheLifetime(
            min(
                $event->getCacheLifetime(),
                $this->variablesService->getLifetime(),
            )
        );
    }
}
