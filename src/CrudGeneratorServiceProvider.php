<?php

namespace Leolopez\Crudgeneratormc;

use Illuminate\Support\ServiceProvider;
use Leolopez\Crudgeneratormc\Commands\GenerateApiFileCommand;
use Leolopez\Crudgeneratormc\Commands\GenerateControllerCommand;
use Leolopez\Crudgeneratormc\Commands\GenerateCrudCommand;
use Leolopez\Crudgeneratormc\Commands\GenerateModelCommand;
use Leolopez\Crudgeneratormc\Commands\GenerateVueCommand;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->commands([
            GenerateControllerCommand::class,
            GenerateModelCommand::class,
            GenerateApiFileCommand::class,
            GenerateVueCommand::class,
            GenerateCrudCommand::class,
        ]);
    }
}
