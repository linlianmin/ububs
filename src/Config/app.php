<?php
return [
    'server_type'             => 'HTTP_SERVER',
    // 回调
    'callback_client'         => '\App\Http\Client',
    'server'                  => [
        'host'                     => '0.0.0.0',
        'port'                     => '9502',
        'worker_num'               => 2,
        'daemonize'                => false,
        'max_request'              => 10000,
        'log_file'                 => '/tmp/swoole.log',
        // 抢占模式
        'dispatch_mode'            => 2,
        'debug_mode'               => 1,
        'task_worker_num'          => 2,
        // 心跳检查间隔时间
        'heartbeat_check_interval' => 100,
        // 连接最大的空闲时间
        'heartbeat_idle_time'      => 300,
        'http'                     => false,
        'sock_type'                => [SWOOLE_SOCK_TCP],
        'mode'                     => SWOOLE_PROCESS,
        'ssl_cert_file'            => '',
        'ssl_key_file'             => '',

    ],
    'swoole_websocket_server' => [],
    'log_file'                => '/tmp/ububs.log',

];
