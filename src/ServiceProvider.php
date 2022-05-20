<?php

namespace Geekor\Module;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslations();

        if (app()->runningInConsole()) {

            $this->loadCommands();
        }
    }

    //////////////////////////////////////////////////

    /**
     * 导入翻译
     */
    protected function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', Consts::LANG_NAMESPACE);
    }

    /**
     * 导入 BM 的命令行命令
     */
    protected function loadCommands()
    {
        // --------------------------- 自动扫描目录中的命令 ----
        // [1] 扫描出 php 文件
        // [2] 把 .../Commands/Check.php 转换成 Geekor\BackendMaster\Console\Commands\Check 的形式
        // [3] 注册
        $dir = '/Console/Commands/';
        $path = __DIR__.$dir;

        // [1]
        $fs = app()->make(Filesystem::class);
        $list = $fs->glob($path.'*.php');

        if (count($list) > 0) {
            $cmds = [];
            $tmp = [];

            // [2]
            foreach ($list as $txt) {
                $tmp = substr($txt, strrpos($txt, '/')+1);
                $tmp = substr($tmp, 0, strrpos($tmp, '.'));
                $cmds[] = vsprintf('%s%s%s', [
                    __NAMESPACE__,
                    str_replace('/', '\\', $dir),
                    $tmp
                ]);
            }

            // [3]
            $this->commands($cmds);
        }
    }

}
