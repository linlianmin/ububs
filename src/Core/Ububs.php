<?php

namespace Ububs\Core;

use Ububs\Core\Swoole\Server\ServerManager;
use FwSwoole\Component\Db\Db;
use Ububs\Core\Swoole\Event\EventManager;

class Ububs
{
    const TYPE_SERVER = 'SERVER';
    const TYPE_DB     = 'DB';

    public function run($type, $action, $params)
    {
        switch ($type) {
            case self::TYPE_SERVER:
                $this->runServer($action, $params);
                break;

            case self::TYPE_DB:
                $this->runDb($action, $params);
                break;
        }
    }
    public function runServer($action, $params)
    {
        ServerManager::getInstance()->$action();
        EventManager::getInstance()->parseCommand($action, $params);
    }

    public function runDb($action, $params)
    {
        Db::getInstance()->$action();
    }
}
