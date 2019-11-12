<?php
namespace Ububs\Core\Swoole\Task;

use Ububs\Core\Swoole\Factory;
use Ububs\Core\Swoole\Server\ServerManager;

class TaskManager extends Factory
{

    const SERVER_TYPE = 'HTTP_SERVER';

    public function task($name, $callback, $taskId = -1)
    {
        return ServerManager::getServer(self::SERVER_TYPE)->task($name, $taskId, $callback);
    }

}
