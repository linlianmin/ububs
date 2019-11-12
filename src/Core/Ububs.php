<?php

namespace Ububs\Core;

use FwSwoole\Component\Db\Db;

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
    }

    public function runDb($action, $params)
    {
        Db::getInstance()->$action();
    }
}
