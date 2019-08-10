<?php

namespace SwooleKit\CoroDatabase;

use EasySwoole\Component\Pool\Exception\PoolEmpty;
use EasySwoole\Component\Pool\Exception\PoolException;
use SwooleKit\CoroDatabase\CoMysql\CoMysqlConnection;
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
     * @return CoMysqlConnection|null
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
     * 字符串命名风格转换
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param string $name 字符串
     * @param integer $type 转换类型
     * @param bool $ucfirst 首字母是否大写（驼峰规则）
     * @return string
     */
    public static function parseName($name, $type = 0, $ucfirst = true)
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);
            return $ucfirst ? ucfirst($name) : lcfirst($name);
        } else {
            return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
        }
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