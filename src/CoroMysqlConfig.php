<?php
/**
 * Created by PhpStorm.
 * User: evalor
 * Date: 2019-04-04
 * Time: 13:40
 */

namespace SwooleKit\CoroDatabase;

/**
 * 协程Mysql数据库配置
 * Class CoroMysqlConfig
 * @package evalor\CoroDatabase
 */
class CoroMysqlConfig
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
    protected $isSubQuery = false;    // 是否子查询
    protected $connectMaxRetryTimes = 1; // 尝试重连次数

    /**
     * 转换为Swoole客户端的配置项格式
     * @return array
     */
    public function convertToClientConfig()
    {
        if (is_null($this->charset)) $this->charset = 'utf8';
        if (is_null($this->hostname)) $this->hostname = 'localhost';
        if (is_null($this->username)) $this->username = 'root';
        if (is_null($this->hostport)) $this->hostport = '3306';
        if (is_null($this->password)) $this->password = '';
        if (is_null($this->database)) $this->database = '';

        $clientConfig = [
            'host'        => $this->hostname,
            'user'        => $this->username,
            'port'        => $this->hostport,
            'charset'     => $this->charset,
            'password'    => $this->password,
            'database'    => $this->database,
            'strict_type' => $this->strictMode,
            'fetch_mode'  => $this->fetchMode
        ];
        return $clientConfig;
    }

    /**
     * HostnameGetter
     * @return mixed
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * HostnameSetter
     * @param mixed $hostname
     * @return CoroMysqlConfig
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * HostportGetter
     * @return mixed
     */
    public function getHostport()
    {
        return $this->hostport;
    }

    /**
     * HostportSetter
     * @param mixed $hostport
     * @return CoroMysqlConfig
     */
    public function setHostport($hostport)
    {
        $this->hostport = $hostport;
        return $this;
    }

    /**
     * UsernameGetter
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * UsernameSetter
     * @param mixed $username
     * @return CoroMysqlConfig
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * PasswordGetter
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * PasswordSetter
     * @param mixed $password
     * @return CoroMysqlConfig
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * DatabaseGetter
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * DatabaseSetter
     * @param mixed $database
     * @return CoroMysqlConfig
     */
    public function setDatabase($database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * CharsetGetter
     * @return mixed
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * CharsetSetter
     * @param mixed $charset
     * @return CoroMysqlConfig
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * PrefixGetter
     * @return mixed
     */
    public function getPrefix()
    {
        return empty($this->prefix) ? '' : $this->prefix;
    }

    /**
     * PrefixSetter
     * @param mixed $prefix
     * @return CoroMysqlConfig
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * FetchModeGetter
     * @return bool
     */
    public function isFetchMode()
    {
        return $this->fetchMode;
    }

    /**
     * FetchModeSetter
     * @param bool $fetchMode
     * @return CoroMysqlConfig
     */
    public function setFetchMode($fetchMode)
    {
        $this->fetchMode = $fetchMode;
        return $this;
    }

    /**
     * StrictModeGetter
     * @return bool
     */
    public function isStrictMode()
    {
        return $this->strictMode;
    }

    /**
     * StrictModeSetter
     * @param bool $strictMode
     * @return CoroMysqlConfig
     */
    public function setStrictMode($strictMode)
    {
        $this->strictMode = $strictMode;
        return $this;
    }

    /**
     * ConnectTimeOutGetter
     * @return int
     */
    public function getConnectTimeOut()
    {
        return $this->connectTimeOut;
    }

    /**
     * ConnectTimeOutSetter
     * @param int $connectTimeOut
     * @return CoroMysqlConfig
     */
    public function setConnectTimeOut($connectTimeOut)
    {
        $this->connectTimeOut = $connectTimeOut;
        return $this;
    }

    /**
     * ExecuteTimeOutGetter
     * @return int
     */
    public function getExecuteTimeOut()
    {
        return $this->executeTimeOut;
    }

    /**
     * ExecuteTimeOutSetter
     * @param int $executeTimeOut
     * @return CoroMysqlConfig
     */
    public function setExecuteTimeOut($executeTimeOut)
    {
        $this->executeTimeOut = $executeTimeOut;
        return $this;
    }

    /**
     * isSubQueryGetter
     * @return bool
     */
    public function isSubQuery()
    {
        return $this->isSubQuery;
    }

    /**
     * IsSubQuerySetter
     * @param bool $isSubQuery
     * @return CoroMysqlConfig
     */
    public function setIsSubQuery($isSubQuery)
    {
        $this->isSubQuery = $isSubQuery;
        return $this;
    }

    /**
     * ConnectMaxRetryTimesGetter
     * @return int
     */
    public function getConnectMaxRetryTimes(): int
    {
        return $this->connectMaxRetryTimes;
    }

    /**
     * ConnectMaxRetryTimesSetter
     * @param int $connectMaxRetryTimes
     * @return CoroMysqlConfig
     */
    public function setConnectMaxRetryTimes(int $connectMaxRetryTimes): CoroMysqlConfig
    {
        $this->connectMaxRetryTimes = $connectMaxRetryTimes;
        return $this;
    }

}