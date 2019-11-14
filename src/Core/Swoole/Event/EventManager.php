<?php
namespace Ububs\Core\Swoole\Event;

use Ububs\Core\Swoole\Factory;
use Ububs\Core\Swoole\Server\ServerManager;

class EventManager extends Factory
{
    private static $registerEvents = [
        'HTTP_SERVER'      => [
            'BeforeStart' => 'beforeStart',
            'Start'       => 'onStart',
            'WorkerStart' => 'onWorkerStart',
            'WorkerError' => 'onWorkerError',
            'Request'     => 'onRequest',
            'Task'        => 'onTask',
            'Finish'      => 'onFinish',
        ],
        'SWOOLE_SERVER'    => [],
        'WEBSOCKET_SERVER' => [
            'BeforeStart' => 'beforeStart',
            'Start'       => 'onStart',
            'WorkerStart' => 'onWorkerStart',
            'WorkerError' => 'onWorkerError',
            'Request'     => 'onRequest',
            'Task'        => 'onTask',
            'Finish'      => 'onFinish',
            'Open'        => 'onOpen',
            'Message'     => 'onMessage',
            'Close'       => 'onClose',
        ],
    ];

    public static function addEventListener()
    {
        $type   = ServerManager::getServerType();
        $events = self::$registerEvents[$type] ?? [];
        if ($type === 'WEBSOCKET_SERVER' && !config('app.server')['http']) {
            unset($events['Request']);
        }
        if (!empty($events)) {
            $server   = ServerManager::getServer();
            $instance = ServerManager::getCallbackClient();
            foreach ($events as $event => $callback) {
                if ($event === 'Request') {
                    $server->on($event, [ServerManager::getServerInstance(), $callback]);
                }
                if (!method_exists($instance, $callback)) {
                    continue;
                }
                if ($event === 'BeforeStart') {
                    $instance->$callback($server);
                } else {
                    $server->on($event, [$instance, $callback]);
                }
            }
        }
    }
}
