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
use Sinso\Variables\Domain\Model\Marker;

#[CoversClass(Marker::class)]
final class MarkerTest extends TestCase
{
    #[Test]
    public function exposesUid(): void
    {
        $subject = new Marker(10, '', '');

        self::assertSame(10, $subject->uid);
    }

    #[Test]
    public function exposesKey(): void
    {
        $subject = new Marker(0, 'key', '');

        self::assertSame('key', $subject->key);
    }

    #[Test]
    public function exposesReplacement(): void
    {
        $subject = new Marker(0, '', 'replacement');

        self::assertSame('replacement', $subject->replacement);
    }

    #[Test]
    public function returnsMarkerWithBrackets(): void
    {
        $subject = new Marker(0, 'key', '');

        self::assertSame('{{key}}', $subject->getMarkerWithBrackets());
    }
}
