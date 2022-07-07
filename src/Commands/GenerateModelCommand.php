<?php

namespace Leolopez\Crudgeneratormc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use DB;
use File;
use Leolopez\Crudgeneratormc\Classes\Helper;
use Str;

class GenerateModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:model {--table= : Table name} {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the model class for the given table name';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Running command to create the model...');

        // Validating the table name
        try {
            $table = $this->option('table');
        } catch (\Throwable $th) {
            $this->error('Table not found');
            return 0;
        }
        $name = $this->option('name');

        // Getting the columns of the table
        $columns = Helper::columnsFromTable($table);
        $databaseName = env('DB_DATABASE');

        // Verifying the model
        if (!empty($name)) {
            $modelName = $name;
        } else {
            $modelName = Str::singular(Helper::formatClassName($table));

            $modelName = Helper::validateName($modelName, $this);
        }

        // Creating the strings for the template
        $fillable = "";
        $joins = "";
        $selects = "'$table.*', ";
        $allColumns = Helper::columnsToArray($columns);
        $allColumns = Helper::tableColumnArray($allColumns, $table);

        // Iterating the name of each column
        foreach ($columns as $column) {
            $fillable .= "'".$column->Field."', ";
            $field = $column->Field;

            // Each column can be: PRI, MUL or none
            switch ($column->Key) {
                case "PRI":
                    break;
                case "MUL":
                    // Getting the name of the referenced table
                    $referencedTable = Helper::infoReferencedTable($databaseName, $table, $field)[0];

                    $nameTableForeign = $referencedTable->REFERENCED_TABLE_NAME;
                    $columnReferenced = $referencedTable->REFERENCED_COLUMN_NAME;

                    // Converting the referenced table to UpperCamel case
                    $referencedTableName = Str::singular(Helper::formatClassName($referencedTable->REFERENCED_TABLE_NAME));

                    // Validate the correct name for the model
                    if ($name != "") {
                        $referencedTableName = Helper::validateName($referencedTableName, $this);
                    }

                    // Creating the array will be used for the joins
                    $columnsForeign = Helper::columnsFromTable($nameTableForeign);
                    $columnsForeign = Helper::columnsToArray($columnsForeign);
                    $columnsForeign = Helper::tableColumnArray($columnsForeign, $nameTableForeign);

                    // Adding each row of the referenced table
                    $allColumns = array_merge($allColumns, $columnsForeign);

                    // Creating the joins
                    $joins .= "->join('$nameTableForeign', '$table.$field', '=', '$nameTableForeign.$columnReferenced')\n";

                    $selects .= "'$nameTableForeign.*', ";

                break;
            }
        }

        // Selecting the fields will be used in the model to search
        $nameModelSearch = $this->choice(
            'Select the fields you want to use to search:',
            $allColumns,
            1,
            $maxAttempts = null,
            $allowMultipleSelections = true
        );

        // Creating wheres
        $wheres = "";
        foreach ($nameModelSearch as $key => $column) {
            if ($key == 0) {
                $wheres .= "\t\t->where('$column', 'like', \$search)\n";
            } else {
                $wheres .= "\t\t->orWhere('$column', 'like', \$search)\n";
            }
        }

        $dataToReplace = [
            "{{fillable}}" => $fillable,
            "{{tableName}}" => $table,
            "{{selects}}" => $selects,
            "{{joins}}" => $joins,
            "{{className}}" => $modelName,
            "{{wheres}}" => $wheres,
        ];

        // Creating the file
        if (!Helper::createFile($this, "Model", "model.stub", $dataToReplace, 'app/Models/', "$modelName.php")) {
            return 0;
        }

        return 0;
    }
}
