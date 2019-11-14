<?php
namespace Ububs\Core\Component\Db;

use Ububs\Core\Component\Factory;

class DbQuery extends Factory
{

    const COUNT_COMMAND  = 'COUNT';
    const SELECT_COMMAND = 'SELECT';
    const UPDATE_COMMAND = 'UPDATE';
    const DELETE_COMMAND = 'DELETE';
    const INSERT_COMMAND = 'INSERT';
    const SELECT_MAX     = 'MAX';
    const SELECT_MIN     = 'MIN';

    protected static $db = null;

    protected $table    = null;
    protected $selects  = '*';
    protected $minField = '';
    protected $maxField = '';
    protected $updates  = [];
    protected $wheres   = [];
    protected $limit    = [];
    protected $orders   = [];

    protected static $conditions = ['=', 'like', '<', '>', '<=', '>=', '!=', '<>', 'in', 'not in', 'between', 'not between'];

    public function getDb()
    {
        if (self::$db === null) {
            $this->connect();
        }
        return self::$db;
    }

    public function getSql()
    {

    }

    public function table($table)
    {
        $this->table = $table;
        $this->resetVar();
        return $this->getDbInstance();
    }

    /**
     * 常驻内存多进程下同一个会话请求参数清空
     * @return void
     */
    private function resetVar()
    {
        $this->selects = '*';
        $this->updates = [];
        $this->wheres  = [];
        $this->limit   = [];
        $this->orders  = [];
    }

    /**
     * 选择那些字段
     * @param  array $params 字段
     * @return object         DB对象
     */
    public function selects($params)
    {
        if (is_array($params) && !empty($params)) {
            $this->selects = implode(',', $params);
        }
        return self::getInstance();
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
        if (count($params) === 1) {
            foreach ($params[0] as $field => $item) {
                if (!is_array($item)) {
                    $this->wheres['='][] = [$field, $item];
                    continue;
                } else {
                    if (count($item) !== 2) {
                        continue;
                    }
                    list($condition, $vs)       = $item;
                    $this->wheres[$condition][] = [$field, $vs];
                }
            }
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
     * whereNot 查询
     * @param  array $params 查询条件
     * @return object        DB对象
     */
    public function whereNot(string $field, string $value)
    {
        $this->wheres['!='][] = [$field, $value];
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

    public function orderBy($field, $sort)
    {
        $this->orders[$field] = $sort;
        return self::getInstance();
    }

    /**
     * limit
     * @param  int $start 数值1
     * @param  int $limit 数值2
     * @return object       DB对象
     */
    public function limit(int $start, int $limit = null)
    {
        $this->limit = [$start, $limit];
        return self::getInstance();
    }

    public function pagination(array $pages)
    {
        $this->limit = $pages;
        return self::getInstance();
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
     * leftjoin 联表查询
     * @param  string $table       表名
     * @param  callback $func      回调函数
     * @return object
     */
    public function leftJoin($table, $func)
    {

        \call_user_func($func, self::getInstance());
        return self::getInstance();
    }

    /**
     * on 查询，配合join和leftjoin
     * @param  array $params 查询条件
     * @return object
     */
    public function on($av, $bv)
    {

        return self::getInstance();
    }

    /**
     * 解析sql语句
     * @param  string $type 增删改查类型
     * @return array
     */
    protected function parseSql($type)
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

            case self::SELECT_MAX:
                $sql = "SELECT max({$this->maxField}) as {$this->maxField} FROM {$this->table}";
                break;

            case self::SELECT_MIN:
                $sql = "SELECT min({$this->minField}) as {$this->minField} FROM {$this->table}";
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
            $this->parseWhere($sql, $queryData, $flag);
        }
        $this->parseOrder($sql);
        $this->parseLimit($sql);
        return [$sql, $queryData];
    }

    /**
     * 解析 where 条件
     * @param  string $sql       sql语句
     * @param  array $queryData  参数
     * @param  bool  $flag       是否启用预处理
     * @return array
     */
    private function parseWhere(&$sql, &$queryData, $flag = true)
    {
        if (empty($this->wheres)) {
            return true;
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
        return true;
    }

    /**
     * 解析 order 条件
     * @param  string $sql sql语句
     * @return string
     */
    private function parseOrder(&$sql)
    {
        if (empty($this->orders)) {
            return $sql;
        }
        foreach ($this->orders as $field => $sort) {
            $sql .= " ORDER BY {$field} {$sort} ";
        }
        return $sql;
    }

    /**
     * 解析 limit 条件
     * @param  string $sql sql语句
     * @return string
     */
    private function parseLimit(&$sql)
    {
        if (empty($this->limit)) {
            return true;
        }
        list($start, $limit) = $this->limit;
        $sql .= " LIMIT {$start}";
        if ($limit !== null) {
            $sql .= ", {$limit}";
        }
        return $sql;
    }
}
