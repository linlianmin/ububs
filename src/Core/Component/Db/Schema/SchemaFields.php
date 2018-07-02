<?php
namespace Ububs\Core\Component\Db\Schema;

trait SchemaFields
{

    public static function table($table)
    {
        self::getInstance()->setTable($table);
        return self::getInstance();
    }

    public function increments(string $field)
    {
        $this->fields[$field] = [
            'type' => self::TABLE_INT,
            'length' => 10,
            'increment' => true,
            'primaryKey' => true
        ];
        return self::getInstance();
    }

    public function tinyInteger(string $field, int $length = 4)
    {
        $this->fields[$field] = [
            'type' => self::TABLE_TINYINT,
            'length' => $length,
        ];
        return self::getInstance();
    }

    public function smallInteger(string $field, int $length = 6)
    {
        $this->fields[$field] = [
            'type' => self::TABLE_SMALLINTEGER,
            'length' => $length,
        ];
        return self::getInstance();
    }

    public function integer(string $field, int $length = 10)
    {
        $this->fields[$field] = [
            'type' => self::TABLE_INT,
            'length' => 10
        ];
        return self::getInstance();
    }

    public function string(string $field, int $length = 255)
    {
        $this->fields[$field] = [
            'type' => self::TABLE_STRING,
            'length' => $length,
        ];
        return self::getInstance();
    }

    public function char(string $field, int $length = 255)
    {
        $this->fields[$field] = [
            'type' => self::TABLE_CHAR,
            'length' => $length,
        ];
        return self::getInstance();
    }

    public function text(string $field)
    {
        $this->fields[$field] = [
            'type' => self::TABLE_TEXT,
        ];
        return self::getInstance();
    }

    public function date(string $field)
    {
        $this->fields[$field] = [
            'type' => self::TABLE_DATE,
        ];
        return self::getInstance();
    }

    public function unsigned()
    {
        $this->fields[array_keys($this->fields)[count($this->fields) - 1]]['unsigned'] = true;
        return self::getInstance();
    }

    public function default($default)
    {
        $this->fields[array_keys($this->fields)[count($this->fields) - 1]]['default'] = $default;
        return self::getInstance();
    }

    public function nullable(bool $boolean = true)
    {
        $this->fields[array_keys($this->fields)[count($this->fields) - 1]]['nullable'] = $boolean;
        return self::getInstance();
    }

    public function comment($comment)
    {
        $this->fields[array_keys($this->fields)[count($this->fields) - 1]]['comment'] = $comment;
        return self::getInstance();
    }

    public function index($field, $indexName)
    {
        if (!isset($this->fields[$field])) {
            throw new \Exception("Error Processing Action");
        }
        $this->fields[$field]['indexType'] = self::INDEX_INDEX;
        $this->fields[$field]['indexName'] = $indexName;
        return self::getInstance();
    }

    public function uniqueIndex($field, $indexName)
    {
        if (!isset($this->fields[$field])) {
            throw new \Exception("Error Processing Action");
        }
        $this->fields[$field]['indexType'] = self::INDEX_UNIQUE_INDEX;
        $this->fields[$field]['indexName'] = $indexName;
        return self::getInstance();
    }

}
