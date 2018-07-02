<?php
namespace Ububs\Core\Component\Db;

use Ububs\Core\Component\Db\Schema\SchemaChange;
use Ububs\Core\Component\Db\Schema\SchemaCreate;
use Ububs\Core\Component\Db\Schema\SchemaFields;
use Ububs\Core\Component\Factory;

class Schema extends Factory
{
    use SchemaFields;
    use SchemaCreate;
    use SchemaChange;

    const TABLE_INT          = 'INT';
    const TABLE_STRING       = 'VARCHAR';
    const TABLE_CHAR         = 'CHAR';
    const TABLE_TEXT         = 'TEXT';
    const TABLE_TINYINT      = 'TINYINT';
    const TABLE_SMALLINTEGER = 'SMALLINT';
    const TABLE_BOOLEAN      = 'BOOLEAN';
    const TABLE_DATE         = 'DATE';

    const INDEX_INDEX        = 'INDEX';
    const INDEX_UNIQUE_INDEX = 'UNIQUE KEY';

    /**
     * $fields = [
     *     'title' => [
     *         'type' => 'string',
     *         'length' => '50',
     *         'nullable' => false,
     *         'default' => '',
     *         'increment' => false,
     *         'primaryKey' => false,
     *         'unsigned' => false,
     *         'indexType' => 'index',
     *         'indexName' => 'index_title'
     *     ]
     * ];
     */
    private $table  = null;
    public $engine  = '';
    private $fields = [];
    private $type   = '';

    public static function getDb()
    {
        return Db::getDbInstance()->getDb();
    }

    public function setTable($table)
    {
        $this->engine = '';
        $this->type   = '';
        $this->fields = [];
        $this->table  = $table;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getFields()
    {
        $sql = "DESC {$this->getTable()}";
        $stmt = self::getDb()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function isExist($table)
    {
        $sql    = "SHOW TABLES LIKE '" . $table . "'";
        $result = self::getDb()->query($sql);
        return count($result->fetchAll()) === 1;
    }

    public static function destory($table)
    {
        $sql = "DROP TABLE {$table}";
        self::getDb()->exec($sql);
    }

    public static function truncate($table)
    {
        $sql = "TRUNCATE TABLE {$table}";
        return self::getDb()->exec($sql);
    }

    public function run()
    {
        $sql = $this->assembleSql();
        self::getDb()->exec($sql);
    }

    private function assembleSql()
    {
        if (empty($this->fields)) {
            throw new \Exception("Error Processing Action");
        }
        $sql = '';
        switch (strtoupper($this->type)) {
            case self::$create_type:
                $sql = "CREATE TABLE " . $this->table . ' (';
                break;

            case self::$change_type:
                # code...
                break;

            default:
                throw new \Exception("Error Processing Request", 1);
                break;
        }
        foreach ($this->fields as $field => $item) {
            $tempType = isset($item['type']) ? $item['type'] : '';
            if ($tempType === '') {
                continue;
            }

            $tempLength = isset($item['length']) ? $item['length'] : '';
            if ($tempLength !== '') {
                $tempLength = "({$tempLength})";
            }

            $tempUnsigned = isset($item['unsigned']) ? $item['unsigned'] : '';
            if ($tempUnsigned === true) {
                $tempUnsigned = "UNSIGNED";
            }

            $tempNullable = isset($item['nullbale']) ? '' : 'NOT NULL';

            $tempIncrement = isset($item['increment']) ? $item['increment'] : '';
            if ($tempIncrement === true) {
                $tempIncrement = "AUTO_INCREMENT";
            }

            $tempPrimaryKey = isset($item['primaryKey']) ? $item['primaryKey'] : '';
            if ($tempPrimaryKey === true) {
                $tempPrimaryKey = "PRIMARY KEY";
            }

            $tempDefault = isset($item['default']) ? $item['default'] : false;
            if ($tempDefault !== false) {
                $tempDefault = "DEFAULT '" . $tempDefault . "'";
            } else {
                $tempDefault = '';
            }

            $sql .= " {$field} {$tempType}{$tempLength} {$tempUnsigned} {$tempNullable} {$tempIncrement} {$tempPrimaryKey} {$tempDefault},";

            if (isset($item['indexType'])) {
                $indexType = $item['indexType'];
                $indexName = isset($item['indexName']) ? $item['indexName'] : 'index_' . $field;
                $sql .= " {$indexType} {$indexName} ({$field}),";
            }
        }
        $sql = rtrim($sql, ',');
        return $sql . ");";
    }
}
