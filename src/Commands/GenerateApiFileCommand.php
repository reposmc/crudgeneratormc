<?php

namespace Leolopez\Crudgeneratormc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Leolopez\Crudgeneratormc\Classes\Helper;
use Str;

class GenerateApiFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:api {--table= : Table name} {--name=} {--apiName=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the api file for the given table name';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Running command to create the api file...');

        // Validating the table name
        try {
            $table = $this->option('table');
        } catch (\Throwable $th) {
            $this->error('Table not found');
            return 0;
        }
        $name = $this->option('name');
        $api = $this->option('apiName');

        // Verifying the model name
        if (!empty($name)) {
            $modelName = $name;
        } else {
            $modelName = Str::singular(Helper::formatClassName($table));

            $modelName = Helper::validateName($modelName, $this);
        }

        // Verifying api name
        if (!empty($api)) {
            $apiName = $api;
        } else {
            $apiName = Str::singular(Helper::lowerFormatFieldName($table));

            $apiName = Helper::validateName($apiName, $this, " for the api file");
        }

        // Creating file
        if (!Helper::createFile($this, "Api file", "api.stub", ['{{apiName}}' => $apiName], 'resources/js/apis/', $apiName."Api.js")) {
            return 0;
        }

        $this->warn("\n\nAdd the following route to routes/web.php: \nRoute::resource('/api/web/$apiName', ".Helper::lowerFormatFieldName($apiName)."Controller::class);");
        $this->warn("\n\nRoute::delete('/api/web/$apiName', ".Helper::lowerFormatFieldName($apiName)."[Controller::class, 'destroy']);");

        return 0;
    }
}
