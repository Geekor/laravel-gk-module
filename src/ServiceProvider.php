<?php

namespace Geekor\Module;

use App\Http\Kernel as AppKernel;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

use Geekor\Module\Consts;
use Illuminate\Support\Arr;

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

        $this->loadCommands();

        $this->loadMigrations();

        $this->defineRoutes();

        $this->configureMiddleware();
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
        if (! app()->runningInConsole()) {
            return;
        }

        // --------------------------- 自动扫描目录中的命令 ----
        // [1] 扫描出 php 文件
        // [2] 把 .../Commands/Check.php 转换成 Geekor\BackendMaster\Console\Commands\Check 的形式
        // [3] 注册
        $dir = '/Console/Commands/';
        $path = __DIR__.$dir;

        // [1]
        $fs = resolve(Filesystem::class);
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

    protected function loadMigrations()
    {
        if (! app()->runningInConsole()) {
            return;
        }

        //...load sub modules migrations
        $module_dir = base_path('_modules');
        if (! is_dir($module_dir)) {
            return;
        }

        $fs = resolve(Filesystem::class);
        foreach($fs->directories($module_dir) as $m_dir) {
            $this->loadMigrationsFrom($m_dir . '/database/migrations');
        }
    }

    /**
     * Define the Sanctum routes.
     *
     * @return void
     */
    protected function defineRoutes()
    {
        if (app()->routesAreCached()) {
            return;
        }

        //...load sub modules routes
        $module_dir = base_path('_modules');
        if (! is_dir($module_dir)) {
            return;
        }

        $fs = resolve(Filesystem::class);
        foreach($fs->directories($module_dir) as $m_dir) {

            foreach($fs->glob($m_dir . '/routes/*.php') as $file) {
                $this->loadRoutesFrom($file);
            }
        }
    }

    /**
     * 添加中间件
     *
     * @return void
     */
    protected function configureMiddleware()
    {
        $routeMiddlewares = [];

        $module_dir = base_path('_modules');
        if (is_dir($module_dir)) {
            $fs = resolve(Filesystem::class);
            foreach($fs->directories($module_dir) as $m_dir) {
                $arr = require_once($m_dir . '/module-config.php');

                foreach ($arr['routeMiddleware'] ?? [] as $k => $v) {
                    if (Arr::has($routeMiddlewares, $k)) {
                        die("\n !! >>> routeMiddleware name of [$k] already defined by $v \n\n");
                    }

                    $routeMiddlewares[$k] = $v;
                }
            }
        }

        // Kernel 在 App 中是单例，
        // 通过 make() 实际只是取得单例。
        $kernel = resolve(Kernel::class);

        if ($kernel instanceof AppKernel) {

            // ----- 下面是要注册到 全局中间件列表
            // $kernel->prependMiddleware(XXXX::class);

            // ----- 下面是要注册到 路由中间件列表

            if(! method_exists($kernel,'appendToRouteMiddleware')) {
                echo('You need add (use \Geekor\BackendMaster\Traits\SettingRoutes;) in app\Http\Kernel.php');

            } else {
                foreach ($routeMiddlewares as $key => $middleware) {
                    $kernel->appendToRouteMiddleware($key, $middleware);
                }
            }
        }
    }

}
