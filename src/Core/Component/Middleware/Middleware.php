<?php

namespace Ububs\Core\Component\Middleware;

use Ububs\Core\Component\Factory;

class Middleware extends Factory
{

    public static function validate($middlewares = [])
    {
        if (!empty($middlewares)) {
            $mis = app('\App\Http\Middleware\Kernel')->routeMiddleware;
            if (empty($mis)) {
                throw new \Exception("Never exists Middleware", 1);
            }
            foreach ($middlewares as $middleware) {
                if (!isset($mis[$middleware])) {
                    if (empty($mis)) {
                        throw new \Exception("Never exists {$middleware} Middleware", 1);
                    }
                }
                $obj = (new $mis[$middleware]);
                $rs = $obj->handle();
                if (!$rs) {
                    return false;
                }
                if (is_numeric($rs) && isset($obj->codeMessage[$rs])) {
                    return [false, $obj->codeMessage[$rs]];
                }
            }
        }
        return true;
    }
}
