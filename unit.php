<?php

require_once 'vendor/autoload.php';

use SwooleKit\CoroDatabase\CoMysql;
use SwooleKit\CoroDatabase\CoMysqlConfig;

// 数据库配置项
$coMysqlConfig = new CoMysqlConfig([
    'hostname' => '127.0.0.1',
    'hostport' => '3306',
    'username' => 'root',
    'password' => 'dd199071',
    'database' => 'mysql',
    'charset'  => 'utf8mb4',
    'prefix'   => 'v9_'
]);

try {
    CoMysql::setMysqlConfig($coMysqlConfig);
    $scheduler = new \Swoole\Coroutine\Scheduler;
    $scheduler->add(function () {
        $tables = CoMysql::query('show tables;');
//        var_dump($tables);
    });
    $scheduler->start();
} catch (Throwable $throwable) {
    var_dump($throwable->getMessage());
}