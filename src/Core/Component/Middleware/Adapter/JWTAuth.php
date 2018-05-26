<?php
namespace FwSwoole\Middleware\Adapter;

use FwSwoole\Core\Tool\Config;
use FwSwoole\Middleware\Kernel;

class JWTAuth extends Kernel
{

    /**
     * jwt 验证格式
     * $jwtToken = [
     *     header => [
     *         "typ": "JWT",
     *         "alg": "HS256"
     *     ],
     *     payload => [
     *         "iss" => "http://example.org",   #非必须。issuer 请求实体，可以是发起请求的用户的信息，也可是jwt的签发者。
     *         "iat" => 1356999524,                #非必须。issued at。 token创建时间，unix时间戳格式
     *         "exp" => "1548333419",            #非必须。expire 指定token的生命周期。unix时间戳格式
     *         "aud" => "http://example.com",   #非必须。接收该JWT的一方。
     *         "sub" => "example@example.com",  #非必须。该JWT所面向的用户
     *         "nbf" => 1357000000,   # 非必须。not before。如果当前时间在nbf里的时间之前，则Token不被接受；一般都会留一些余地，比如几分钟。
     *         "jti" => '222we',     # 非必须。JWT ID。针对当前token的唯一标识
     *         "GivenName" => "Jonny", # 自定义字段
     *         "Surname" => "Rocket",  # 自定义字段
     *     ],
     *     signature => 'eyJhbGciOiJIUzUxMiJ9'
     * ];
     */

    private $token       = '';
    private $tokenHeader = 'Bearer ';

    /**
     * 创建 token
     * @return string
     */
    public function createToken($primaryValue)
    {
        $header = [
            "typ" => "JWT",
            "alg" => "SHA256",
        ];
        $jwtHeader  = base64_encode(json_encode($header));
        $createTime = time();
        $expireTime = intval($createTime + Config::get('app.token_expire_time', '10800'));
        $payload    = [
            // "iss"   => "http://example.org", #非必须。issuer 请求实体，可以是发起请求的用户的信息，也可是jwt的签发者。
            "iat" => $createTime, #非必须。issued at。 token创建时间，unix时间戳格式
            "exp" => $expireTime, #非必须。expire 指定token的生命周期。unix时间戳格式
            // "aud"   => "http://example.com", #非必须。接收该JWT的一方。
            // "sub"   => "ububs@example.com", #非必须。该JWT所面向的用户
            // "nbf"   => 1357000000, # 非必须。not before。如果当前时间在nbf里的时间之前，则Token不被接受；一般都会留一些余地，比如几分钟。
            // "jti"   => '222we', # 非必须。JWT ID。针对当前token的唯一标识
            "id"  => $primaryValue, # 自定义字段
        ];
        $jwtPayload   = base64_encode(json_encode($payload));
        $jwtSignature = $this->createSignature($header['alg'], $jwtHeader . $jwtPayload, ENCRYPT_KEY);
        return $this->tokenHeader . $jwtHeader . '.' . $jwtPayload . '.' . $jwtSignature;
    }

    /**
     * 校验 token，并且解析token数据
     * @param  string $token
     * @return boolean
     */
    public function attempt($token)
    {
        if (strpos($token, $this->tokenHeader) !== 0) {
            return false;
        }
        $token    = mb_substr($token, mb_strlen($this->tokenHeader));
        $tokenArr = explode('.', $token);
        if (count($tokenArr) !== 3) {
            return false;
        }
        list($jwtHeader, $jwtPayload, $jwtSignature) = $tokenArr;
        // 解析 header
        $header = json_decode(base64_decode($tokenArr[0]), true);
        if (count($header) !== 2 || !isset($header['alg'])) {
            return false;
        }

        // 校验 signature
        if ($this->createSignature($header['alg'], $jwtHeader . $jwtPayload, ENCRYPT_KEY) !== $jwtSignature) {
            return false;
        }

        // 解析payload
        $payload = json_decode(base64_decode($jwtPayload), true);

        // 校验过期时间
        $time = time();
        if (isset($payload['iat']) && $payload['iat'] > $time) {
            return false;
        }
        if (isset($payload['exp']) && $payload['exp'] < $time) {
            return false;
        }

        return $payload['id'];
    }

    /**
     * 刷新 token
     * @return string
     */
    public function refreshToken()
    {

    }

    /**
     * 获取token
     * @return string
     */
    public function getJWTAuthToken()
    {
        return '';
        // return $this->createToken();
    }

    /**
     * token生成signature
     * @param  string $alg        算法
     * @param  string $string     header.payload
     * @param  string $encryptKey 加密字符
     * @return string             signature
     */
    private function createSignature($alg, $string, $encryptKey)
    {
        return hash_hmac($alg, $string, $encryptKey);
    }
}