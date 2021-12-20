<?php

namespace Sinso\Variables\Utility;

class CacheKeyUtility
{
    public const KEYNAME = 'tx_variables_key_hash_';

    public static function getCacheKey(string $key): string
    {
        return self::KEYNAME . md5(trim($key));
    }
}
