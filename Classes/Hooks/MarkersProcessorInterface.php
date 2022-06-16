<?php

/*
 * This file is part of the Sinso/Variables project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

namespace Sinso\Variables\Hooks;

interface MarkersProcessorInterface
{

    function postProcessMarkers(array &$markers);

}
