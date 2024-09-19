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

namespace Sinso\Variables\Tests\Functional\Caching;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sinso\Variables\Hooks\DataHandler;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @covers \Sinso\Variables\Hooks\DataHandler
 */
class FlushViaDataHandlerChangesTest extends FunctionalTestCase
{
    use ProphecyTrait;

    #[Test]
    public function canBeCreated(): void
    {
        $subject = new DataHandler();

        self::assertInstanceOf(
            DataHandler::class,
            $subject
        );
    }

    #[Test]
    #[DataProvider('possibleNoneTriggeringParams')]
    public function doesNotInteractWithCacheManagerOnUnkownData(array $params): void
    {
        $cacheManager = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $subject = new DataHandler();
        $subject->clearCachePostProc($params);

        $cacheManager->flushCachesInGroupByTag('pages', Argument::type('array'))->shouldNotBeCalled();
    }

    /**
     * @return Generator<string,array{params:array}|array{params:array<string,string>}>
     */
    public static function possibleNoneTriggeringParams(): Generator
    {
        yield 'no table given' => [
            'params' => [],
        ];

        yield 'wrong table given' => [
            'params' => [
                'table' => 'tt_content',
            ],
        ];

        yield 'no uid given' => [
            'params' => [
                'table' => 'tx_variables_marker',
            ],
        ];
    }

    #[Test]
    public function flushCachesByGroupForMarker(): void
    {
        $cacheManager = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $subject = new DataHandler();
        $subject->clearCachePostProc([
            'table' => 'tx_variables_marker',
            'uid' => '1',
            'marker' => 'TEST'
        ]);

        $cacheManager->flushCachesInGroupByTag('pages', 'tx_variables_key_hash_033bd94b1168d7e4f0d644c3c95e35bf')->shouldBeCalledOnce();
    }
}
