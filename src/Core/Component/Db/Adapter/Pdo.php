<?php
namespace Ububs\Core\Component\Db\Adapter;

use Ububs\Core\Component\Factory;

class Pdo extends Factory
{

    private static $db = null;

    private $table   = null;
    private $selects = '*';
    private $updates = [];
    private $wheres  = [];

    const COUNT_COMMAND  = 'COUNT';
    const SELECT_COMMAND = 'SELECT';
    const UPDATE_COMMAND = 'UPDATE';
    const DELETE_COMMAND = 'DELETE';
    const INSERT_COMMAND = 'INSERT';

    private static $conditions = ['=', 'like', '<', '>', '<=', '>=', '!=', '<>', 'in', 'not in', 'between', 'not between'];

    /**
     * 连接数据库
     * @return objcet
     */
    public function connect()
    {
        $config = config('database');
        try {
            self::$db = new \PDO(
                "mysql:host=" . $config['host'] . ";port=" . $config['port'] . ";dbname=" . $config['databaseName'] . "",
                $config['user'],
                $config['password'], array(
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . $config['charset'] . "';",
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_PERSISTENT         => true,
                ));
            return self::$db;
        } catch (PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 获取数据库资源db对象
     * @return object
     */
    public function getDb()
    {
        if (self::$db === null) {
            $this->connect();
        }
        return self::$db;
    }

    /**
     * 指定 table
     * @param  string $table 表名
     * @return object        dbInstance
     */
    public function table($table)
    {
        $this->resetVar();
        $this->table = $table;
        return self::getInstance();
    }

    /**
     * 常驻内存多进程下同一个绘画请求参数清空
     * @return void
     */
    private function resetVar()
    {
        $this->selects = '*';
        $this->updates = [];
        $this->wheres  = [];
    }

    /**
     * where 条件查询
     * 参数说明：
     *     1、数组，key => value，表示相等
     *     2、两个字符串， key = value，表示相等
     *     3、三个字符串，key, condition, value，中间参数为条件
     * @param  [type] $params 查询条件
     * @return object         dbInstance
     */
    public function where(...$params)
    {
        if (empty($params)) {
            return self::getInstance();
        }
        // 数组
        if (count($params) === 1 && count($params[0]) === 1) {
            $this->wheres['='][] = [array_keys($params[0])[0], array_values($params[0])[0]];
        }
        // 两个字符串
        if (count($params) === 2) {
            list($field, $value) = $params;
            $this->wheres['='][] = [$field, $value];
        }
        if (count($params) === 3) {
            list($field, $condition, $value) = $params;
            if (!is_string($condition) || !in_array($condition, self::$conditions)) {
                throw new \Exception("where\'s condition is error", 1);
            }
            $this->wheres[$condition][] = [$field, $value];
        }
        return self::getInstance();
    }

    /**
     * whereIn 查询
     * @param  array $params 查询条件
     * @return object        DB对象
     */
    public function whereIn(string $field, array $params)
    {
        $this->wheres['in'][] = [$field, $params];
        return self::getInstance();
    }

    /**
     * whereIn 查询
     * @param  array $params 查询条件
     * @return object        DB对象
     */
    public function whereNotIn(string $field, array $params)
    {
        $this->wheres['not in'][] = [$field, $params];
        return self::getInstance();
    }

    /**
     * whereBetween 查询
     * @param  array $params 查询条件
     * @return object        DB对象
     */
    public function whereBetween(string $field, $params)
    {
        $this->wheres['between'][] = [$field, $params];
        return self::getInstance();
    }

    /**
     * whereNotBetween 查询
     * @param  array $params 查询条件
     * @return object        DB对象
     */
    public function whereNotBetween(string $field, $params)
    {
        $this->wheres['not between'][] = [$field, $params];
        return self::getInstance();
    }

    /**
     * 执行原生sql
     * @param  string $sql       sql
     * @param  array  $queryData 查询条件
     * @return array
     */
    public function query($sql, $queryData = [])
    {
        if (empty($queryData)) {
            $stmt = self::getDb()->query($sql);
            return $stmt->fetchAll();
        }
        $stmt = self::getDb()->prepare($sql);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        $stmt->execute($queryData);
        return $stmt->fetchAll();
    }

    /**
     * 插入多条数据
     * @param  array $data
     * @return bool
     */
    public function insert($data)
    {
        if (empty($data)) {
            throw new \Exception("Error Processing Request", 1);
        }
        $fileds       = implode(',', array_keys($data[0]));
        $insertValues = trim(str_repeat("(" . trim(str_repeat('?,', count($data[0])), ',') . "),", count($data)), ',');
        $stmt         = self::getDB()->prepare("INSERT INTO {$this->table} ($fileds) VALUES {$insertValues}");
        $queryData    = [];
        foreach ($data as $key => $item) {
            $queryData = array_merge($queryData, array_values($item));
        }
        return $stmt->execute($queryData);
    }

    /**
     * 新增一条数据
     * @param  array $data
     * @return bool       是否新增成功
     */
    public function create($data)
    {
        if (empty($data)) {
            throw new \Exception("Error Processing Request", 1);
        }
        $fileds = implode(',', array_keys($data));
        $values = ':' . implode(',:', array_keys($data));
        $stmt   = self::getDb()->prepare("INSERT INTO {$this->table} ($fileds) VALUES ({$values})");
        return $stmt->execute($data);
    }

    /**
     * 新增一条数据，返回插入的自增主键
     * @param  array $data
     * @return int
     */
    public function createGetId($data)
    {
        $fileds = implode(',', array_keys($data));
        $values = ':' . implode(',:', array_keys($data));
        $stmt   = self::getDb()->prepare("INSERT INTO {$this->table} ($fileds) VALUES ({$values})");
        $stmt->execute($data);
        return self::$db->lastInsertId();
    }

    /**
     * 更新
     * @param  array  更新数据
     * @return bool   是否更新成功
     */
    public function update($data)
    {
        $this->updates         = $data;
        list($sql, $queryData) = $this->parseSql(self::UPDATE_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        return $stmt->execute($queryData);
    }

    /**
     * 删除
     * @return bool 是否删除成功
     */
    public function delete()
    {
        list($sql, $queryData) = $this->parseSql(self::DELETE_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        return $stmt->execute($queryData);
    }

    /**
     * 获取总数
     * @return int
     */
    public function count()
    {
        list($sql) = $this->parseSql(self::COUNT_COMMAND);
        $stmt      = self::getDb()->query($sql);
        return (int) $stmt->fetchColumn();
    }

    /**
     * 获取value值
     * @param  string $field 字段
     * @return string
     */
    public function value(string $field)
    {
        $this->selects         = $field;
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        $stmt->execute($queryData);
        return $stmt->fetchColumn();
    }

    /**
     * 根据主键获取某一条数据
     * @param  int $id 主键value
     * @return array
     */
    public function find($id)
    {
        // 获取表详情，获取主键
        $tableData    = self::getDb()->query('describe ' . $this->table);
        $searchParams = [
            'id' => $id,
        ];
        foreach ($tableData as $fieldData) {
            if ($fieldData['Key'] == 'PRI') {
                $searchParams = [
                    $fieldData['Field'] => $id,
                ];
                break;
            }
        }
        $this->where($searchParams);
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        $stmt->execute($queryData);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetch();
    }

    /**
     * 获取一条数据
     * @return array
     */
    public function first()
    {
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        $stmt->execute($queryData);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetch();
    }

    /**
     * 获取列表数据
     * @return array
     */
    public function get()
    {
        list($sql, $queryData) = $this->parseSql(self::SELECT_COMMAND);
        $stmt                  = self::getDb()->prepare($sql);
        $stmt->execute($queryData);
        $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $stmt->fetchAll();
    }

    /**
     * 获取 sql
     * @return string
     */
    public function toSql()
    {
        list($sql) = $this->parseSql(self::SELECT_COMMAND);
        return $sql;
    }

    /**
     * 解析sql语句
     * @param  string $type 增删改查类型
     * @return array
     */
    private function parseSql($type)
    {
        if (!$this->table) {
            return errorMessage(500, 'tableName can\'t be eempty');
        }
        $sql       = '';
        $queryData = [];
        switch ($type) {
            case self::COUNT_COMMAND:
                $sql = "SELECT COUNT(*) as count FROM {$this->table}";
                break;

            case self::SELECT_COMMAND:
                $sql = "SELECT {$this->selects} FROM {$this->table}";
                break;

            case self::UPDATE_COMMAND:
                $sql = "UPDATE {$this->table} SET ";
                if (!empty($this->updates)) {
                    foreach ($this->updates as $key => $value) {
                        $sql .= $key . '=:' . $key . ',';
                        $queryData[$key] = $value;
                    }
                    $sql = rtrim($sql, ',');
                }
                break;

            case self::INSERT_COMMAND:
                $sql = "INSERT INTO {$this->table}";
                break;

            case self::DELETE_COMMAND:
                $sql = "DELETE FROM {$this->table}";
                break;

            default:
                # code...
                break;
        }
        // 解析 where 条件
        if ($type !== self::INSERT_COMMAND) {
            $flag = $type === self::COUNT_COMMAND ? false : true;
            return $this->parseWhere($sql, $queryData, $flag);
        }
        return [$sql, $queryData];
    }

    /**
     * 解析 where 条件
     * @param  string $sql       sql语句
     * @param  array $queryData  参数
     * @param  bool  $flag       是否启用预处理
     * @return array
     */
    private function parseWhere($sql, $queryData, $flag = true)
    {
        if (empty($this->wheres)) {
            return $sql;
        }
        $sql .= " WHERE ";
        $existAnd = false;
        foreach ($this->wheres as $condition => $item) {
            $condition = strtoupper($condition);
            foreach ($item as $index => $data) {
                if ($existAnd) {
                    $sql .= ' AND ';
                }
                $existAnd            = true;
                list($field, $value) = $data;
                // between、notbetween
                if ($condition === 'BETWEEN' || $condition === 'NOT BETWEEN') {
                    $start = isset($value[0]) ? $value[0] : '';
                    $end   = isset($value[1]) ? $value[1] : '';
                    $sql .= " {$field} {$condition} {$start} AND {$end} ";
                    continue;
                }
                // in notin 查询不支持变量绑定
                if (is_array($value)) {
                    $value = implode("','", $value);
                    $sql .= " {$field} {$condition} ('{$value}') ";
                    continue;
                }
                // 关联查询
                if (strpos($field, '.') > -1) {
                    $sql .= " {$field} {$condition} '{$value}' ";
                    continue;
                }
                if ($flag) {
                    $queryData[$field] = $value;
                    $sql .= "{$field} {$condition} :{$field} ";
                } else {
                    $sql .= "{$field} {$condition} '{$value}' ";
                }
            }
        }
        return [$sql, $queryData];
    }
}