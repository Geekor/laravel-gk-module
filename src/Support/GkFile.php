<?php


namespace Geekor\Module\Support;


class GkFile
{
    /**
     * 删除文件夹
     *
     * @param $dir : string
     * @pararm $removeSelf : bool
     *
     * @return null
     */
    public static function rm($dir, $removeSelf = true)
    {
        if (is_dir($dir)) {
            $dh = opendir($dir);
            while (($file = readdir($dh)) !== false) {
                if ($file != "." && $file != "..") {
                    $fullPath = rtrim($dir, '/\\') . '/' . $file;
                    if (is_dir($fullPath)) {
                        self::rm($fullPath, true);
                    } else {
                        @unlink($fullPath);
                    }
                }
            }
            closedir($dh);
            if ($removeSelf) {
                @rmdir($dir);
            }
        } else {
            @unlink($dir);
        }
        return true;
    }
}