<?php

namespace Ububs\Core\Http\Interaction;

use Ububs\Core\Component\Middleware\Adapter\VerifyCsrfToken;
use Ububs\Core\Component\Middleware\Middleware;
use Ububs\Core\Http\Factory;
use UBubs\Core\Http\Interaction\Response;
use Ububs\Core\Tool\StatusCode\StatusCode;

class Route extends Factory
{
    private static $dispatcher;
    private static $routes;
    public $actionNamespace;
    public $actionController;
    public $actionMethod;

    public function init()
    {
        dir_make(APP_ROOT . 'storage/cache/route');
        self::$dispatcher = \FastRoute\cachedDispatcher(function (\FastRoute\RouteCollector $r) {
            self::$routes = $r;
            require_once APP_ROOT . '/routes/web.php';
        }, [
            'cacheFile' => APP_ROOT . 'storage/cache/route/' . time('YmdHis') . rand() . rand() . '.cache', /* required */
            'cacheDisabled' => false, /* optional, enabled by default */
        ]);
    }

    public function run($routeInfo)
    {
        $routers = isset($routeInfo[1]) ? $routeInfo[1] : [];
        if (empty($routers)) {
            return Response::error(StatusCode::CODE_UNAUTHORIZED);
        }

        // csrf 验证
        if (!$cr = VerifyCsrfToken::getInstance()->checkCsrf()) {
            return Response::error(StatusCode::CODE_UNAUTHORIZED);
        }

        // 中间件验证
        $middleware = isset($routers['middleware']) ? (array) $routers['middleware'] : [];
        $mr =  Middleware::validate($middleware);
        \file_put_contents("/tmp/test.txt", $mr, FILE_APPEND);
        if (!$mr || \is_array($mr)) {
            $rc = $mr[0] ?? StatusCode::CODE_UNAUTHORIZED;
            $rm = $mr[1] ?? '';
            return Response::error($rc, $rm);
        }
        $actions               = isset($routers['action']) ? $routers['action'] : '';
        $this->actionNamespace = isset($routers['namespace']) ? $routers['namespace'] : '';
        if ($actions === '') {
            return Response::error(StatusCode::CODE_UNAUTHORIZED);
        }
        if (is_callable($actions)) {
            return call_user_func($actions);
        }
        list($this->actionController, $this->actionMethod) = explode('@', $actions);
        // 依赖注入等解析
        $diContainer    = Container::getInstance();
        $diContainer->c = $this->actionNamespace . '\\' . $this->actionController;
        $controller     = $diContainer->c;
        $method         = $this->actionMethod;
        $params         = isset($routeInfo[2]) ? array_values($routeInfo[2]) : [];
        try {
            return $controller->$method(...$params);
        } catch (\Exception $e) {
            return Response::error(StatusCode::CODE_UNAUTHORIZED, $e->getMessage());
        }
    }

    public function getDispatcher()
    {
        return self::$dispatcher;
    }

    private function addGroup($attributes, $callback)
    {
        $this->prefix     = isset($attributes['prefix']) ? strval($attributes['prefix']) : '';
        $this->namespace  = isset($attributes['namespace']) ? strval($attributes['namespace']) : '';
        $this->middleware = isset($attributes['middleware']) ? (array) $attributes['middleware'] : [];
        call_user_func($callback);
    }

    private function addRoutes($method, $url, $action, $middleware = [])
    {
        $actionLast           = [];
        $actionLast['action'] = $action;
        if ($this->namespace) {
            $actionLast['namespace'] = $this->namespace;
        } else {
            $actionLast['namespace'] = '\App\Http\\';
        }
        if ($this->middleware) {
            $actionLast['middleware'] = $this->middleware;
        }
        if (is_array($middleware) && !empty($middleware)) {
            $actionLast['middleware'] = array_merge($actionLast['middleware'], $middleware);
        }
        self::$routes->addRoute(strtoupper($method), $this->prefix . $url, $actionLast);
    }

    private function addRoute($method, $url, $action, $middleware = [])
    {
        $actionLast = [];
        if (is_string($action)) {
            // 判断命名空间
            $action    = str_replace('/', '\\', $action);
            $firstWord = substr($action, 0, 1);
            $rNum      = strripos($action, '\\');
            if ($rNum) {
                $actionLast['namespace'] = $firstWord === '\\' ? substr($action, 0, $rNum) : '\App\Http\\' . substr($action, 0, $rNum);
                $actionLast['action']    = substr($action, $rNum + 1);
            } else {
                $actionLast['namespace'] = '\App\Http\\';
                $actionLast['action']    = $action;
            }
        } else {
            $actionLast['action'] = $action;
        }
        if (is_array($middleware) && !empty($middleware)) {
            $actionLast['middleware'] = $middleware;
        }
        self::$routes->addRoute(strtoupper($method), $url, $actionLast);
    }

    private function checkCsrfToken()
    {
        $excepts = \App\Http\Middleware\VerifyCsrfToken::getInstance()->except;
        if (!empty($excepts) && in_array(Request::getPathInfo(), $excepts)) {
            return true;
        }

        // 需要进行csrf_token验证
        if (\FwSwoole\Component\Session::get('csrf_token') !== Request::getCsrfToken()) {
            return false;
        }
        return true;
    }

    private function checkRouteMiddleware($middlewares)
    {
        $result = true;
        foreach ($middlewares as $middleware) {
            $routeMiddleware = Kernel::getInstance()->routeMiddleware;
            if (!isset($routeMiddleware[$middleware])) {
                throw new \Exception("Error not exist {$middleware}");
            }
            $result = (new $routeMiddleware[$middleware])->handle();
            if ($result !== true) {
                break;
            }
        }
        return $result;
    }
}
