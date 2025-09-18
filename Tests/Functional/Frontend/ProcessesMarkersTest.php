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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Sinso\Variables\EventListener\ContentProcessor;

#[CoversClass(ContentProcessor::class)]
class ProcessesMarkersTest extends AbstractProcessesMarkersTestCase
{
    #[Test]
    public function noMarkerAppliedAsNoneExist(): void
    {
        self::assertStringContainsString(
            '<p>Some example text with marker {{MARKER1}}</p>',
            $this->fetchContentForPage(1)
        );

        $this->assertHasNoCacheKeyStartingWithPrefix('tx_variables_key_hash');
    }

    #[Test]
    public function appliesMarkersStoredOnSamePage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Frontend/Marker.csv');

        self::assertStringContainsString(
            '<p>Some example text with marker Replaced marker 1 from pid 1</p>',
            $this->fetchContentForPage(1)
        );

        $this->assertHasCacheForKey('tx_variables_key_hash_b3560bb929f682dcc19c903256f98639');
    }

    #[Test]
    public function appliesMarkersFromRootlinePage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Frontend/Marker.csv');

        self::assertStringContainsString(
            '<p>Some example text with marker Replaced marker 2 from pid 2 Replaced marker 1 from pid 1</p>',
            $this->fetchContentForPage(2)
        );

        $this->assertHasCacheForKey('tx_variables_key_hash_b3560bb929f682dcc19c903256f98639');
        $this->assertHasCacheForKey('tx_variables_key_hash_7324efb2ab7ff6e7ef0fe77210ff6b20');
    }

    #[Test]
    public function appliesMarkersFromConfiguredStoragePid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Frontend/Marker.csv');

        self::assertStringContainsString(
            '<p>Some example text with marker Replaced marker 3 from storage pid 4</p>',
            $this->fetchContentForPage(3)
        );

        $this->assertHasCacheForKey('tx_variables_key_hash_2328e4c0fcee8716480763f53e97ea82');
    }
}
