<?php

namespace Geekor\Module\Console\Commands;

use Geekor\Module\Models\DynamicModel;
use Geekor\Module\Support\GkFile;
use Geekor\Module\Support\GkStub;
use Illuminate\Console\Command;

class MakeModule extends Command
{
    const MODULE_DIR = '_modules';

    protected $signature =
        'bm:make-module
            {model : model name}
            {table : database table name}';

    protected $description = 'Geekor Backend Master: make a new module';

    //--------------------

    /**
     * _modules/Xyz
     */
    protected $moduleDir = self::MODULE_DIR;
    /**
     * Modules\Xyz
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
     * Models\Xyz
     */
    protected $modelClassUsingPath = '';
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
        $this->line(' # '.$this->signature);
        $this->newLine();
        //----------------

        $this->tableName = $this->argument('table');
        $this->modelName = $this->argument('model');
        $this->moduleNamespace = vsprintf('Modules\%s', [ $this->modelName ]);

        $this->modelClassUsingPath = vsprintf('%s\Models\%s', [ $this->moduleNamespace, $this->modelName ]);
        $this->ctrlName = $this->modelName . 'Controller';
        $this->ctrlDir = '/Http/Controllers/Api';
        $this->ctrlDirNamespace = 'Http\Controllers\Api';
        $this->ctrlClassUsingPath = vsprintf('%s\%s', [ $this->ctrlDirNamespace, $this->ctrlName ]);
        
        //..........................
        $this->clear_module_dir();

        $this->make_routes();
        $this->make_migration();
        $this->make_model();
        $this->make_controller();

        return 0;
    }

    //====================================
    private function clear_module_dir()
    {
        $this->moduleDir = base_path(self::MODULE_DIR) . DIRECTORY_SEPARATOR . $this->modelName;
        echo('Generate Module to: ' . $this->moduleDir);
        $this->newLine(2);

        @mkdir($this->moduleDir, 0755, true);
        GkFile::rm($this->moduleDir, false);
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
        $content = GkStub::render($stub, $attrs);
        file_put_contents($file, $content);
    }

    //=====================

    private function make_routes()
    {
        $file_name = 'normal.php';
        $dir = '/routes/api';
        $stub = 'bm.route.api.normal';
        $attrs = [
            'table' => $this->tableName,
            'model' => $this->modelName,
            'ctrlClassUsing' => vsprintf('%s\Normal\%s', [ $this->moduleNamespace, $this->ctrlClassUsingPath ]),
            'ctrlClassName' => $this->ctrlName
        ];

        $this->create_stub($dir, $file_name, $stub, $attrs);

        // [后台]专用 (只修改上面部分的参数即可)
        $file_name = 'backend.php';
        $stub = 'bm.route.api.backend';
        $attrs['ctrlClassUsing'] = vsprintf('%s\Backend\%s', [ $this->moduleNamespace, $this->ctrlClassUsingPath ]);
        $this->create_stub($dir, $file_name, $stub, $attrs);
    }

    private function make_migration()
    {
        $timestamp = date('Y_m_d_his');
        $file_name = vsprintf('%s_create_%s_table.php', [ $timestamp, $this->tableName ]);
        $dir = '/database/migrations';
        $stub = 'migration.create';
        $attrs = [
            'table' => $this->tableName,
        ];

        $this->create_stub($dir, $file_name, $stub, $attrs);
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
    }

    private function make_controller()
    {
        $file_name = vsprintf('%s.php', [ $this->ctrlName ]);
        $dir = '/Normal' .$this->ctrlDir;
        $stub = 'bm.controller.api';
        $attrs = [
            'namespace' => vsprintf('%s\Normal\%s', [ $this->moduleNamespace, $this->ctrlDirNamespace ]),
            'class' => $this->ctrlName,
            'model' => $this->modelName,
            'modelClass' => $this->modelClassUsingPath
        ];

        $this->create_stub($dir, $file_name, $stub, $attrs);

        //... 添加[后台]专用的
        $dir = '/Backend' . $this->ctrlDir;
        $attrs['namespace'] = vsprintf('%s\Backend\%s', [ $this->moduleNamespace, $this->ctrlDirNamespace ]);

        $this->create_stub($dir, $file_name, $stub, $attrs);
    }


}
