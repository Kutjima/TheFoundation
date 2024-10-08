<?php

/**
 * 
 */

namespace TheFoundation;

/**
 * 
 */
class PDOFactory
{

    /**
     * @param \PDO $PDO - instance of PDO
     * @param array $struct - [
     *      <table_name> => [
     *          'columns' => [
     *              <column_name> => <column_datatype>,
     *              ...
     *          ],
     *          'constraints' => [
     *              <column_constraint>,
     *              ...
     *          ],
     *      ],
     * ];
     * @return bool
     * @throws \PDOException
     */
    public static function inidb(\PDO $PDO, array $struct): bool
    {
        $query_string = [];

        foreach ($struct as $table => $details) {
            $query_string[] = sprintf('CREATE TABLE IF NOT EXISTS `%s` (', $table);
            $columns = [];

            foreach ($details['columns'] as $name => $type)
                $columns[] = sprintf('`%s` %s', $name, $type);

            $query_string[] = "\t" . implode(",\n\t", array_merge($columns, $details['constraints']));
            $query_string[] = ');';
        }

        try {
            $PDO->exec(implode("\n", $query_string));
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Prepare a statement with PDO::prepare
     * 
     * @param PDO $PDO
     * @param string $table
     * @param array $columns
     * @param array $constraints
     * @return optional PDOStatement
     * @throws PDOException
     */
    public static function upsert(\PDO $PDO, string $table, array $columns, array $constraints = []): ?\PDOStatement
    {
        $scolumns = implode(', ', $columns);
        $placeholders = str_repeat('?, ', count($columns) - 1) . '?';
        $sconstraints = implode(', ', $constraints);
        $update_set = implode(', ', array_map(fn($column) => sprintf('%s = excluded.%s', $column, $column), $columns));

        $query_string = empty($constraints)
            ? sprintf('INSERT INTO %s (%s) VALUES (%s);', $table, $scolumns, $placeholders)
            : sprintf('INSERT INTO %s (%s) VALUES (%s) ON CONFLICT(%s) DO UPDATE SET %s;', $table, $scolumns, $placeholders, $sconstraints, $update_set);

        if ($stmt = $PDO->prepare($query_string))
            return $stmt;

        return null;
    }

    /**
     * 
     */
    public static function SQLite(string $database, array $options = []): \PDO
    {
        $PDO = new \PDO('sqlite:' . $database, '', '', array_replace([
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_EMULATE_PREPARES => true,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_PERSISTENT => false,
        ], $options));

        $PDO->sqliteCreateFunction('json_value', function (string $json_string, string $json_path) {
            $json_data = @json_decode($json_string, \JSON_OBJECT_AS_ARRAY);

            foreach (explode('->', $json_path) as $index_name) {
                if (!is_array($json_data) || !isset($json_data[$index_name]))
                    return null;

                $json_data = $json_data[$index_name];
            }

            return is_array($json_data) ? json_encode($json_data) : $json_data;
        });

        $PDO->sqliteCreateFunction('json_contains', function (string $json_string, string $json_path, string ...$search_values) {
            $json_data = @json_decode($json_string, \JSON_OBJECT_AS_ARRAY);

            foreach ($json_path ? explode('->', $json_path) : [] as $index_name) {
                if (!is_array($json_data) || !isset($json_data[$index_name]))
                    return 0;

                $json_data = $json_data[$index_name];
            }

            foreach ($search_values as $search_value)
                if (in_array($search_value, (array) $json_data))
                    return 1;

            return 0;
        });

        $PDO->sqliteCreateFunction('json_contains_all', function (string $json_string, string $json_path, string ...$search_values) {
            $json_data = @json_decode($json_string, \JSON_OBJECT_AS_ARRAY);

            foreach ($json_path ? explode('->', $json_path) : [] as $index_name) {
                if (!is_array($json_data) || !isset($json_data[$index_name]))
                    return 0;

                $json_data = $json_data[$index_name];
            }

            $nb = 0;
            foreach ($search_values as $search_value)
                if (in_array($search_value, (array) $json_data))
                    $nb++;

            return $nb >= count($search_values);
        });

        return $PDO;
    }
}
