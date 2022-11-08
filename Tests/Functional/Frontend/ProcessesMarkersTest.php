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

/**
 * @covers \Sinso\Variables\Hooks\ContentProcessor
 */
class ProcessesMarkersTest extends AbstractProcessesMarkersTest
{
    /**
     * @test
     */
    public function noMarkerAppliedAsNoneExist(): void
    {
        self::assertStringContainsString(
            '<p>Some example text with marker {{MARKER1}}</p>',
            $this->fetchContentForPage(1)
        );

        $pageCache = $this->getAllRecords('cache_pages_tags');
        self::assertCount(1, $pageCache);
    }

    /**
     * @test
     */
    public function appliesMarkersStoredOnSamePage(): void
    {
        $this->importDataSet('EXT:variables/Tests/Functional/Fixtures/Frontend/Marker.xml');

        self::assertStringContainsString(
            '<p>Some example text with marker Replaced marker 1 from pid 1</p>',
            $this->fetchContentForPage(1)
        );

        $pageCache = $this->getAllRecords('cache_pages_tags');
        self::assertCount(2, $pageCache);
        self::assertSame('tx_variables_uid_1', $pageCache[0]['tag']);
    }

    /**
     * @test
     */
    public function appliesMarkersFromRootlinePage(): void
    {
        $this->importDataSet('EXT:variables/Tests/Functional/Fixtures/Frontend/Marker.xml');

        self::assertStringContainsString(
            '<p>Some example text with marker Replaced marker 2 from pid 2 Replaced marker 1 from pid 1</p>',
            $this->fetchContentForPage(2)
        );

        $pageCache = $this->getAllRecords('cache_pages_tags');
        self::assertCount(3, $pageCache);
        self::assertSame('tx_variables_uid_1', $pageCache[0]['tag']);
        self::assertSame('tx_variables_uid_2', $pageCache[1]['tag']);
    }

    /**
     * @test
     */
    public function appliesMarkersFromConfiguredStoragePid(): void
    {
        $this->importDataSet('EXT:variables/Tests/Functional/Fixtures/Frontend/Marker.xml');

        self::assertStringContainsString(
            '<p>Some example text with marker Replaced marker 3 from storage pid 4</p>',
            $this->fetchContentForPage(3)
        );

        $pageCache = $this->getAllRecords('cache_pages_tags');
        self::assertCount(2, $pageCache);
        self::assertSame('tx_variables_uid_3', $pageCache[0]['tag']);
    }
}
