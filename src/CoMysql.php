<?php

namespace SwooleKit\CoroDatabase;

use Swoole\Coroutine\MySQL as CoMySQLClient;
use Throwable;

/**
 * DAO操作类
 * Class CoMysql
 * @package SwooleKit\CoroDatabase
 */
class CoMysql
{
    /**
     * 数据库配置项
     * @var CoMysqlConfig
     */
    protected $coMysqlConfig;

    /**
     * 数据库客户端
     * @var CoMySQLClient
     */
    protected $coMysqlClient;

    // ----------------------------
    // 以下为本类的状态储存
    // ----------------------------

    protected $lastQuery = '';          // 最后执行的查询
    protected $reconnectTryTimes = 0;   // 已尝试的重连次数

    /**
     * 数据库架构函数
     * CoMysql constructor.
     * @param CoMysqlConfig $coMysqlConfig
     */
    public function __construct(CoMysqlConfig $coMysqlConfig)
    {
        $this->coMysqlConfig = $coMysqlConfig;
        $this->coMysqlClient = new CoMySQLClient();
    }

    /**
     * 连接数据库服务器
     * 当前没有连接上或要求强制重连时才执行连接
     * @param bool $forceReconnect 强制重新连接
     * @return bool 是否连接成功
     * @throws Throwable
     */
    protected function connectMysqlServer($forceReconnect = false): bool
    {
        if ($this->coMysqlClient->connected !== true || $forceReconnect) {
            try {
                // 强制重连则先关闭当前链接
                if ($forceReconnect) {
                    $this->disconnectMysqlServer();
                    $this->reconnectTryTimes = 0;
                }

                // 如果连接成功则置零重连次数
                $serverInfo = $this->coMysqlConfig->getSwooleClientConfig();
                if ($this->coMysqlClient->connect($serverInfo)) {
                    $this->reconnectTryTimes = 0;
                    return true;
                }

                // 没有超出最大重试次数则再次尝试
                if ($this->reconnectTryTimes < $this->coMysqlConfig->getConnectMaxRetryTimes()) {
                    $this->reconnectTryTimes++;
                    $this->connectMysqlServer();
                }

                // 多次链接仍然失败则抛出异常
                $error = $this->coMysqlClient->connect_error ?? $this->coMysqlClient->error;
                $errno = $this->coMysqlClient->connect_errno ?? $this->coMysqlClient->errno;
                throw new CoMysqlException("[{$errno}] connect to {$this->coMysqlConfig->getPdoDsnString()} failed, {$error}", $errno);

            } catch (\Throwable $throwable) {
                throw $throwable;
            }
        }
        return true;
    }

    /**
     * 断开数据库服务器
     * @return bool
     */
    protected function disconnectMysqlServer(): bool
    {
        if ($this->coMysqlClient->connected) {
            $this->coMysqlClient->close();
        }
        return true;
    }

    /**
     * 执行未经预处理的语句
     * 请勿用该方法执行生产SQL语句 尤其是使用了用户输入的语句
     * 直接执行的语句并未经过转义或参数绑定 极有可能导致注入攻击
     * @param string $query 需要执行的查询语句
     * @param int $timeout 为语句单独设置超时(默认一直等待)
     * @return array
     * @throws Throwable
     */
    public function queryUnprepared($query, $timeout = null)
    {
        $this->connectMysqlServer();
        try {
            $this->lastQuery = $query;
            $retval = $this->coMysqlClient->query($query, $this->_executeTimeout($timeout));
            if ($retval === false) {
                $errno = $this->coMysqlClient->errno;
                $error = $this->coMysqlClient->error;
                throw new CoMysqlException(sprintf('[%u] Unprepared Query Error: %s with statement -> %s', $errno, $error, $query));
            }
            return $retval;
        } catch (Throwable $throwable) {
            throw $throwable;
        }
    }

    /**
     * 获取数据库配置
     * @return CoMysqlConfig
     */
    public function getCoMysqlConfig(): CoMysqlConfig
    {
        return $this->coMysqlConfig;
    }

    /**
     * 获取数据库客户端
     * @return CoMySQLClient
     */
    public function getCoMysqlClient(): CoMySQLClient
    {
        return $this->coMysqlClient;
    }

    /**
     * 计算查询超时
     * @param float|null $timeout
     * @return float
     */
    private function _executeTimeout(?float $timeout = null): float
    {
        if ($timeout == null) {
            $timeout = $this->coMysqlConfig->getExecuteTimeOut();
            if ($timeout == null) {
                $timeout = -1;
            }
        }
        return $timeout;
    }
}