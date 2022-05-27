<?php

namespace Geekor\Module\Console\Commands;

use Geekor\Module\Support\GkModuleStub;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class MakeModel extends Command
{
    const MODULE_DIR = '_modules';

    protected $signature =
        'bm:make-model
        {module : module name}
        {model : model name}
        {--no-backend}
        {--no-normal}
        ';

    protected $description = 'Geekor Module: make a model in target module';

    //--------------------
    /**
     * Abc
     */
    protected $moduleName = '';
    /**
     * _modules/Abc
     */
    protected $moduleDir = self::MODULE_DIR;
    /**
     * Modules\Abc
     */
    protected $moduleNamespace = '';
    /**
     * xyzs
     */
    protected $tableName = '';
    /**
     * Xyz
     */
    protected $modelName = '';
    /**
     * Modules\Abc\Models\Xyz
     */
    protected $modelClassUsingPath = '';
    /**
     * ModelName -> model-names
     */
    protected $apiName = '';
    /**
     * XyzController
     */
    protected $ctrlName = '';
    /**
     * /Http/Controllers/Api
     */
    protected $ctrlDir = '';
    /**
     * Http\Controllers\Api
     */
    protected $ctrlDirNamespace = '';
    /**
     * Http\Controllers\Api\XyzController
     */
    protected $ctrlClassUsingPath = '';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->newLine();
        $this->line(' >> '.$this->signature);
        $this->newLine();
        //----------------

        $this->moduleName = Str::ucfirst( $this->argument('module') );
        $this->moduleDir = base_path(self::MODULE_DIR) . DIRECTORY_SEPARATOR . $this->moduleName;
        $this->modelName = Str::studly( $this->argument('model') );
        $this->moduleNamespace = vsprintf('Modules\%s', [ Str::ucfirst($this->moduleName) ]);
        $this->tableName = Str::snake(Str::pluralStudly($this->modelName)); // ModelName -> model_names
        $this->apiName = Str::kebab(Str::pluralStudly($this->modelName)); // ModelName -> model-names

        $this->modelClassUsingPath = vsprintf('%s\Models\%s', [ $this->moduleNamespace, $this->modelName ]);
        $this->ctrlName = $this->modelName . 'Controller';
        $this->ctrlDir = '/Http/Controllers';
        $this->ctrlDirNamespace = 'Http\Controllers';
        $this->ctrlClassUsingPath = vsprintf('%s\%s', [ $this->ctrlDirNamespace, $this->ctrlName ]);

        if (! is_dir($this->moduleDir)) {
            $this->error('module directory not exists: ' . $this->moduleDir);
            $this->newLine(2);
            return 1;
        }

        // echo($this->moduleDir);
        // $this->newLine();
        // echo($this->tableName);
        // $this->newLine();
        // echo($this->modelName);
        // $this->newLine(2);

        //..........................
        $this->clear_module_dir();

        $this->make_routes();
        $this->make_migration();
        $this->make_model();
        $this->make_controller();

        // --------------------------- dump info ----
        // 因为只有在下次调用才会有显示，所以不调用 $this->call('route:list', ['--path' => $this->apiName]);

        $this->newLine();
        $this->info('for checking migration:');
        $this->info('    php artisan migrate:status');
        $this->newLine();

        $this->info('for checking routes:');
        $this->info('    php artisan route:list --path='.$this->apiName);

        $this->newLine(2);
        return 0;
    }

    //====================================
    private function clear_module_dir()
    {
        echo('Generate Module to: ' . $this->moduleDir);
        $this->newLine(2);

        // @mkdir($this->moduleDir, 0755, true);
        // GkModuleStub::rm($this->moduleDir, false);
    }

    private function get_module_dir_path($sub_dir, $file_name)
    {
        $path = $this->moduleDir . $sub_dir;
        @mkdir($path, 0755, true);

        return $path . DIRECTORY_SEPARATOR . $file_name;
    }

    private function create_stub($dir, $file_name, $stub, $attrs)
    {
        $file = $this->get_module_dir_path($dir, $file_name);
        $content = GkModuleStub::render($stub, $attrs);
        file_put_contents($file, $content);
    }

    private function append_stub($dir, $file_name, $stub, $attrs)
    {
        $file = $this->get_module_dir_path($dir, $file_name);
        $content = GkModuleStub::render($stub, $attrs);
        file_put_contents($file, $content, FILE_APPEND);
    }
    //=====================

    private function make_routes()
    {
        $file_name = 'normal-api.php';
        $dir = '/routes';
        $stub = 'bm.route.normal.append';
        $attrs = [
            'apiName' => $this->apiName,
            'model' => $this->modelName,
            'ctrlClassUsing' => vsprintf('%s\App\%s', [ $this->moduleNamespace, $this->ctrlClassUsingPath ]),
            'ctrlClassName' => $this->ctrlName
        ];

        if (! $this->option("no-normal")) {
            $file = $this->get_module_dir_path($dir, $file_name);
            $content = file_get_contents($file);
            if (! Str::contains($content, vsprintf('/api/%s/',[$this->apiName]))) {
                $this->append_stub($dir, $file_name, $stub, $attrs);
                $this->line(" > append routes into: $dir/$file_name");
            }
        }

        // [后台]专用 (只修改上面部分的参数即可)
        $file_name = 'backend-api.php';
        $stub = 'bm.route.backend.append';
        $attrs['ctrlClassUsing'] = vsprintf('%s\Backend\%s', [ $this->moduleNamespace, $this->ctrlClassUsingPath ]);


        if (! $this->option("no-backend")) {
            $file = $this->get_module_dir_path($dir, $file_name);
            $content = file_get_contents($file);
            if (! Str::contains($content, vsprintf('/api/backend/%s/',[$this->apiName]))) {
                $this->append_stub($dir, $file_name, $stub, $attrs);
                $this->line(" > append routes into: $dir/$file_name");
            }
        }
    }

    private function make_migration()
    {
        $timestamp = date('Y_m_d_his');
        $file_name_s = vsprintf('create_%s_table.php', [$this->tableName]);
        $file_name = vsprintf('%s_%s', [ $timestamp, $file_name_s]);
        $dir = '/database/migrations';
        $stub = 'migration.create';
        $attrs = [
            'table' => Str::kebab($this->tableName),
        ];

        // 如果已经创建了迁移文件，就不用再次创建啦
        $pattern = $this->get_module_dir_path($dir, '*_' . $file_name_s);
        if (count(glob($pattern)) > 0) {
            return;
        }

        $this->create_stub($dir, $file_name, $stub, $attrs);
        $this->line(" > create migrations to: $dir/$file_name");
    }

    private function make_model()
    {
        $file_name = vsprintf('%s.php', [ $this->modelName ]);
        $dir = '/Models';
        $stub = 'bm.model';
        $attrs = [
            'namespace' => $this->moduleNamespace . '\Models',
            'class' => $this->modelName,
            'table' => $this->tableName
        ];

        $this->create_stub($dir, $file_name, $stub, $attrs);
        $this->line(" > create model to: $dir/$file_name");
    }

    private function make_controller()
    {
        $file_name = vsprintf('%s.php', [ $this->ctrlName ]);
        $dir = '/App' .$this->ctrlDir;
        $stub = 'bm.controller.api';
        $attrs = [
            'namespace' => vsprintf('%s\App\%s', [ $this->moduleNamespace, $this->ctrlDirNamespace ]),
            'class' => $this->ctrlName,
            'model' => $this->modelName,
            'modelClass' => $this->modelClassUsingPath
        ];

        if (! $this->option("no-normal")) {
            $this->create_stub($dir, $file_name, $stub, $attrs);
            $this->line(" > create controller to: $dir/$file_name");
        }

        //... 添加[后台]专用的
        $dir = '/Backend' . $this->ctrlDir;
        $attrs['namespace'] = vsprintf('%s\Backend\%s', [ $this->moduleNamespace, $this->ctrlDirNamespace ]);

        if (! $this->option("no-backend")) {
            $this->create_stub($dir, $file_name, $stub, $attrs);
            $this->line(" > create controller to: $dir/$file_name");
        }
    }


}
