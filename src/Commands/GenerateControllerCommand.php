<?php

namespace Leolopez\Crudgeneratormc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use DB;
use File;
use Leolopez\Crudgeneratormc\Classes\Helper;
use Str;

class GenerateControllerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:controller {--table=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the controller class for the given table name';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Running command to create the controller...');

        try {
            $table = $this->option('table');
        } catch (\Throwable $th) {
            $this->error('Table not found');
            return 0;
        }

        $columns = Helper::columnsFromTable($table);
        $databaseName = env('DB_DATABASE');

        $formFields = "";
        $imports = "";
        $model = Helper::formatFieldName($table);
        $className = Helper::formatClassName($table);

        foreach ($columns as $column) {
            $field = $column->Field;

            //Possible cases: PRI, MULL or none
            switch ($column->Key) {
                case "PRI":

                break;
                case "MUL":
                    $referencedTable = Helper::infoReferencedTable($databaseName, $table, $field)[0];

                    $nameTableForeign = $referencedTable->REFERENCED_TABLE_NAME;

                    $referencedTableName = Str::singular(Helper::formatClassName($nameTableForeign));

                    $referencedTableName = Helper::validateName($referencedTableName, $this);

                    $columnsForeign = DB::select('SHOW COLUMNS FROM `'.$nameTableForeign.'`');
                    $foreignColumns = [];
                    foreach ($columnsForeign as $column) {
                        $foreignColumns[] = $column->Field;
                    }

                    $nameModelSearch = $this->choice(
                        'What is the field you will use to search in the model of the form?',
                        $foreignColumns,
                        1,
                        $maxAttempts = null,
                        $allowMultipleSelections = false
                    );

                    $formFields .= "\t\t\$$model->$field = $referencedTableName::where('$nameModelSearch', \$request->$nameModelSearch)->first()->id;\n";
                    $imports .= "use App\\Models\\$referencedTableName;\n";

                break;
                default:
                    if ($field != "created_at" && $field != "updated_at") {
                        $formFields .= "\t\t\$$model->$field = \$request->$field;\n";
                    }
                break;
            }
        }

        $dataToReplace = [
            "{{imports}}" => $imports,
            "{{model}}" => $model,
            "{{formFields}}" => $formFields,
            "{{className}}" => $className,
        ];

        // Creating the file
        if (!Helper::createFile($this, "Controller", "controller.stub", $dataToReplace, 'app/Http/Controllers/', $className."Controller.php")) {
            return 0;
        }

        return 0;
    }
}
