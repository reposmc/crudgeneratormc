<?php

namespace Leolopez\Crudgeneratormc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Leolopez\Crudgeneratormc\Classes\Helper;
use Str;

class GenerateCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:crud {--table= : Table name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the CRUD for the given table name';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Running command to create the CRUD...');

        try {
            $table = $this->option('table');
        } catch (\Throwable $th) {
            $this->error('Table not found');
            return 0;
        }

        $this->call('generate:model', ['--table' => $table]);
        $this->call('generate:controller', ['--table' => $table]);
        $this->call('generate:vue', ['--table' => $table]);

        return 0;
    }
}
