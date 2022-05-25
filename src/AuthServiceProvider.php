<?php

namespace Geekor\Module;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        // 导入 _modules 目录中的配置
        $module_dir = base_path('_modules');
        if (is_dir($module_dir)) {
            $fs = $this->app->make(Filesystem::class);
            foreach($fs->directories($module_dir) as $m_dir) {
                $arr = require_once($m_dir . '/module-config.php');

                $this->policies = Arr::collapse([
                    $this->policies,
                    Arr::get($arr, 'policies', []),
                ]);
            }
        }

        //...
        $this->registerPolicies();

        //
    }
}
