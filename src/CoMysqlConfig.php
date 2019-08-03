<?php

namespace SwooleKit\CoroDatabase;

use EasySwoole\Spl\SplBean;

/**
 * 数据库配置
 * Class CoMysqlConfig
 * @package SwooleKit\CoroDatabase
 */
class CoMysqlConfig extends SplBean
{
    // 数据库配置
    protected $hostname;  // 数据库地址
    protected $hostport;  // 数据库端口
    protected $username;  // 数据库用户
    protected $password;  // 数据库密码
    protected $database;  // 数据库名称
    protected $charset;   // 字符集设置
    protected $prefix;    // 表前缀名称

    // 链接设置
    protected $fetchMode = false;   // 允许以POD方式使用fetch/fetchAll方法
    protected $strictMode = false;  // 严格模式下返回值也将转为强类型

    // 超时设置
    protected $connectTimeOut = -1;  // 开启连接超时
    protected $executeTimeOut = -1;  // 查询执行超时

    // 其他设置
    protected $connectMaxRetryTimes = 1; // 尝试重连次数

    /**
     * 转为Swoole客户端配置
     * @return array
     */
    public function getSwooleClientConfig(): array
    {
        return [
            'host'        => $this->hostname ?? 'localhost',
            'user'        => $this->username ?? 'root',
            'port'        => $this->hostport ?? '3306',
            'charset'     => $this->charset ?? 'utf8',
            'password'    => $this->password ?? '',
            'database'    => $this->database ?? '',
            'strict_type' => $this->strictMode,
            'fetch_mode'  => $this->fetchMode
        ];
    }

    /**
     * 转为PDO连接字符串
     * @return string
     */
    public function getPdoDsnString(): string
    {
        $defaultConfig = $this->getSwooleClientConfig();
        $connectDsn = "mysql://{$defaultConfig['user']}@{$defaultConfig['host']}:{$defaultConfig['port']}/{$defaultConfig['database']}";
        return $connectDsn;
    }

    /**
     * Hostname Getter
     * @return mixed
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Hostname Setter
     * @param mixed $hostname
     * @return CoMysqlConfig
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * Hostport Getter
     * @return mixed
     */
    public function getHostport()
    {
        return $this->hostport;
    }

    /**
     * Hostport Setter
     * @param mixed $hostport
     * @return CoMysqlConfig
     */
    public function setHostport($hostport)
    {
        $this->hostport = $hostport;
        return $this;
    }

    /**
     * Username Getter
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Username Setter
     * @param mixed $username
     * @return CoMysqlConfig
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Password Getter
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Password Setter
     * @param mixed $password
     * @return CoMysqlConfig
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Database Getter
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Database Setter
     * @param mixed $database
     * @return CoMysqlConfig
     */
    public function setDatabase($database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Charset Getter
     * @return mixed
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Charset Setter
     * @param mixed $charset
     * @return CoMysqlConfig
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Prefix Getter
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Prefix Setter
     * @param mixed $prefix
     * @return CoMysqlConfig
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * FetchMode Getter
     * @return bool
     */
    public function isFetchMode(): bool
    {
        return $this->fetchMode;
    }

    /**
     * FetchMode Setter
     * @param bool $fetchMode
     * @return CoMysqlConfig
     */
    public function setFetchMode(bool $fetchMode): CoMysqlConfig
    {
        $this->fetchMode = $fetchMode;
        return $this;
    }

    /**
     * StrictMode Getter
     * @return bool
     */
    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    /**
     * StrictMode Setter
     * @param bool $strictMode
     * @return CoMysqlConfig
     */
    public function setStrictMode(bool $strictMode): CoMysqlConfig
    {
        $this->strictMode = $strictMode;
        return $this;
    }

    /**
     * ConnectTimeOut Getter
     * @return int
     */
    public function getConnectTimeOut(): int
    {
        return $this->connectTimeOut;
    }

    /**
     * ConnectTimeOut Setter
     * @param int $connectTimeOut
     * @return CoMysqlConfig
     */
    public function setConnectTimeOut(int $connectTimeOut): CoMysqlConfig
    {
        $this->connectTimeOut = $connectTimeOut;
        return $this;
    }

    /**
     * ExecuteTimeOut Getter
     * @return int
     */
    public function getExecuteTimeOut(): int
    {
        return $this->executeTimeOut;
    }

    /**
     * ExecuteTimeOut Setter
     * @param int $executeTimeOut
     * @return CoMysqlConfig
     */
    public function setExecuteTimeOut(int $executeTimeOut): CoMysqlConfig
    {
        $this->executeTimeOut = $executeTimeOut;
        return $this;
    }

    /**
     * ConnectMaxRetryTimes Getter
     * @return int
     */
    public function getConnectMaxRetryTimes(): int
    {
        return $this->connectMaxRetryTimes;
    }

    /**
     * ConnectMaxRetryTimes Setter
     * @param int $connectMaxRetryTimes
     * @return CoMysqlConfig
     */
    public function setConnectMaxRetryTimes(int $connectMaxRetryTimes): CoMysqlConfig
    {
        $this->connectMaxRetryTimes = $connectMaxRetryTimes;
        return $this;
    }
}