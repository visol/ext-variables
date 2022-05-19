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

use Sinso\Variables\Tests\Functional\Fixtures\Frontend\Hook\NotImplementingInterface;

/**
 * @covers \Sinso\Variables\Hooks\ContentProcessor
 */
class BreaksOnInvalidHookTest extends AbstractProcessesMarkersTest
{
    protected $configurationToUseInTestInstance = [
        'EXTCONF' => [
            'variables' => [
                'postProcessMarkers' => [
                    NotImplementingInterface::class,
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function throwsExceptionIfConfiguredClassDoesntImplementInterface(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sinso\Variables\Tests\Functional\Fixtures\Frontend\Hook\NotImplementingInterface does not implement Sinso\Variables\Hooks\MarkersProcessorInterface');
        $this->expectExceptionCode(1512391205);
        $this->fetchContentForPage(1);
    }
}
