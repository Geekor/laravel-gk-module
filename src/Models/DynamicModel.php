<?php

namespace Geekor\Module\Models;

use Geekor\Module\Support\GkStub;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DynamicModel extends Model
{
    /**
     * @param $table
     *
     * @return Model
     */
    public static function make($table)
    {
        $className = 'DynamicModel_' . $table;
        $class = '\\Geekor\\Module\\Models\\' . $className;

        if (!class_exists($class)) {
            $file = base_path('bootstrap/cache/' . $className . '.php');
            if (!file_exists($file)) {
                $table_name = strtolower( $table );
                if (! Str::endsWith($table, 's')) {
                    $table_name = $table_name . 's';
                }

                $content = GkStub::render('DynamicModel', [
                    'className' => $className,
                    'table' => $table_name,
                ]);
                file_put_contents($file, $content);
            }
            require $file;
        }
        return new $class();
    }
}
