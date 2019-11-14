<?php

namespace Ububs\Core\Component\Log;

use Ububs\Core\Component\Factory;

class Log extends Factory
{
    public static function info(string $data)
    {
        \file_put_contents(config('app.log_file'), $data);
    }

    public static function error(string $data)
    {
        \file_put_contents(config('app.log_file'), $data);
    }
}
