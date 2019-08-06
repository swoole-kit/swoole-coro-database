<?php

namespace SwooleKit\CoroDatabase\CoMysql;

use EasySwoole\Component\Pool\AbstractPool;
use SwooleKit\CoroDatabase\CoMysql;
use SwooleKit\CoroDatabase\CoMysqlException;

/**
 * 协程连接池
 * Class CoMysqlPool
 * @package SwooleKit\CoroDatabase\CoMysqlPool
 */
class CoMysqlPool extends AbstractPool
{
    /**
     * 创建一个连接
     * @return CoMysql\CoMysqlConnection
     * @throws CoMysqlException
     */
    protected function createObject()
    {
        $coMysqlConfig = CoMysql::getMsqlConfig();
        return new CoMysql\CoMysqlConnection($coMysqlConfig);
    }
}