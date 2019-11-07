<?php

use Ububs\Core\Http\Interaction\Request;
use Ububs\Core\Http\Interaction\Response;
use Ububs\Core\Swoole\Server\ServerManager;
use Ububs\Core\Tool\Config\Config;

/**
 * 获取config配置
 * @param  string $key    变量的key
 * @param  string $default 默认值
 * @return value
 */
function config($key, $default = null)
{
    return Config::get($key, $default);
}

/**
 * 获取对象
 * @param  string   需要实例化的对象
 * @param  refresh  是否需要重新实例化
 * @return object
 */
function app($class, $refresh = false)
{
    static $appClass = [];
    if ($refresh) {
        if (!class_exists($class)) {
            throw new \Exception("Error class is not exist", 1);
        }
        return new $class();
    }
    if (isset($appClass[$class])) {
        return $appClass[$class];
    }
    if (!class_exists($class)) {
        throw new \Exception("Error class is not exist", 1);
    }
    $appClass[$class] = new $class();
    return $appClass[$class];
}

/**
 * 调试打印内容
 * @param  array | string | object $data
 * @return value
 */
function write_response($data)
{
    return Response::write($data);
}

/**
 * 获取server对象
 * @return object swoole_server
 */
function getServ()
{
    return ServerManager::getServer();
}

function redirect($route)
{
    return Response::redirect($route);
}

/**
 * 返回错误信息
 * @param  string $message 错误内容
 * @return array
 */
function errorMessage($message)
{
    return Response::error(500, $message);
}

/**
 * 获取 token
 * @return string
 */
function csrf_token()
{
    $csrfToken = \Ububs\Core\Component\Middleware\Adapter\VerifyCsrfToken::getInstance()->getCsrfToken();
    return "<meta name='Authrization' content='" . $csrfToken . "' />\r\n";
}

function generatePassword($password)
{
    return sha1(md5($password, config('app.encrypt_key', 'http://www.ububs.com')));
}

/**
 * 入口php文件获取最新的文件
 * @param  string $filePath 文件路劲
 * @return string
 */
function webpackLoad($filePath)
{
    $target  = APP_ROOT . '/public/' . dirname(str_replace('\\', '/', $filePath));
    $files   = new \DirectoryIterator($target);
    $name = basename($filePath);
    $fileDatas = [];
    $pos = strpos($name, '.');
    $fileDatas[] = mb_substr($name, 0, $pos);
    $fileDatas[] = mb_substr($name, $pos);
    $result  = $lastModifyTime  = '';
    if (count($fileDatas) !== 2) {
        return $result;
    }

    foreach ($files as $file) {
        // 跳过目录 和 .  ..
        if ($file->isDot() || $file->isDir()) {
            continue;
        }
        $pattern = '/^' . $fileDatas[0] . '.*' . $fileDatas[1] . '$/';
        if (preg_match($pattern, $file->getFilename())) {
            if (!$lastModifyTime || $file->getCTime() > $lastModifyTime) {
                $lastModifyTime = $file->getCTime();
                $result         = $file->getFilename();
            }
        }
    }
    return '/public/' . dirname(str_replace('\\', '/', $filePath)) . '/' . $result;
}

/**
 * 获取用户真实ip
 * @return string 
 */
function getRealIp()
{
    return Request::getRealIp();
}

/**
 * 加密 和 解密 算法
 * @param  String $string 需要加密的字符
 * @param  String $key 密匙
 * @param  String $operation decrypt表示解密，其它表示加密
 * @param  Int $expiry 有效期，秒数
 * @return  String $result, 为空表示解密失败，不存在
 */
function authcode($string, $key = '', $operation = '', $expiry = 0)
{
    $key = md5($key);
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    $ckey_length = 4;

    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'decrypt' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey   = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
    // 解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string        = $operation == 'decrypt' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result        = '';
    $box           = range(0, 255);
    $rndkey        = array();
    // 产生密匙簿
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for ($j = $i = 0; $i < 256; $i++) {
        $j       = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp     = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a       = ($a + 1) % 256;
        $j       = ($j + $box[$a]) % 256;
        $tmp     = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'decrypt') {
        // 验证数据有效性，请看未加密明文的格式
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
            substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)
        ) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}
