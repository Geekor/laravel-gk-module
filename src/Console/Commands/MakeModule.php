<?php

namespace Geekor\Module\Console\Commands;

use Geekor\Core\Support\GkFileUtil;
use Geekor\Module\Models\DynamicModel;
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

    protected $module_dir = self::MODULE_DIR;

    protected $table_name = ''; // xyzs
    protected $model_name = ''; // Xyz
    protected $model_class = ''; // Modules\Xyz\Models\Xyz

    public function __construct()
    {
        parent::__construct();
    }

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

        $this->model_name = $this->argument('model');
        $this->table_name = $this->argument('table');
        // var_dump($model_name, $table_name);

        $this->clear_module_dir();
        $this->make_migration();
        $this->make_model();
        $this->make_controller();

        return 0;
    }

    //====================================
    private function clear_module_dir()
    {
        $this->module_dir = base_path(self::MODULE_DIR) . DIRECTORY_SEPARATOR . $this->model_name;
        var_dump($this->module_dir);

        @mkdir($this->module_dir, 0755, true);
        GkFileUtil::rm($this->module_dir, false);
    }

    private function get_module_dir_path($sub_dir, $file_name)
    {
        $path = $this->module_dir . $sub_dir;
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

    private function make_migration()
    {
        $timestamp = date('Y_m_d_his');
        $file_name = vsprintf('%s_create_%s_table.php', [ $timestamp, $this->table_name ]);
        $dir = '/database/migrations';
        $stub = 'migration.create';
        $attrs = [
            'table' => $this->table_name,
        ];

        $this->create_stub($dir, $file_name, $stub, $attrs);
    }

    private function make_model()
    {
        $file_name = vsprintf('%s.php', [ $this->model_name ]);
        $dir = '/Models';
        $stub = 'bm.model';
        $attrs = [
            'namespace' => vsprintf('Modules\%s\Models', [ $this->model_name ]),
            'class' => $this->model_name,
            'table' => $this->table_name
        ];

        $this->model_class = $attrs['namespace'] . '\\' . $this->model_name ;

        $this->create_stub($dir, $file_name, $stub, $attrs);
    }

    private function make_controller()
    {
        $className = $this->model_name . 'Controller';
        $file_name = vsprintf('%s.php', [ $className ]);
        $dir = '/Http/Controllers/Api';
        $stub = 'bm.controller.api';
        $attrs = [
            'namespace' => vsprintf('Modules\%s\Http\Controllers\Api', [ $this->model_name ]),
            'class' => $className,
            'model' => $this->model_name,
            'modelClass' => $this->model_class
        ];

        $this->create_stub($dir, $file_name, $stub, $attrs);

        //... 添加后台专用的
        $dir = '/Backend/Http/Controllers/Api';
        $attrs['namespace'] = vsprintf('Modules\%s\Backend\Http\Controllers\Api', [ $this->model_name ]);

        $this->create_stub($dir, $file_name, $stub, $attrs);
    }


}
