<?php
return [
    'server_type'             => 'swoole_http_server',
    'swoole_callback_client'  => '\App\Http\Client',
    'swoole_http_server'      => [
        'host'                     => '0.0.0.0',
        'port'                     => '9501',
        'worker_num'               => 2,
        'daemonize'                => true,
        'max_request'              => 10000,
        'log_file'                 => '/tmp/swoole.log',
        // 抢占模式
        'dispatch_mode'            => 3,
        'debug_mode'               => 1,
        'task_worker_num'          => 2,
        // 心跳检查间隔时间
        'heartbeat_check_interval' => 100,
        // 连接最大的空闲时间
        'heartbeat_idle_time'      => 300,
    ],
    'swoole_websocket_server' => [],
    'log_file' => '/tmp/ububs.log'
];
