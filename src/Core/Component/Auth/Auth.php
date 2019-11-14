<?php
namespace Ububs\Core\Component\Auth;

use Ububs\Core\Component\Db\Db;
use Ububs\Core\Component\Middleware\Adapter\JWTAuth;

class Auth
{
    private static $instance = null;
    private $table           = null;

    public static function getInstance($table = 'user')
    {
        if (self::$instance === null) {
            self::$instance = new Auth();
        }
        self::$instance->table = $table;
        return self::$instance;
    }

    /**
     * 指定登录表
     * @param  string $table 表名
     * @return object
     */
    public static function guard(string $table = 'user')
    {
        $instance        = self::getInstance();
        $instance->table = $table;
        return $instance;
    }

    /**
     * 验证登录信息
     * @param  array  $loginData 登录数据
     * @param  boolean $remember  是否记住密码
     * @return array
     */
    public function attempt(array $loginData, $remember)
    {
        $result = [];
        if (count($loginData) !== 2) {
            $this->table = null;
            return $result;
        }
        $configs  = isset(config('auth.guards')[$this->table]) ? config('auth.guards')[$this->table] : [];
        $account  = $configs['account'] ?? 'account';
        $password = $configs['password'] ?? 'password';
        if (!isset($loginData[$account]) || !isset($loginData[$password])) {
            $this->table = null;
            return $result;
        }
        $wheres = [
            $account  => $loginData[$account],
            $password => generatePassword($loginData[$password]),
        ];
        $list = Db::table($this->table)->where($wheres)->first();
        if (empty($list)) {
            $this->table = null;
            return $result;
        }
        $tableData  = Db::query('describe ' . $this->table);
        $primaryKey = 'id';
        foreach ($tableData as $fieldData) {
            if ($fieldData['Key'] == 'PRI') {
                $primaryKey = $fieldData['Field'];
                break;
            }
        }
        // 生成一个token
        $result['__UBUBS_TOKEN__'] = JWTAuth::getInstance()->createToken($list[$primaryKey], $this->table);
        $result['list']            = $list;
        $this->table               = null;
        return $result;
    }

    /**
     * 检测是否已经授权登录
     * @param  string $token jwt
     * @return boolean
     */
    public function checkLogin()
    {
        if ($this->table !== JWTAuth::getInstance()->getTable()) {
            return false;
        }
        if (!$this->id()) {
            return false;
        }
        return true;
    }

    public function logout()
    {
        return true;
    }

    /**
     * 获取当前登录的用户
     * @return array
     */
    public function user()
    {
        return JWTAuth::getInstance()->user();
    }

    /**
     * 获取当前登录的主键
     * @return string
     */
    public function id()
    {
        return JWTAuth::getInstance()->id();
    }
}
