<?php

namespace Leolopez\Crudgeneratormc\Classes;

use DB;
use File;
use Str;

class Helper
{
    /**
     * Replace the first letter to a capital letter.
     *
     * @return String $field
     */
    public static function formatField(string $field = ""): string
    {
        $field = str_replace("_", " ", $field);
        $field = ucwords($field);

        return $field;
    }

    /**
     * Replace the first letter to a capital letter.
     *
     * @return String $field
     */
    public static function formatClassName(string $field = ""): string
    {
        $field = str_replace("_", " ", $field);
        $field = ucwords($field);
        $field = str_replace(" ", "", $field);

        return $field;
    }

    /**
     * Replace the first letter to a capital letter.
     *
     * @return String $field
     */
    public static function formatFieldName(string $field = ""): string
    {
        $field = str_replace("_", " ", $field);
        $field = ucwords($field);
        $field = str_replace(" ", "", $field);
        $field = strtolower($field);

        return $field;
    }

    /**
     * Replace the first letter to a lower case.
     *
     * @return String $field
     */
    public static function lowerFormatFieldName(string $field = ""): string
    {
        $field = str_replace("_", " ", $field);
        $field = ucwords($field);
        $field = lcfirst($field);
        $field = str_replace(" ", "", $field);

        return $field;
    }

    /**
     * Replace the first letter to a capital letter.
     *
     * @return String $field
     */
    public static function formatLabelName(string $field = ""): string
    {
        $field = str_replace("_", " ", $field);
        $field = ucwords($field);

        return $field;
    }

    /**
     * Reads a stub file.
     * @param string $fileName
     * @return string
     */
    public static function readStub(string $fileName): string
    {
        return file_get_contents(__DIR__ . '/../Templates/' . $fileName);
    }

    /**
     * Replace the string in the stub file.
     *
     * @return String $controller
     */
    public static function replaceStringInStub(string $string = "", string $replace = "", string $stub = ""): string
    {
        $stub = str_replace($string, $replace, $stub);
        return $stub;
    }

    /**
     * Replace the string in the stub file.
     *
     * @param String $referenceTableName
     * @param Command $these
     * @param string $messageAddition
     *
     * @return String $controller
     */
    public static function validateName(string $referencedTableName = "", Object $these, string $messageAddition = "")
    {
        $referencedTableField = $referencedTableName;
        $validModelName = false;

        if (!$these->confirm("Is the '$referencedTableName' name correct$messageAddition?", true)) {
            do {
                if ($referencedTableName == "") {
                    $referencedTableName ==  $referencedTableField;
                    $these->warn("The name of the model couldn't be empty");
                }

                $referencedTableName = $these->ask("Write the new name for $referencedTableName");

                if ($referencedTableName != "") {
                    $validModelName = true;
                }

                if (!$these->confirm("Are you sure '$referencedTableName' name is correct?", true) || $validModelName == false) {
                    $referencedTableName = $referencedTableField;
                    $validModelName = false;
                }
            } while ($referencedTableName == "" || $validModelName == false);
        }

        return $referencedTableName;
    }

    public static function columnsFromTable($referencedTableName = "")
    {
        return DB::select('SHOW COLUMNS FROM `'.$referencedTableName.'`');
    }

    public static function infoReferencedTable($databaseName = "", $table = "", $field = "")
    {
        $queryForeign = "SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA IS NOT NULL AND TABLE_SCHEMA = '$databaseName' AND COLUMN_NAME = '$field' AND TABLE_NAME = '$table';";

        return DB::select($queryForeign);
    }

    public static function columnsToArray(array $columns): array
    {
        $columnsArray = [];
        foreach ($columns as $column) {
            $columnsArray[] = $column->Field;
        }

        return $columnsArray;
    }

    public static function tableColumnArray(array $columns, string $table): array
    {
        $columnsArray = [];
        foreach ($columns as $column) {
            $columnsArray[] = "$table.$column";
        }

        return $columnsArray;
    }

    public static function createFile(Object $these, string $createString, string $nameStub, array $dataToReplace, String $path, string $file): bool
    {
        $these->info("Creating $createString...");

        $model = Helper::readStub($nameStub);

        foreach ($dataToReplace as $key => $value) {
            $model = Helper::replaceStringInStub($key, $value, $model);
        }

        $modelPath = base_path($path);

        // Creating folder if not exists
        if (!is_dir($modelPath)) {
            mkdir($modelPath, 0777, true);
        }

        // Verifying if file the model exists
        $modelExists = false;
        if (file_exists($modelPath.$file)) {
            $modelExists = true;
            $these->error("The file '$file' already exists");
        }

        // Writing the model
        if ($modelExists) {
            if (!$these->confirm("Do you want to overwrite the file '$file'?", true)) {
                $these->error('The file will not be overwriten.');
                return false;
            }
        }

        File::put($modelPath.$file, $model);

        $these->info("$createString created successfully.");

        return true;
    }

    public static function getBasicTextField(string $field = ""): string
    {
        $label = Helper::formatLabelName($field);

        return "
        <!-- $field -->
            <v-col cols=\"12\" sm=\"12\" md=\"4\">
                <base-input
                label=\"$label\"
                v-model=\"\$v.editedItem.$field.\$model\"
                :rules=\"\$v.editedItem.$field\"
                />
            </v-col>
        <!-- $field -->\n
        ";
    }

    public static function getSelectSearchField(string $field = "", string $tableName = ""): string
    {
        $label = Helper::formatLabelName($field);
        $items = Helper::formatFieldName($tableName);

        return "
        <!-- $field -->
            <v-col cols=\"12\" sm=\"12\" md=\"4\">
                <base-select-search
                    label=\"$label\"
                    v-model.trim=\"\$v.editedItem.$field.\$model\"
                    :items=\"$items\"
                    item=\"$field\"
                    :rules=\"\$v.editedItem.$field\"
                />
            </v-col>
        <!-- $field -->\n
        ";
    }
}
