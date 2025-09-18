<?php

declare(strict_types=1);

/*
 * Copyright (C) 2025 Daniel Siepmann <coding@daniel-siepmann.de>
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

namespace Sinso\Variables\Tests\Functional\Caching;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sinso\Variables\EventListener\ModifyCacheLifetime;
use Sinso\Variables\Hooks\DataHandler;
use Sinso\Variables\Service\VariablesService;
use Sinso\Variables\Tests\Functional\Frontend\AbstractProcessesMarkersTestCase;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler as Typo3DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

#[CoversClass(ModifyCacheLifetime::class)]
#[CoversClass(VariablesService::class)]
class AdjustsPageCacheLifetimeTest extends AbstractProcessesMarkersTestCase
{
    /**
     * @param array<string, string> $availableVariable
     */
    #[DataProvider('possibleVariableLifeTimeCombinations')]
    #[Test]
    public function cacheLifetimeOfPageIsSetAsExpected(
        DateTimeImmutable $expectedTimeStamp,
        array $availableVariable,
    ): void {
        if ($availableVariable !== []) {
            $this->insertMarkerWithData($availableVariable);
        }

        $this->fetchContentForPage(1);

        $this->assertCacheTimeSampMatches($expectedTimeStamp);
    }

    /**
     * @return \Generator
     */
    public static function possibleVariableLifeTimeCombinations(): iterable
    {
        yield 'No Variable Exists' => [
            'expectedTimeStamp' => (new DateTimeImmutable())->modify('+1 day'),
            'availableVariable' => [],
        ];
        yield 'Only Variable is deleted' => [
            'expectedTimeStamp' => (new DateTimeImmutable())->modify('+1 day'),
            'availableVariable' => [
                'deleted' => '1',
            ],
        ];
        yield 'Variable StartTime more in future than default life time' => [
            'expectedTimeStamp' => (new DateTimeImmutable())->modify('+1 day'),
            'availableVariable' => [
                'starttime' => (new DateTimeImmutable())->modify('+2 day')->format('U'),
            ],
        ];
        yield 'Variable EndTime more in future than default life time' => [
            'expectedTimeStamp' => (new DateTimeImmutable())->modify('+1 day'),
            'availableVariable' => [
                'endtime' => (new DateTimeImmutable())->modify('+2 day')->format('U'),
            ],
        ];
        // Currently broken.
        // yield 'Variable StartTime nearer than default life time' => [
        //     'expectedTimeStamp' => (new DateTimeImmutable())->modify('+1 hour'),
        //     'availableVariable' => [
        //         'starttime' => (new DateTimeImmutable())->modify('+1 hour')->format('U'),
        //     ],
        // ];
        yield 'Variable EndTime nearer than default life time' => [
            'expectedTimeStamp' => (new DateTimeImmutable())->modify('+1 hour'),
            'availableVariable' => [
                'endtime' => (new DateTimeImmutable())->modify('+1 hour')->format('U'),
            ],
        ];
    }

    private function assertCacheTimeSampMatches(\DateTimeImmutable $dateTime): void
    {
        $cacheEntries = $this->getConnectionPool()
            ->getConnectionForTable('cache_pages')
            ->select(['expires'], 'cache_pages')
            ->fetchAllAssociative();

        self::assertCount(1, $cacheEntries);

        $actualTimeStamp = $cacheEntries[0]['expires'];

        $minimumTimeStamp = $dateTime->modify('-5 seconds')->format('U');
        $maximumTimeStamp = $dateTime->modify('+5 seconds')->format('U');

        self::assertGreaterThanOrEqual($minimumTimeStamp, $actualTimeStamp);
        self::assertLessThanOrEqual($maximumTimeStamp, $actualTimeStamp);
    }

    /**
     * @param array<string, string> $data
     */
    private function insertMarkerWithData(array $data): void
    {
        $this->getConnectionPool()->getConnectionForTable('tx_variables_marker')
            ->insert('tx_variables_marker', array_merge([
                'pid' => '1',
                'marker' => 'MARKER1',
                'replacement' => 'Replaced marker 1',
            ], $data));
    }
}
