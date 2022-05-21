<?php

namespace Geekor\Module\Support;

class GkStub
{
    // public static function render($file, $variables = [], $base = null)
    // {
    //     if (null === $base) {
    //         $base = __DIR__.'/../../resources/stub';
    //     }
    //     $content = file_get_contents("$base/$file.stub");
    //     $variables = array_build($variables, function ($k, $v) {
    //         return ['${' . $k . '}', $v];
    //     });
    //     return str_replace(array_keys($variables), array_values($variables), $content);
    // }

    public static function render($file, $variables = [])
    {
        $base = __DIR__.'/../../stubs';
        $content = file_get_contents("$base/$file.stub");
        $variables = self::array_build($variables, function ($k, $v) {
            return ['{{ ' . $k . ' }}', $v];
        });
        return str_replace(array_keys($variables), array_values($variables), $content);
    }


    /// moving to module
    protected static function array_build($array, callable $callback)
    {
        $results = [];

        foreach ($array as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }
}
