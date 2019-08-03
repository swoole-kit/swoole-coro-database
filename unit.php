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

/**
 * 创建协程执行测试逻辑
 */
\Swoole\Coroutine::create(function () use ($coMysqlConfig) {
    try {
        $dbConnect = new CoMysql($coMysqlConfig);
    } catch (Throwable $throwable) {
        echo PHP_EOL . $throwable->getMessage() . PHP_EOL . PHP_EOL;
    }
});