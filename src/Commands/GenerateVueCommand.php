<?php

namespace Leolopez\Crudgeneratormc\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Leolopez\Crudgeneratormc\Classes\Helper;
use Str;

class GenerateVueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:vue {--table= : Table name} {--name=} {--apiName=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the vue and the api file for the given table name';

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
        $apiName = $this->option('apiName');

        $columns = Helper::columnsFromTable($table);
        $databaseName = env('DB_DATABASE');

        // Creating the table for the model
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
            $apiName = Str::singular(Helper::formatFieldName($table));

            $apiName = Helper::validateName($apiName, $this, " for the api file");
        }

        // Verifying api name
        $this->call('generate:api', ['--table' => $table, '--apiName' => $apiName]);

        // Creating strings for the template
        $apiNameCurrentTable = $apiName."Api";

        $form = "";
        $importApis = "import $apiNameCurrentTable from \"../apis/$apiNameCurrentTable\";\n";
        $validations = "";
        $dataObject = "";
        $headers = "";
        $requestApis = "";
        $dataArrays = "";
        $requestAssigns = "";
        $requestIterator = 1;

        // Iterating each column of the table
        foreach ($columns as $key => $column) {
            $field = $column->Field;

            switch ($column->Key) {
                case "PRI":

                    break;
                case "MUL":
                    // Getting the columns of the referenced table
                    $referencedTable = Helper::infoReferencedTable($databaseName, $table, $field);

                    $nameTableForeign = $referencedTable[0]->REFERENCED_TABLE_NAME;

                    $columnsForeign = Helper::columnsFromTable($nameTableForeign);
                    $columnsForeign = Helper::columnsToArray($columnsForeign);

                    $field = $this->choice('Select the field to be used in the select', $columnsForeign);

                    $field = empty($field) ? Helper::validateName($field, $this, "") : $field;
                    // dd($field);
                    // Joining the strings for the template
                    $form .= Helper::getSelectSearchField($field, $nameTableForeign);

                    $import = Helper::lowerFormatFieldName($nameTableForeign)."Api";
                    $importApis .= "import $import from \"../apis/$import\"";

                    // Creating the api file
                    if ($this->confirm("Do you want to generate the api file for $nameTableForeign?", false)) {
                        $this->call('generate:api', ['--table' => $table]);
                    }

                    $assign = Helper::formatFieldName($nameTableForeign);

                    $dataArrays .= "$assign: [],\n";
                    $requestAssigns .= "this.$assign = responses[$requestIterator].data.$assign;\n";

                    $validations .= "$field: {\n\t\trequired,\n\t\tminLength: minLength(1),\n},\n";

                    $dataObject .= "\t\t$field: \"\",";
                    $requestApis .= "$import.get(null, {\n\t\tparams: { itemsPerPage: -1 },\n\t}),";
                    $requestIterator++;

                    break;
                default:
                    if ($field == "created_at" || $field == "updated_at" || $field == "deleted_at") {
                        break;
                    }

                    $validations .= "$field: {\n\t\trequired,\n\t\tminLength: minLength(1),\n},";

                    $dataObject .= "\t\t$field: \"\",";

                    $form .= Helper::getBasicTextField($field);

                    $nameHeader = Helper::formatLabelName($field);

                    $headers .= "\n\t\t{ text: \"$nameHeader\", value: \"$field\" },";

            }
        }

        // Creating the data array
        $dataToReplace = [
            "{{className}}" => $modelName,
            "{{form}}" => $form,
            "{{importApis}}" => $importApis,
            "{{headers}}" => $headers,
            "{{validations}}" => $validations,
            "{{apiName}}" => $apiName."Api",
            "{{requestApis}}" => $requestApis,
            "{{requestAssigns}}" => $requestAssigns,
            "{{dataArrays}}" => $dataArrays,
            "{{dataObject}}" => $dataObject,
        ];

        // Creating the file
        if (!Helper::createFile($this, "Vue file", "vue.stub", $dataToReplace, 'resources/js/components/', $modelName.".vue")) {
            return 0;
        }

        return 0;
    }
}
