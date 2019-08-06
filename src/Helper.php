<?php

namespace SwooleKit\CoroDatabase;

/**
 * 助手类函数
 * Class Helper
 * @package SwooleKit\CoroDatabase
 */
class Helper
{
    /**
     * 当前是否在Swoole环境中
     * @throws CoMysqlException
     */
    public static function inSwooleEnv()
    {
        // 是否安装了Swoole扩展
        if (!extension_loaded('swoole')) {
            throw new CoMysqlException('Swoole extension not detected');
        }

        // 版本必须大于4.3.0
        if (version_compare(phpversion('swoole'), '4.3.0', '<')) {
            throw new CoMysqlException('Swoole extension not detected');
        }

        return true;
    }
}