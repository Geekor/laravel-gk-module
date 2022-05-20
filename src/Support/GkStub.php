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
        $variables = array_build($variables, function ($k, $v) {
            return ['{{ ' . $k . ' }}', $v];
        });
        return str_replace(array_keys($variables), array_values($variables), $content);
    }
}
