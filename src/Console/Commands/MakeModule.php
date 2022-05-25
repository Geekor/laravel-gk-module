<?php

namespace Geekor\Module\Console\Commands;

use Geekor\Module\Support\GkModuleStub;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class MakeModule extends Command
{
    const MODULE_DIR = '_modules';

    protected $signature =
        'bm:make-module
            {name : Module name}';

    protected $description = 'Geekor Module: make a new module in /' . self::MODULE_DIR;

    //--------------------

    /**
     * _modules/XYZ
     */
    protected $moduleDir = self::MODULE_DIR;
    /**
     * XYZ
     */
    protected $moduleName = '';

    /**
     * ===============================================================================
     */
    public function handle()
    {
        $this->newLine();
        $this->line(' # '.$this->signature);
        $this->newLine();
        //----------------

        $this->moduleName = Str::ucfirst( $this->argument('name') );

        //..........................
        $this->clear_module_dir();

        $this->make_routes();
        $this->make_migration();
        $this->make_model();
        $this->make_controller();

        $this->create_stub('/', 'module-config.php', 'bm.module-config', []);

        return 0;
    }

    //====================================
    private function clear_module_dir()
    {
        $this->moduleDir = base_path(self::MODULE_DIR) . DIRECTORY_SEPARATOR . $this->moduleName;
        echo('Generate Module to: ' . $this->moduleDir);
        $this->newLine(2);

        @mkdir($this->moduleDir, 0755, true);
        GkModuleStub::rm($this->moduleDir, false);
    }

    private function create_module_dir_path($sub_dir, $file_name)
    {
        $path = $this->moduleDir . $sub_dir;
        @mkdir($path, 0755, true);

        return $path . DIRECTORY_SEPARATOR . $file_name;
    }

    private function create_stub($dir, $file_name, $stub, $attrs)
    {
        $file = $this->create_module_dir_path($dir, $file_name);
        $content = GkModuleStub::render($stub, $attrs);
        file_put_contents($file, $content);
    }

    //=====================

    private function make_routes()
    {
        $file_name = 'normal-api.php';
        $dir = '/routes';
        $stub = 'bm.route.normal.empty';

        $this->create_stub($dir, $file_name, $stub, []);

        // [后台]专用 (只修改上面部分的参数即可)
        $file_name = 'backend-api.php';
        $stub = 'bm.route.backend.empty';
        $this->create_stub($dir, $file_name, $stub, []);
    }

    private function make_migration()
    {
        // $timestamp = date('Y_m_d_his');
        // $file_name = vsprintf('%s_create_%s_table.php', [ $timestamp, $this->tableName ]);
        $dir = '/database/migrations';
        // $stub = 'migration.create';
        // $attrs = [
        //     'table' => $this->tableName,
        // ];

        $this->create_module_dir_path($dir, '');
        // $this->create_stub($dir, $file_name, $stub, $attrs);
    }

    private function make_model()
    {
        // $file_name = vsprintf('%s.php', [ $this->modelName ]);
        $dir = '/Models';
        // $stub = 'bm.model';
        // $attrs = [
        //     'namespace' => $this->moduleNamespace . '\Models',
        //     'class' => $this->modelName,
        //     'table' => $this->tableName
        // ];

        $this->create_module_dir_path($dir, '');
        // $this->create_stub($dir, $file_name, $stub, $attrs);
    }

    private function make_controller()
    {
        // $file_name = vsprintf('%s.php', [ $this->ctrlName ]);
        // $dir = '/Normal' .$this->ctrlDir;
        // $stub = 'bm.controller.api';
        // $attrs = [
        //     'namespace' => vsprintf('%s\Normal\%s', [ $this->moduleNamespace, $this->ctrlDirNamespace ]),
        //     'class' => $this->ctrlName,
        //     'model' => $this->modelName,
        //     'modelClass' => $this->modelClassUsingPath
        // ];

        // $this->create_stub($dir, $file_name, $stub, $attrs);

        // //... 添加[后台]专用的
        // $dir = '/Backend' . $this->ctrlDir;
        // $attrs['namespace'] = vsprintf('%s\Backend\%s', [ $this->moduleNamespace, $this->ctrlDirNamespace ]);

        // $this->create_stub($dir, $file_name, $stub, $attrs);

        foreach([
            '/Backend/Http/Controllers',
            '/Backend/Http/Middle',
            '/Backend/Policies',
            '/Backend/Http/Resources',

            '/App/Http/Controllers',
            '/App/Http/Middle',
            '/App/Policies',
            '/App/Http/Resources',
        ] as $dir) {
            $this->create_module_dir_path($dir, '');
        }
    }


}
