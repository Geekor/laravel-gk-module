<?php

namespace Geekor\Module;

/**
 * use Geekor\Module\Consts;
 *
 * Consts::tr('xxx');
 *
 */
class Consts
{
    public const LANG_NAMESPACE = 'geekor-module';

    //========================================

    public static function tr($key = null, $replace = [], $locale = null)
    {
        return trans(self::LANG_NAMESPACE . '::' . $key, $replace, $locale);
    }
}
