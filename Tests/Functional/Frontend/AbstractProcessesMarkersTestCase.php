<?php

declare(strict_types=1);

/*
 * Copyright (C) 2022 Daniel Siepmann <coding@daniel-siepmann.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

namespace Sinso\Variables\Tests\Functional\Frontend;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractProcessesMarkersTestCase extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/variables',
    ];

    protected array $pathsToLinkInTestInstance = [
        'typo3conf/ext/variables/Tests/Functional/Fixtures/Frontend/Sites/' => 'typo3conf/sites',
    ];

    protected function setUp(): void
    {
        ArrayUtility::mergeRecursiveWithOverrule($this->configurationToUseInTestInstance, [
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        'pages' => [
                            'backend' => Typo3DatabaseBackend::class,
                        ],
                    ],
                ],
            ],
        ]);

        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Frontend/Content.csv');
        $this->setUpFrontendRootPage(1, [
            'EXT:variables/Tests/Functional/Fixtures/Frontend/Rendering.typoscript',
        ]);
    }

    protected function fetchContentForPage(int $pageUid): string
    {
        $request = new InternalRequest();
        $request = $request->withPageId($pageUid);

        return $this->executeFrontendSubRequest($request)->getBody()->__toString();
    }

    protected function assertHasCacheForKey(string $cacheKey): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('cache_pages_tags');
        $queryBuilder->count('*');
        $queryBuilder->from('cache_pages_tags');
        $queryBuilder->where($queryBuilder->expr()->eq('tag', $queryBuilder->createNamedParameter($cacheKey)));

        $count = $queryBuilder->executeQuery()->fetchOne();
        self::assertSame(1, $count, 'Did not find a single cache entry for key: ' . $cacheKey);
    }

    protected function assertHasNoCacheKeyStartingWithPrefix(string $cacheKeyPrefix): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('cache_pages_tags');
        $queryBuilder->count('*');
        $queryBuilder->from('cache_pages_tags');
        $queryBuilder->where($queryBuilder->expr()->like('tag', $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($cacheKeyPrefix))));

        $count = $queryBuilder->executeQuery()->fetchOne();
        self::assertSame(0, $count, 'Did find a cache entries for key prefix: ' . $cacheKeyPrefix);
    }
}
