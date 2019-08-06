<?php

namespace SwooleKit\CoroDatabase;

use EasySwoole\Component\Pool\Exception\PoolEmpty;
use EasySwoole\Component\Pool\Exception\PoolException;
use SwooleKit\CoroDatabase\CoMysql\CoMysqlPool;
use SwooleKit\CoroDatabase\CoMysql\CoMysqlQuery;

/**
 * 数据库操作入口
 * Class CoMysql
 * @package SwooleKit\CoroDatabase
 * @mixin CoMysqlQuery
 */
class CoMysql
{
    /**
     * 数据库配置
     * 请在服务启动前注册配置(子进程共用)
     * 启动后请勿修改相关的配置项
     * @var CoMysqlConfig
     */
    private static $coMysqlConfig;

    /**
     * 设置数据库配置项
     * @param CoMysqlConfig $coMysqlConfig
     * @throws CoMysqlException
     */
    public static function setMysqlConfig(CoMysqlConfig $coMysqlConfig)
    {
        Helper::inSwooleEnv();
        self::$coMysqlConfig = $coMysqlConfig;
    }

    /**
     * 获取数据库配置项
     * @return CoMysqlConfig
     * @throws CoMysqlException
     */
    public static function getMsqlConfig()
    {
        // 如果存在配置项则直接返回 否则抛出异常
        if (self::$coMysqlConfig instanceof CoMysqlConfig) {
            return self::$coMysqlConfig;
        }
        throw new CoMysqlException("The database configuration has not been set, call 'CoMysql::setMysqlConfig' in the global phase to initialize the database configuration");
    }

    /**
     * 获取一条连接
     * @return mixed|null
     * @throws CoMysqlException
     * @throws PoolEmpty
     * @throws PoolException
     */
    public static function getConnection()
    {
        $poolTimeout = CoMysql::getMsqlConfig()->getPoolFetchTimeOut();
        $poolConnection = CoMysqlPool::defer($poolTimeout);
        return $poolConnection;
    }

    /**
     * 调用查询类方法
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws CoMysqlException
     * @throws PoolEmpty
     * @throws PoolException
     */
    public static function __callStatic($name, $arguments)
    {
        $queryObject = new CoMysqlQuery(CoMysql::getConnection());
        return call_user_func_array([$queryObject, $name], $arguments);
    }
}