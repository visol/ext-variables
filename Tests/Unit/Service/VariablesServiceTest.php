<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Sinso\Variables\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sinso\Variables\Domain\Model\Marker;
use Sinso\Variables\Domain\Model\MarkerCollection;
use Sinso\Variables\Service\VariablesService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;

#[CoversClass(VariablesService::class)]
final class VariablesServiceTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function markerKeyDataProvider(): iterable
    {
        yield 'plain key' => ['key', 'replacement', 'before {{key}} after', 'before replacement after'];
        yield 'key containing a slash' => ['foo/bar', 'replacement', 'before {{foo/bar}} after', 'before replacement after'];
        yield 'key containing a regular expression special character' => ['foo.bar', 'replacement', 'before {{foo.bar}} after', 'before replacement after'];
    }

    #[Test]
    #[DataProvider('markerKeyDataProvider')]
    public function replacesMarkerRegardlessOfSpecialCharactersInKey(
        string $markerKey,
        string $replacement,
        string $text,
        string $expected,
    ): void {
        $markerCollection = new MarkerCollection();
        $markerCollection->add(new Marker(1, $markerKey, $replacement));

        $this->replaceMarkersInStructure($markerCollection, $text);

        self::assertSame($expected, $text);
    }

    private function replaceMarkersInStructure(MarkerCollection $markerCollection, mixed &$structure): void
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn(false);

        $subject = new VariablesService(
            $extensionConfiguration,
            $this->createMock(Context::class),
            $this->createMock(ConnectionPool::class),
        );

        $reflectionMethod = new \ReflectionMethod($subject, 'replaceMarkersInStructure');
        $reflectionMethod->invokeArgs($subject, [$markerCollection, &$structure]);
    }
}
