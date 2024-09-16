<?php
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

namespace Sinso\Variables\Domain\Model;

class Marker
{
    public function __construct(
        public int $uid,
        public string $key,
        public string $replacement,
        // public ?int $smallestValueFromTimeFields,
    )
    {
    }

    public function getMarkerWithBrackets(): string
    {
        return '{{' . $this->key . '}}';
    }
}
