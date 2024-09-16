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

use Ramsey\Collection\AbstractArray;

class MarkerCollection extends AbstractArray
{
    public function getType(): string
    {
        return Marker::class;
    }

    public function add(Marker $marker): void
    {
        $this[$marker->key] = $marker;
    }

    /**
     * @throws \RuntimeException
     */
    public function get(string $markerKey): Marker
    {
        if (!isset($this[$markerKey])) {
            throw new \RuntimeException('Marker not found');
        }

        return $this[$markerKey];
    }

    public function getMarkerKeys(): array
    {
        return array_keys($this->data);
    }
}
