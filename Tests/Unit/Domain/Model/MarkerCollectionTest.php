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

namespace Sinso\Variables\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sinso\Variables\Domain\Model\Marker;
use Sinso\Variables\Domain\Model\MarkerCollection;

#[CoversClass(MarkerCollection::class)]
final class MarkerCollectionTest extends TestCase
{
    #[Test]
    public function returnsType(): void
    {
        $subject = new MarkerCollection();

        self::assertSame('Sinso\Variables\Domain\Model\Marker', $subject->getType());
    }

    #[Test]
    public function throwsExceptionWhenRequestingUnavailableMarker(): void
    {
        $subject = new MarkerCollection();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Marker not found');
        $this->expectExceptionCode(6494338102);

        $subject->get('{{unavailable}}');
    }

    #[Test]
    public function returnsAddedMarker(): void
    {
        $marker = new Marker(0, 'key', 'replacement');

        $subject = new MarkerCollection();

        $subject->add($marker);

        self::assertSame($marker, $subject->get('{{key}}'));
    }

    #[Test]
    public function returnsMarkerKeys(): void
    {
        $subject = new MarkerCollection();

        $subject->add(new Marker(0, 'key_1', 'replacement_1'));
        $subject->add(new Marker(0, 'key_2', 'replacement_2'));

        self::assertSame([
            '{{key_1}}',
            '{{key_2}}',
        ], $subject->getMarkerKeys());
    }
}
