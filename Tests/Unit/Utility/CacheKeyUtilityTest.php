<?php

declare(strict_types=1);

/*
 * Copyright (C) 2025 Daniel Siepmann <daniel.siepmann@codappix.com>
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

namespace Sinso\Variables\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sinso\Variables\Utility\CacheKeyUtility;

#[CoversClass(CacheKeyUtility::class)]
final class CacheKeyUtilityTest extends TestCase
{
    #[DataProvider('possibleCacheKeys')]
    #[Test]
    public function returnsCacheKey(string $cacheKey): void
    {
        self::assertSame('tx_variables_key_hash_3d70412c7e9ea2d96fa23d4f1f1f0a1c', CacheKeyUtility::getCacheKey($cacheKey));
    }

    /**
     * @return \Generator<array{'cacheKey': string}>
     */
    public static function possibleCacheKeys(): iterable
    {
        yield 'Simple Cache Key' => [
            'cacheKey' => 'some_key',
        ];

        yield 'Cache Key with leading white space' => [
            'cacheKey' => ' some_key',
        ];

        yield 'Cache Key with trailing white space' => [
            'cacheKey' => 'some_key ',
        ];
    }
}
