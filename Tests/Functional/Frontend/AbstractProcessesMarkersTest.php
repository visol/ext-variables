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

use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractProcessesMarkersTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/variables',
    ];

    protected $pathsToLinkInTestInstance = [
        'typo3conf/ext/variables/Tests/Functional/Fixtures/Frontend/Sites/' => 'typo3conf/sites',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('EXT:variables/Tests/Functional/Fixtures/Frontend/Content.xml');
        $this->setUpFrontendRootPage(1, [
            'EXT:variables/Tests/Functional/Fixtures/Frontend/Rendering.typoscript',
        ]);
    }

    protected function fetchContentForPage(int $pageUid): string
    {
        $request = new InternalRequest();
        $request = $request->withPageId($pageUid);

        return $this->executeFrontendRequest($request)->getBody()->__toString();
    }
}
