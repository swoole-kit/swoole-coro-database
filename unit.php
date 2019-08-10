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
    'database' => 'yy_gxfybjcom',
    'charset'  => 'utf8mb4',
    'prefix'   => 'v9_'
]);

try {
    CoMysql::setMysqlConfig($coMysqlConfig);
    \Swoole\Coroutine::create(function () {

        \Swoole\Coroutine::create(function () {
            $query = new CoMysql\CoMysqlQuery;
            $res = $query->getTableFields('v9_log');
            var_dump($res);
        });

    });
} catch (Throwable $throwable) {
    var_dump($throwable->getMessage());
}