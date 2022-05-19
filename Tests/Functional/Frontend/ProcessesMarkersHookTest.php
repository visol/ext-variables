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

use Sinso\Variables\Tests\Functional\Fixtures\Frontend\Hook\ImplementingInterface;

/**
 * @covers \Sinso\Variables\Hooks\ContentProcessor
 */
class ProcessesMarkersHookTest extends AbstractProcessesMarkersTest
{
    protected $configurationToUseInTestInstance = [
        'EXTCONF' => [
            'variables' => [
                'postProcessMarkers' => [
                   ImplementingInterface::class,
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function executesHookClass(): void
    {
        $this->importDataSet('EXT:variables/Tests/Functional/Fixtures/Frontend/Marker.xml');

        self::assertStringContainsString(
            '<p>Some example text with marker Modified by hook</p>',
            $this->fetchContentForPage(1)
        );

        $pageCache = $this->getAllRecords('cache_pages_tags');
        self::assertCount(2, $pageCache);
        self::assertSame('tx_variables_uid_1', $pageCache[0]['tag']);
    }
}
