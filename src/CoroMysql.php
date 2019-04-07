<?php
/**
 * Created by PhpStorm.
 * User: evalor
 * Date: 2019-04-04
 * Time: 17:40
 */

namespace SwooleKit\CoroDatabase;

use Swoole\Coroutine\MySQL as CoroMysqlClient;
use SwooleKit\CoroDatabase\Exception\ConnectException;
use SwooleKit\CoroDatabase\Exception\CoroDBException;
use SwooleKit\CoroDatabase\Exception\QueryException\EmptyConditionException;
use SwooleKit\CoroDatabase\Exception\QueryException\ExecuteQueryException;
use SwooleKit\CoroDatabase\Exception\QueryException\JoinTypeException;
use SwooleKit\CoroDatabase\Exception\QueryException\LockFailedException;
use SwooleKit\CoroDatabase\Exception\QueryException\LockTypeException;
use SwooleKit\CoroDatabase\Exception\QueryException\OrderDirectionException;
use SwooleKit\CoroDatabase\Exception\QueryException\OrderRegexException;
use SwooleKit\CoroDatabase\Exception\QueryException\PrepareQueryException;
use SwooleKit\CoroDatabase\Exception\QueryException\QueryException;
use SwooleKit\CoroDatabase\Exception\QueryException\QueryTimeoutException;
use SwooleKit\CoroDatabase\Exception\QueryException\TableNotSetException;
use SwooleKit\CoroDatabase\Exception\QueryException\UnlockException;
use SwooleKit\CoroDatabase\Exception\QueryException\WrongOperationException;
use SwooleKit\CoroDatabase\Exception\QueryException\WrongOptionException;

/**
 * PHP-MySQLi-Database-Class for Swoole CoroMysql
 * Making this library support the use of 'Swoole Coroutine' environment
 *
 * @category  Database Access
 * @package   CoroMysql
 * @author    Jeffery Way <jeffrey@jeffrey-way.com>
 * @author    Josh Campbell <jcampbell@ajillion.com>
 * @author    Alexander V. Butenko <a.butenka@gmail.com>
 * @author    eValor <mipone@foxmail.com>
 * @copyright Copyright (c) 2010-2017
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link      http://github.com/joshcam/PHP-MySQLi-Database-Class
 * @link      http://github.com/ThingEngineer/PHP-MySQLi-Database-Class
 * @link      http://github.com/evalor/swoole-coro-database
 * @version   2.9.2 / R 1.0.0
 */
class CoroMysql
{
    protected $coroMysqlConfig;         // 数据库配置项
    protected $coroMysqlClient;         // 数据库客户端

    // 以下为状态储存
    protected $affectRows;              // 影响的行数
    protected $totalCount;              // 总共的行数
    protected $stmtError;               // 语句错误文本
    protected $stmtErrno;               // 语句错误码
    protected $lastInsertId;            // 最后插入的id
    protected $inTransaction = false;   // 当前是否已启动了事务
    protected $isSubQuery = false;      // 是否子查询
    protected $reconnectTryTimes = 0;   // 重连尝试次数
    protected $lastQuery = '';          // 最后执行的查询
    protected $currentQuery = '';       // 当前执行的查询
    protected $lastStatement = null;    // 最后查询的语句

    // 以下为查询条件构造项
    protected $condJoin = [];             // Join查询条件
    protected $condWhere = [];            // Where查询条件
    protected $condHaving = [];           // Having查询条件
    protected $condGroupBy = [];          // GroupBy查询条件
    protected $condOrderBy = [];          // OrderBy查询条件
    protected $condJoinAnd = [];          // JoinAnd查询条件

    // 查询额外配置项
    protected $limit = null;              // 查询limit条件
    protected $tableName = '';            // 表名称
    protected $queryColumns = '*';        // 指定操作列
    protected $nestJoin = false;          // NESTED LOOP JOIN
    protected $forUpdate = false;         // SELECT FOR UPDATE
    protected $lockInShareMode = false;   // LOCK IN SHARE MODE
    protected $queryOptions = [];         // 查询参数
    protected $tableLockMethod = 'READ';  // 锁表方式
    protected $bindParams = [];           // 绑定的查询参数
    protected $updateColumns = [];        // 需要更新的列
    protected $tablePrefix = null;        // 当前的表前缀

    // 允许使用的优化器提示选项
    protected $allowedOptions = Array(
        'ALL', 'DISTINCT', 'DISTINCTROW', 'HIGH_PRIORITY', 'STRAIGHT_JOIN', 'SQL_SMALL_RESULT',
        'SQL_BIG_RESULT', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_NO_CACHE', 'SQL_CALC_FOUND_ROWS',
        'LOW_PRIORITY', 'IGNORE', 'QUICK', 'MYSQLI_NESTJOIN', 'FOR UPDATE', 'LOCK IN SHARE MODE'
    );

    /**
     * CoroMysql constructor.
     * @param CoroMysqlConfig $coroMysqlConfig
     */
    public function __construct(CoroMysqlConfig $coroMysqlConfig)
    {
        $this->coroMysqlConfig = $coroMysqlConfig;
        $this->isSubQuery = $coroMysqlConfig->isSubQuery();

        if ($this->isSubQuery == false) {
            $this->coroMysqlClient = new CoroMysqlClient;
        } else {
            $this->tablePrefix = $coroMysqlConfig->getPrefix();
        }
    }

    /* Connection Functions */

    /**
     * 连接到数据库
     * @return bool
     * @throws ConnectException
     * @throws \Throwable
     */
    public function connect(): bool
    {
        if ($this->isSubQuery) return true;
        if ($this->coroMysqlClient->connected !== true) {

            $connectHost = $this->coroMysqlConfig->getHostname();
            $connectPort = $this->coroMysqlConfig->getHostport();
            $connectUser = $this->coroMysqlConfig->getUsername();
            $connectDatabase = $this->coroMysqlConfig->getDatabase();
            $connectDsn = "mysql://{$connectUser}@{$connectHost}:{$connectPort}/{$connectDatabase}";

            try {
                $connected = $this->coroMysqlClient->connect($this->coroMysqlConfig->convertToClientConfig());
                if ($connected) {
                    $this->reconnectTryTimes = 0;
                    return true;
                } else {
                    if ($this->reconnectTryTimes < $this->coroMysqlConfig->getConnectMaxRetryTimes()) {
                        $this->reconnectTryTimes++;
                        $this->connect();
                    }
                    $error = $this->coroMysqlClient->connect_error == '' ? $this->coroMysqlClient->error : $this->coroMysqlClient->connect_error;
                    $errno = $this->coroMysqlClient->connect_errno == 0 ? $this->coroMysqlClient->errno : $this->coroMysqlClient->connect_errno;
                    throw new ConnectException("[{$errno}] connect to {$connectDsn} failed: {$error}");
                }
            } catch (\Throwable $throwable) {
                if ($throwable instanceof ConnectException) {
                    throw $throwable;
                } else {
                    throw new ConnectException("[{$throwable->getCode()}] connect to {$connectDsn} failed: {$throwable->getMessage()}");
                }
            }
        } else {
            return true;
        }
    }

    /**
     * 关闭数据库连接
     * @return bool|mixed
     */
    public function disconnect(): bool
    {
        if ($this->coroMysqlClient->connected) {
            return $this->coroMysqlClient->close();
        }
        return true;
    }

    /**
     * 获取当前的数据库连接
     * @return CoroMysqlClient
     * @throws ConnectException
     * @throws \Throwable
     */
    public function coroMysqlClient(): CoroMysqlClient
    {
        if (!$this->coroMysqlClient->connected) {
            $this->connect();
        }
        return $this->coroMysqlClient;
    }

    /* BaseQuery Functions */

    /**
     * 执行未经预处理的语句
     * 警告: 请勿在生产环境中使用此方法
     * 由于直接执行的语句并未经过转义或参数绑定 极有可能导致侏注入攻击
     * @param string $query 需要执行的查询语句
     * @param float|null $timeout 本次查询超时 优先级高于配置
     * @return mixed
     * @throws QueryException
     * @throws QueryTimeoutException
     */
    public function queryUnprepared($query, ?float $timeout = null): array
    {
        try {
            $this->currentQuery = $this->lastQuery = $query;
            $retval = $this->coroMysqlClient()->query($this->currentQuery, $this->_executeTimeout($timeout));
            if ($retval === false) {
                throw new QueryException;
            } else {
                return $retval;
            }
        } catch (\Throwable $throwable) {
            $query = $this->currentQuery;
            $this->reset();
            if ($throwable instanceof QueryException) {
                if ($this->coroMysqlClient->errno == 110) {
                    throw new QueryTimeoutException(sprintf('[%u] Unprepared Query TimeOut: %s with statement -> %s', $this->coroMysqlClient->errno, $this->coroMysqlClient->error, $query));
                }
                throw new QueryException(sprintf('[%u] Unprepared Query Failed: %s with statement -> %s', $this->coroMysqlClient->errno, $this->coroMysqlClient->error, $query));
            }
            throw new QueryException(sprintf('[%u] Unprepared Query Failed: %s with statement -> %s', $throwable->getCode(), $throwable->getMessage(), $query));
        }
    }

    /**
     * 进行带参数绑定的直接查询
     * @param string $query 查询语句
     * @param array $bindParams 绑定参数
     * @param float|null $timeout 查询超时(可以额外设置)
     * @return mixed|null
     * @throws ConnectException
     * @throws ExecuteQueryException
     * @throws PrepareQueryException
     * @throws QueryTimeoutException
     * @throws \Throwable
     */
    public function rawQuery($query, array $bindParams = [], ?float $timeout = null)
    {
        $this->currentQuery = $query;
        $this->bindParams = $bindParams;

        $statement = $this->_prepareQuery($timeout);
        $retval = $this->executeStatement($statement, $timeout);

        $this->affectRows = $statement->affected_rows;
        $this->lastQuery = $this->_replacePlaceHolders($this->currentQuery, $bindParams);
        $this->reset();
        return $retval;
    }

    /**
     * 返回查询到的第一条结果
     * 此方法并不会向查询语句添加 limit1 而是简单的取出第一条返回
     * @param string $query 查询语句
     * @param array $bindParams 绑定参数
     * @param float|null $timeout 查询超时(可以额外设置)
     * @return mixed|null
     * @throws ConnectException
     * @throws ExecuteQueryException
     * @throws PrepareQueryException
     * @throws QueryTimeoutException
     * @throws \Throwable
     */
    public function rawQueryOne($query, array $bindParams = [], ?float $timeout = null)
    {
        $retval = $this->rawQuery($query, $bindParams, $timeout);
        if (is_array($retval) && isset($retval[0])) {
            return $retval[0];
        }
        return null;
    }

    /* Query Setting Functions */

    /**
     * 添加查询参数
     * @param string|array $options 需要添加的查询参数
     * @return $this
     * @throws WrongOptionException
     */
    public function setQueryOption($options)
    {
        // 支持传入字符串
        if (!is_array($options)) {
            $options = array($options);
        }

        foreach ($options as $option) {
            $option = strtoupper($option);

            // 只允许传入限制列表内的参数
            if (!in_array($option, $this->allowedOptions)) {
                throw new WrongOptionException('Wrong query option: ' . $option);
            }

            // 特殊的查询选项需要特殊对待
            if ($option == 'MYSQLI_NESTJOIN') {
                $this->nestJoin = true;
            } elseif ($option == 'FOR UPDATE') {
                $this->forUpdate = true;
            } elseif ($option == 'LOCK IN SHARE MODE') {
                $this->lockInShareMode = true;
            } else {
                if (!in_array($option, $this->queryOptions)) {
                    $this->queryOptions[] = $options;
                }
            }
        }

        return $this;
    }

    /**
     * 快捷设置返回行数选项
     * @return $this
     * @throws WrongOptionException
     */
    public function withTotalCount()
    {
        $this->setQueryOption('SQL_CALC_FOUND_ROWS');
        return $this;
    }

    /**
     * 指定查询的表(不带前缀)
     * @param string $tableName
     * @return CoroMysql
     */
    public function table(?string $tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * 设置查询limit
     * @param int|array|null $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 指定需要操作的列
     * @param $columns
     * @return CoroMysql
     */
    public function field($columns)
    {
        if (is_array($columns)) {
            $this->queryColumns = implode(',', $columns);
        } else if (is_string($columns)) {
            $this->queryColumns = $columns;
        }
        return $this;
    }

    /**
     * 添加一个Where条件
     * @param string $whereProp 条件字段
     * @param string $whereValue 条件的值
     * @param string $operator 执行操作
     * @param string $cond AND/OR
     * @return $this
     */
    public function where($whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        // forkaround for an old operation api
        if (is_array($whereValue) && ($key = key($whereValue)) != "0") {
            $operator = $key;
            $whereValue = $whereValue[$key];
        }

        if (count($this->condWhere) == 0) {
            $cond = '';
        }

        $this->condWhere[] = array($cond, $whereProp, $operator, $whereValue);
        return $this;
    }

    /**
     * 添加一个WhereOr条件
     * @param string $whereProp 条件字段
     * @param string $whereValue 条件的值
     * @param string $operator 执行操作
     * @return CoroMysql
     */
    public function orWhere($whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
        return $this->where($whereProp, $whereValue, $operator, 'OR');
    }

    /**
     * 添加一个Having条件
     * @param string $havingProp 条件字段
     * @param string $havingValue 条件的值
     * @param string $operator 执行操作
     * @param string $cond AND/OR
     * @return $this
     */
    public function having($havingProp, $havingValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        // forkaround for an old operation api
        if (is_array($havingValue) && ($key = key($havingValue)) != "0") {
            $operator = $key;
            $havingValue = $havingValue[$key];
        }

        if (count($this->condHaving) == 0) {
            $cond = '';
        }

        $this->condHaving[] = array($cond, $havingProp, $operator, $havingValue);
        return $this;
    }

    /**
     * 添加一个HavingOr条件
     * @param string $havingProp 条件字段
     * @param string $havingValue 条件的值
     * @param string $operator 执行操作
     * @return CoroMysql
     */
    public function orHaving($havingProp, $havingValue = null, $operator = null)
    {
        return $this->having($havingProp, $havingValue, $operator, 'OR');
    }

    /**
     * 添加一个Join条件
     * @param string $joinTable 需要连接的表
     * @param string $joinCondition 连接的条件
     * @param string $joinType 连接类型
     * @return CoroMysql
     * @throws JoinTypeException
     */
    public function join($joinTable, $joinCondition, $joinType = '')
    {
        $allowedTypes = array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER', 'NATURAL');
        $joinType = strtoupper(trim($joinType));

        if ($joinType && !in_array($joinType, $allowedTypes)) {
            throw new JoinTypeException('Wrong JOIN type: ' . $joinType);
        }

        if (!is_object($joinTable)) {
            $joinTable = $this->coroMysqlConfig->getPrefix() . $joinTable;
        }

        $this->condJoin[] = Array($joinType, $joinTable, $joinCondition);

        return $this;
    }

    /**
     * 添加一个JoinWhere条件
     * @param string $whereJoin 需要连接的表
     * @param string $whereProp 需要查询的字段
     * @param string $whereValue 查询的值
     * @param string $operator 执行的操作
     * @param string $cond AND/OR
     * @return CoroMysql
     */
    public function joinWhere($whereJoin, $whereProp, $whereValue = 'DBNULL', $operator = '=', $cond = 'AND')
    {
        $this->condJoinAnd[$this->coroMysqlConfig->getPrefix() . $whereJoin][] = Array($cond, $whereProp, $operator, $whereValue);
        return $this;
    }

    /**
     * 添加一个JoinWhereOr条件
     * @param string $whereJoin 需要连接的表
     * @param string $whereProp 需要查询的字段
     * @param string $whereValue 查询的值
     * @param string $operator 执行的操作
     * @return CoroMysql
     */
    public function joinOrWhere($whereJoin, $whereProp, $whereValue = 'DBNULL', $operator = '=')
    {
        return $this->joinWhere($whereJoin, $whereProp, $whereValue, $operator, 'OR');
    }

    /**
     * 添加一个OrderBy条件
     * @param string $orderByField 排序字段
     * @param string $orderbyDirection 排序方向
     * @param null|string $customFieldsOrRegExp 自定义排序表达式
     * @return $this
     * @throws OrderDirectionException
     * @throws OrderRegexException
     */
    public function orderBy($orderByField, $orderbyDirection = "DESC", $customFieldsOrRegExp = null)
    {
        $allowedDirection = Array("ASC", "DESC");
        $orderbyDirection = strtoupper(trim($orderbyDirection));
        $orderByField = preg_replace("/[^ -a-z0-9\.\(\),_`\*\'\"]+/i", '', $orderByField);

        // Add table prefix to orderByField if needed.
        // 我们只在表被括在``中时添加前缀，以区分别名和表名
        $orderByField = preg_replace('/(\`)([`a-zA-Z0-9_]*\.)/', '\1' . $this->coroMysqlConfig->getPrefix() . '\2', $orderByField);

        if (empty($orderbyDirection) || !in_array($orderbyDirection, $allowedDirection)) {
            throw new OrderDirectionException('Wrong order direction: ' . $orderbyDirection);
        }

        if (is_array($customFieldsOrRegExp)) {
            foreach ($customFieldsOrRegExp as $key => $value) {
                $customFieldsOrRegExp[$key] = preg_replace("/[^\x80-\xff-a-z0-9\.\(\),_` ]+/i", '', $value);
            }
            $orderByField = 'FIELD (' . $orderByField . ', "' . implode('","', $customFieldsOrRegExp) . '")';
        } elseif (is_string($customFieldsOrRegExp)) {
            $orderByField = $orderByField . " REGEXP '" . $customFieldsOrRegExp . "'";
        } elseif ($customFieldsOrRegExp !== null) {
            throw new OrderRegexException('Wrong custom field or Regular Expression: ' . $customFieldsOrRegExp);
        }

        $this->condOrderBy[$orderByField] = $orderbyDirection;
        return $this;
    }

    /**
     * 添加一个GroupBy条件
     * @param string $groupByField
     * @return $this
     */
    public function groupBy($groupByField)
    {
        $groupByField = preg_replace("/[^-a-z0-9\.\(\),_\* <>=!]+/i", '', $groupByField);

        $this->condGroupBy[] = $groupByField;
        return $this;
    }

    /**
     * 发起onDuplicate查询
     * @param $updateColumns
     * @param null $lastInsertId
     * @return $this
     */
    public function onDuplicate($updateColumns, $lastInsertId = null)
    {
        $this->lastInsertId = $lastInsertId;
        $this->updateColumns = $updateColumns;
        return $this;
    }

    /* Query External Functions */

    /**
     * 执行一次 SELECT 查询
     * 修改了方法名称对于TP的玩家更加友好
     * @param null|string $table
     * @param null|int $limit
     * @param null $fields
     * @return CoroMysql|mixed
     * @throws ConnectException
     * @throws PrepareQueryException
     * @throws TableNotSetException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    public function select($table = null, $limit = null, $fields = null)
    {
        $this->_parserTableName($table);
        if (!is_null($limit)) $this->limit($limit);
        if (!is_null($fields)) $this->field($fields);
        $this->currentQuery = 'SELECT ' . implode(' ', $this->queryOptions) . ' ' . $this->queryColumns . " FROM " . $this->tableName;
        $stmt = $this->_buildQuery($this->limit);

        if ($this->isSubQuery) {
            return $this;
        }

        try {
            $retval = $this->executeStatement($stmt);
            $this->affectRows = $stmt->affected_rows;
            return $retval;
        } catch (\Throwable $throwable) {
            throw $throwable;
        } finally {
            $this->reset();
        }
    }

    /**
     * 执行一次 SELECT LIMIT 1 查询
     * 修改了方法名称对于TP的玩家更加友好
     * 这是select方法的一个语法糖
     * @param null|string $table
     * @param null|string|array $columns
     * @return CoroMysql|mixed|null
     * @throws PrepareQueryException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    public function find($table = null, $columns = null)
    {
        $retval = $this->select($table, 1, $columns);
        if ($retval instanceof CoroMysql) {
            return $retval;
        } elseif (is_array($retval) && isset($retval[0])) {
            return $retval[0];
        } elseif ($retval) {
            return $retval;
        }
        return null;
    }

    /**
     * 获取某一列的数据
     * 这是select方法的一个语法糖
     * @param string $columnName 需要获取的列名称
     * @param null|string $table 需要获取的表名称
     * @param null|int $limit 限制获取的记录数
     * @param string|null $arrayKey 可以指定以某个字段作为返回数组的key
     * @return CoroMysql|mixed|null
     * @throws PrepareQueryException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    public function column(string $columnName, ?string $table = null, ?int $limit = null, ?string $arrayKey = null)
    {
        $searchColumnName = is_string($arrayKey) ? "{$columnName},{$arrayKey}" : $columnName;
        $retval = $this->select($table, $limit, $searchColumnName);
        if ($retval instanceof CoroMysql) {
            return $retval;
        } elseif (is_array($retval) && !empty($retval)) {
            return array_column($retval, $columnName, $arrayKey);
        }
        return null;
    }

    /**
     * 获取某个字段的数据(仅仅返回首条记录)
     * 这是select方法的一个语法糖
     * @param string $columnName 需要获取的列名称
     * @param string|null $table 需要获取的表名称
     * @return CoroMysql|mixed|null
     * @throws PrepareQueryException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    public function value(string $columnName, ?string $table = null)
    {
        $retval = $this->select($table, 1, $columnName);
        if ($retval instanceof CoroMysql) {
            return $retval;
        } elseif (is_array($retval) && isset($retval[0][$columnName])) {
            return $retval[0][$columnName];
        }
        return null;
    }

    /**
     * 执行一次 INSERT INTO 操作
     * @param array $insertData 需要插入的数据
     * @param null|string $table 指定插入的表
     * @return mixed
     * @throws ConnectException
     * @throws ExecuteQueryException
     * @throws PrepareQueryException
     * @throws QueryTimeoutException
     * @throws TableNotSetException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    public function insert(array $insertData, $table = null)
    {
        $this->_parserTableName($table);
        return $this->_buildInsert($this->tableName, $insertData, 'INSERT');
    }

    /**
     * 执行一次 REPLACE INTO 操作
     * @param array $insertData 需要插入的数据
     * @param null|string $table 指定插入的表
     * @return mixed
     * @throws ConnectException
     * @throws ExecuteQueryException
     * @throws PrepareQueryException
     * @throws QueryTimeoutException
     * @throws TableNotSetException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    public function replace(array $insertData, $table = null)
    {
        $this->_parserTableName($table);
        return $this->_buildInsert($this->tableName, $insertData, 'REPLACE');
    }

    /**
     * 执行一次 UPDATE 更新操作
     * @param array $updateData 需要更新的数据
     * @param string|null $table 需要更新的表
     * @param bool $force 开启强制模式 无条件时也可进行更新
     * @param int|null $limit 限制更新记录的条数
     * @return mixed|null 返回执行结果 获取影响行数请使用 affected_rows
     * @throws PrepareQueryException
     * @throws TableNotSetException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    public function update(array $updateData, ?string $table = null, bool $force = false, ?int $limit = null)
    {
        // 子查询不执行 只负责构建语句
        if ($this->isSubQuery) {
            return null;
        }

        // 如果没有开启force 并且没有where条件 禁止执行更新
        if (empty($this->condWhere) && !$force) {
            throw new EmptyConditionException('WARNING: update condition is empty, if you want to update it, please set the "$force" option as true');
        }

        // 解析表名称 构建查询语句
        $this->_parserTableName($table);
        $this->currentQuery = "UPDATE " . $this->tableName;
        $stmt = $this->_buildQuery($limit, $updateData);

        try {
            $status = $this->executeStatement($stmt);
            $this->affectRows = $stmt->affected_rows;
            return $status;
        } catch (\Throwable $throwable) {
            throw $throwable;
        } finally {
            $this->reset();
        }

    }

    /**
     * 执行一次 DELETE 删除操作
     * @param string|null $table
     * @param bool $force
     * @param int|null $limit
     * @return null
     * @throws EmptyConditionException
     * @throws TableNotSetException
     * @throws \Throwable
     */
    public function delete(?string $table = null, bool $force = false, ?int $limit = null)
    {
        // 子查询不执行 只负责构建语句
        if ($this->isSubQuery) {
            return null;
        }

        // 如果没有开启force 并且没有where条件 禁止执行删除
        if (empty($this->condWhere) && !$force) {
            throw new EmptyConditionException('WARNING: delete condition is empty, if you want to delete it, please set the "$force" option as true');
        }

        // 解析表名称 构建删除语句
        $this->_parserTableName($table);
        if (count($this->condJoin)) {
            $this->currentQuery = "DELETE " . preg_replace('/.* (.*)/', '$1', $this->tableName) . " FROM " . $this->tableName;
        } else {
            $this->currentQuery = "DELETE " . " FROM " . $this->tableName;
        }

        $stmt = $this->_buildQuery($limit);
        try {
            $this->executeStatement($stmt);
            $this->affectRows = $stmt->affected_rows;
            return ($stmt->affected_rows > -1);    //	affected_rows returns 0 if nothing matched where statement, or required updating, -1 if error
        } catch (\Throwable $throwable) {
            throw $throwable;
        } finally {
            $this->reset();
        }

    }

    /* Builder Functions */

    /**
     * 构建一条查询语句
     * @param null $numRows
     * @param null $tableData
     * @return mixed|CoroMysqlClient\Statement|null
     * @throws ConnectException
     * @throws PrepareQueryException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    protected function _buildQuery($numRows = null, $tableData = null)
    {
        $this->_buildJoin();
        $this->_buildInsertQuery($tableData);
        $this->_buildCondition('WHERE', $this->condWhere);
        $this->_buildGroupBy();
        $this->_buildCondition('HAVING', $this->condHaving);
        $this->_buildOrderBy();
        $this->_buildLimit($numRows);
        $this->_buildOnDuplicate($tableData);
        if ($this->forUpdate) {
            $this->currentQuery .= ' FOR UPDATE';
        }
        if ($this->lockInShareMode) {
            $this->currentQuery .= ' LOCK IN SHARE MODE';
        }

        $this->lastQuery = $this->_replacePlaceHolders($this->currentQuery, $this->bindParams);

        if ($this->isSubQuery) {
            return null;
        }

        // 对当前语句执行预处理
        $stmt = $this->_prepareQuery();
        return $stmt;
    }

    /**
     * 构建Join查询
     * @return void;
     */
    protected function _buildJoin()
    {
        if (empty ($this->condJoin)) return;
        foreach ($this->condJoin as $data) {

            list ($joinType, $joinTable, $joinCondition) = $data;

            if (is_object($joinTable))
                $joinStr = $this->_buildPair("", $joinTable);
            else
                $joinStr = $joinTable;

            $this->currentQuery .= " " . $joinType . " JOIN " . $joinStr . (false !== stripos($joinCondition, 'using') ? " " : " on ") . $joinCondition;

            if (!empty($this->condJoinAnd) && isset($this->condJoinAnd[$joinStr])) {
                foreach ($this->condJoinAnd[$joinStr] as $join_and_cond) {
                    list ($concat, $varName, $operator, $val) = $join_and_cond;
                    $this->currentQuery .= " " . $concat . " " . $varName;
                    $this->_conditionToSql($operator, $val);
                }
            }
        }
    }

    /**
     * 构建一个INSERT/UPDATE语句
     * @param array $tableData 插入或更新的数据
     * @throws WrongOperationException
     * @return void
     */
    protected function _buildInsertQuery($tableData)
    {
        if (!is_array($tableData)) {
            return;
        }

        $isInsert = preg_match('/^[INSERT|REPLACE]/', $this->currentQuery);
        $dataColumns = array_keys($tableData);
        if ($isInsert) {
            if (isset ($dataColumns[0]))
                $this->currentQuery .= ' (`' . implode($dataColumns, '`, `') . '`) ';
            $this->currentQuery .= ' VALUES (';
        } else {
            $this->currentQuery .= " SET ";
        }

        $this->_buildDataPairs($tableData, $dataColumns, $isInsert);

        if ($isInsert) {
            $this->currentQuery .= ')';
        }
    }

    /**
     * 构建Where和Having查询条件
     * @param string $operator 执行的操作
     * @param array $conditions 查询条件
     * @return void
     */
    protected function _buildCondition($operator, &$conditions)
    {
        if (empty($conditions)) {
            return;
        }

        //Prepare the where portion of the query
        $this->currentQuery .= ' ' . $operator;

        foreach ($conditions as $cond) {
            list ($concat, $varName, $operator, $val) = $cond;
            $this->currentQuery .= " " . $concat . " " . $varName;

            switch (strtolower($operator)) {
                case 'not in':
                case 'in':
                    $comparison = ' ' . $operator . ' (';
                    if (is_object($val)) {
                        $comparison .= $this->_buildPair("", $val);
                    } else {
                        foreach ($val as $v) {
                            $comparison .= ' ?,';
                            $this->_bindParam($v);
                        }
                    }
                    $this->currentQuery .= rtrim($comparison, ',') . ' ) ';
                    break;
                case 'not between':
                case 'between':
                    $this->currentQuery .= " $operator ? AND ? ";
                    $this->_bindParams($val);
                    break;
                case 'not exists':
                case 'exists':
                    $this->currentQuery .= $operator . $this->_buildPair("", $val);
                    break;
                default:
                    if (is_array($val)) {
                        $this->_bindParams($val);
                    } elseif ($val === null) {
                        $this->currentQuery .= ' ' . $operator . " NULL";
                    } elseif ($val != 'DBNULL' || $val == '0') {
                        $this->currentQuery .= $this->_buildPair($operator, $val);
                    }
            }
        }
    }

    /**
     * 构建GroupBy查询条件
     * @return void
     */
    protected function _buildGroupBy()
    {
        if (empty($this->condGroupBy)) {
            return;
        }

        $this->currentQuery .= " GROUP BY ";

        foreach ($this->condGroupBy as $key => $value) {
            $this->currentQuery .= $value . ", ";
        }

        $this->currentQuery = rtrim($this->currentQuery, ', ') . " ";
    }

    /**
     * 构建OrderBy查询条件
     * @return void
     */
    protected function _buildOrderBy()
    {
        if (empty($this->condOrderBy)) {
            return;
        }

        $this->currentQuery .= " ORDER BY ";
        foreach ($this->condOrderBy as $prop => $value) {
            if (strtolower(str_replace(" ", "", $prop)) == 'rand()') {
                $this->currentQuery .= "rand(), ";
            } else {
                $this->currentQuery .= $prop . " " . $value . ", ";
            }
        }

        $this->currentQuery = rtrim($this->currentQuery, ', ') . " ";
    }

    /**
     * 构建查询语句的LIMIT条件
     * @param int|array $numRows 传入一个数组 ($offset, $count) 或者单独传入 $count
     * @return void
     */
    protected function _buildLimit($numRows)
    {
        if (!isset($numRows)) {
            return;
        }

        if (is_array($numRows)) {
            $this->currentQuery .= ' LIMIT ' . (int)$numRows[0] . ', ' . (int)$numRows[1];
        } else {
            $this->currentQuery .= ' LIMIT ' . (int)$numRows;
        }
    }

    /**
     * 构建ON DUPLICATE KEY UPDATE语句
     * @param array $tableData
     * @throws WrongOperationException
     */
    protected function _buildOnDuplicate($tableData)
    {
        if (is_array($this->updateColumns) && !empty($this->updateColumns)) {
            $this->currentQuery .= " ON DUPLICATE KEY UPDATE ";
            if ($this->lastInsertId) {
                $this->currentQuery .= $this->lastInsertId . "=LAST_INSERT_ID (" . $this->lastInsertId . "), ";
            }

            foreach ($this->updateColumns as $key => $val) {
                // skip all params without a value
                if (is_numeric($key)) {
                    $this->updateColumns[$val] = '';
                    unset($this->updateColumns[$key]);
                } else {
                    $tableData[$key] = $val;
                }
            }
            $this->_buildDataPairs($tableData, array_keys($this->updateColumns), false);
        }
    }

    /* Query Inner Functions */

    /**
     * 构建一条插入语句并执行
     * @param string $tableName 操作的表名称
     * @param array $insertData 插入的数据
     * @param string $operation INSERT/REPLACE
     * @return mixed
     * @throws ConnectException
     * @throws ExecuteQueryException
     * @throws PrepareQueryException
     * @throws QueryTimeoutException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    protected function _buildInsert($tableName, $insertData, $operation)
    {
        if ($this->isSubQuery) {
            return null;
        }
        $this->currentQuery = $operation . " " . implode(' ', $this->queryOptions) . " INTO " . $tableName;
        $stmt = $this->_buildQuery(null, $insertData);
        $status = $this->executeStatement($stmt);
        $this->stmtError = $stmt->error;
        $this->stmtErrno = $stmt->errno;
        $haveOnDuplicate = !empty ($this->updateColumns);
        $this->affectRows = $stmt->affected_rows;
        $this->reset();
        if ($stmt->affected_rows < 1) {
            // in case of onDuplicate() usage, if no rows were inserted
            if ($status && $haveOnDuplicate) {
                return true;
            }
            return false;
        }

        if ($stmt->insert_id > 0) {
            return $stmt->insert_id;
        }

        return true;
    }

    /**
     * 对已进行预处理的语句执行查询
     * @param CoroMysqlClient\Statement $statement
     * @param float|null $timeout
     * @return mixed
     * @throws ExecuteQueryException
     * @throws QueryTimeoutException
     */
    public function executeStatement(CoroMysqlClient\Statement $statement, ?float $timeout = null)
    {
        try {

            // 将绑定的参数传入进行查询
            $this->lastStatement = $statement;
            $bindParams = empty($this->bindParams) ? array() : $this->bindParams;
            $retval = $statement->execute($bindParams, $this->_executeTimeout($timeout));
            if ($retval === false) throw new ExecuteQueryException;

            // 重置以下成员变量
            $this->stmtError = $statement->error;
            $this->stmtErrno = $statement->errno;
            $this->affectRows = 0;
            $this->totalCount = 0;

            // 如果参数中要求计算总行数 则额外取得总行数
            if (in_array('SQL_CALC_FOUND_ROWS', $this->queryOptions)) {
                $hitCount = $this->coroMysqlClient->query('SELECT FOUND_ROWS() as count');
                $this->totalCount = $hitCount[0]['count'];
            }

            return $retval;

        } catch (\Throwable $throwable) {
            $query = $this->_replacePlaceHolders($this->currentQuery, $bindParams);
            if ($throwable instanceof ExecuteQueryException) {
                if ($this->coroMysqlClient->errno == 110) {
                    throw new QueryTimeoutException(sprintf('[%u] Execute Statement TimeOut: %s with statement -> %s', $this->coroMysqlClient->errno, $this->coroMysqlClient->error, $query));
                }
                throw new ExecuteQueryException(sprintf('[%u] Execute Statement Failed: %s with statement -> %s', $this->coroMysqlClient->errno, $this->coroMysqlClient->error, $query));
            }
            throw new ExecuteQueryException(sprintf('[%u] Execute Statement Failed: %s with statement -> %s', $throwable->getCode(), $throwable->getMessage(), $query));
        } finally {
            $this->reset();
        }
    }

    /* Lock And Transaction Functions */

    /**
     * 设置锁表方式
     * @param string $method 锁表方式
     * @return $this
     * @throws LockTypeException
     */
    public function setLockMethod($method)
    {
        // Switch the uppercase string
        switch (strtoupper($method)) {
            // Is it READ or WRITE?
            case "READ" || "WRITE":
                // Succeed
                $this->tableLockMethod = $method;
                break;
            default:
                // Else throw an exception
                throw new LockTypeException("Bad lock type: Can be either READ or WRITE");
                break;
        }
        return $this;
    }

    /**
     * 当前线程请求持有指定表的表锁
     * @param string|array $table 单个或者多个表
     * @return bool
     * @throws QueryException
     * @throws QueryTimeoutException
     */
    public function lock($table)
    {
        $this->currentQuery = "LOCK TABLES";

        // Is the table an array?
        if (gettype($table) == "array") {
            // Loop trough it and attach it to the query
            foreach ($table as $key => $value) {
                if (gettype($value) == "string") {
                    if ($key > 0) {
                        $this->currentQuery .= ",";
                    }
                    $this->currentQuery .= " " . $this->coroMysqlConfig->getPrefix() . $value . " " . $this->tableLockMethod;
                }
            }
        } else {
            // Build the table prefix
            $table = $this->coroMysqlConfig->getPrefix() . $table;

            // Build the query
            $this->currentQuery = "LOCK TABLES " . $table . " " . $this->tableLockMethod;
        }

        // Execute the query unprepared because LOCK only works with unprepared statements.
        $result = $this->queryUnprepared($this->currentQuery);

        // Reset the query
        $this->reset();

        // Are there rows modified?
        if ($result) {
            // Return true
            // We can't return ourself because if one table gets locked, all other ones get unlocked!
            return true;
        } // Something went wrong
        else {
            throw new LockFailedException("Locking of table " . $table . " failed: " . $this->coroMysqlClient->error, $this->coroMysqlClient->errno);
        }
    }

    /**
     * 释放当前线程的表锁
     * @return $this
     * @throws QueryException
     * @throws QueryTimeoutException
     */
    public function unlock()
    {
        // Build the query
        $this->currentQuery = "UNLOCK TABLES";

        // Execute the query unprepared because UNLOCK and LOCK only works with unprepared statements.
        $result = $this->queryUnprepared($this->currentQuery);

        // Reset the query
        $this->reset();

        // Are there rows modified?
        if ($result) {
            // return self
            return $this;
        } // Something went wrong
        else {
            throw new UnlockException("Unlocking of tables failed: " . $this->coroMysqlClient->error, $this->coroMysqlClient->errno);
        }
    }

    /**
     * 启动事务
     * @return array|bool|mixed
     * @throws ConnectException
     * @throws QueryException
     * @throws QueryTimeoutException
     * @throws \Throwable
     */
    public function startTransaction()
    {
        if ($this->inTransaction) {
            return true;
        } else {
            $this->connect();
            $res = $this->queryUnprepared('start transaction');
            if ($res) {
                $this->inTransaction = true;
            }
            return $res;
        }
    }

    /**
     * 事务提交
     * @return array|bool|mixed
     * @throws ConnectException
     * @throws QueryException
     * @throws QueryTimeoutException
     * @throws \Throwable
     */
    public function commit()
    {
        if ($this->inTransaction) {
            $this->connect();
            $res = $this->queryUnprepared('commit');
            if ($res) {
                $this->inTransaction = false;
            }
            return $res;
        } else {
            return true;
        }
    }

    /**
     * 事务回滚
     * @param bool $autocommit
     * @return array|bool|mixed
     * @throws ConnectException
     * @throws QueryException
     * @throws QueryTimeoutException
     * @throws \Throwable
     */
    public function rollback($autocommit = true)
    {
        if ($this->inTransaction) {
            $this->connect();
            $res = $this->queryUnprepared('rollback');
            if ($res && $autocommit) {
                $res = $this->commit();
                if ($res) {
                    $this->inTransaction = false;
                }
                return $res;
            } else {
                return $res;
            }
        } else {
            return true;
        }
    }

    /* Get Status Functions */

    /**
     * 获取最后插入的ID
     * @return mixed
     */
    public function getInsertId()
    {
        return $this->coroMysqlClient->insert_id;
    }

    /**
     * 获取最后执行的查询语句
     * @return string
     */
    public function getLastQuery()
    {
        return $this->lastQuery;
    }

    /**
     * 获取最后错误原因
     * @return string
     */
    public function getLastError()
    {
        return trim($this->stmtError);
    }

    /**
     * 获取最后错误代码
     * @return mixed
     */
    public function getLastErrno()
    {
        return $this->stmtErrno;
    }

    /**
     * 本次查询的总数据行数
     * @return mixed
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * 本次查询受影响的行数
     * @return mixed
     */
    public function getAffectRows()
    {
        return $this->affectRows;
    }

    /**
     * 获取子查询
     * @return array|null
     */
    public function getSubQuery()
    {
        if (!$this->isSubQuery) {
            return null;
        }

        $val = Array(
            'query'  => $this->currentQuery,
            'params' => $this->bindParams,
            'alias'  => $this->coroMysqlConfig->getHostname()
        );
        $this->reset();
        return $val;
    }

    /**
     * 创建一个子查询
     * @param string $subQueryAlias
     * @return CoroMysql
     */
    public static function subQuery($subQueryAlias = "")
    {
        $conf = new CoroMysqlConfig;
        $conf->setIsSubQuery(true);
        $conf->setHostname($subQueryAlias);
        return new self($conf);
    }

    /* Helper Functions */

    /**
     * 重置操作类的状态
     * 使得操作类可以在连接池中复用
     * @return void
     */
    public function reset()
    {

        // 查询条件构造项
        $this->condJoin = [];
        $this->condWhere = [];
        $this->condHaving = [];
        $this->condGroupBy = [];
        $this->condOrderBy = [];
        $this->condJoinAnd = [];

        // 重置状态储存
        $this->currentQuery = '';

        // 重置额外配置项
        $this->limit = null;
        $this->tableName = '';
        $this->queryColumns = '*';
        $this->nestJoin = false;
        $this->forUpdate = false;
        $this->lockInShareMode = false;
        $this->queryOptions = [];
        $this->tableLockMethod = 'READ';
        $this->updateColumns = [];
        $this->bindParams = [];
    }

    /**
     * 转义字符串
     * 注意: 依赖mysqlnd驱动
     * @param string $str
     * @return mixed
     * @throws ConnectException
     * @throws \Throwable
     */
    public function escape($str)
    {
        $this->connect();
        return $this->coroMysqlClient->escape($str);
    }

    /**
     * 检查当前是否连接中
     * @return mixed
     */
    public function ping()
    {
        return $this->coroMysqlClient->connected;
    }

    /**
     * 返回字符串形式的时间间隔函数
     * @param string $diff 详见文档
     * @param string $func 初始时间
     * @return string 返回时间字符串
     * @throws \Exception
     */
    public function interval($diff, $func = "NOW()")
    {
        $types = Array("s" => "second", "m" => "minute", "h" => "hour", "d" => "day", "M" => "month", "Y" => "year");
        $incr = '+';
        $items = '';
        $type = 'd';

        if ($diff && preg_match('/([+-]?) ?([0-9]+) ?([a-zA-Z]?)/', $diff, $matches)) {
            if (!empty($matches[1])) {
                $incr = $matches[1];
            }

            if (!empty($matches[2])) {
                $items = $matches[2];
            }

            if (!empty($matches[3])) {
                $type = $matches[3];
            }

            if (!in_array($type, array_keys($types))) {
                throw new CoroDBException("invalid interval type in '{$diff}'");
            }

            $func .= " " . $incr . " interval " . $items . " " . $types[$type] . " ";
        }
        return $func;
    }

    /**
     * 对字段插入时间
     * @param null $diff 详见文档
     * @param string $func 初始时间
     * @return array
     * @throws \Exception
     */
    public function now($diff = null, $func = "NOW()")
    {
        return array("[F]" => Array($this->interval($diff, $func)));
    }

    /**
     * 对字段做自增操作
     * @param int $num 步进值
     * @return array
     * @throws CoroDBException
     */
    public function inc($num = 1)
    {
        if (!is_numeric($num)) {
            throw new CoroDBException('Argument supplied to inc must be a number');
        }
        return array("[I]" => "+" . $num);
    }

    /**
     * 对字段做自减操作
     * @param int $num 步进值
     * @return array
     * @throws CoroDBException
     */
    public function dec($num = 1)
    {
        if (!is_numeric($num)) {
            throw new CoroDBException('Argument supplied to dec must be a number');
        }
        return array("[I]" => "-" . $num);
    }

    /**
     * 对字段做取反操作
     * @param string $col column name. null by default
     * @return array
     */
    public function not($col = null)
    {
        return array("[N]" => (string)$col);
    }

    /**
     * 生成参数绑定的Mysql函数调用
     * @param string $expr 函数
     * @param array $bindParams 绑定参数
     * @return array
     */
    public function func($expr, $bindParams = null)
    {
        return array("[F]" => array($expr, $bindParams));
    }

    /**
     * 检查某个表是否存在
     * @param array $tables 需要检查的表名称(可以传入数组来查询多个表)
     * @return bool 如果存在则返回true
     * @throws PrepareQueryException
     * @throws WrongOperationException
     * @throws \Throwable
     */
    public function tableExists($tables)
    {
        $tables = !is_array($tables) ? Array($tables) : $tables;
        $count = count($tables);
        if ($count == 0) {
            return false;
        }

        foreach ($tables as $i => $value) $tables[$i] = $this->coroMysqlConfig->getPrefix() . $value;
        $db = $this->coroMysqlConfig->getDatabase();
        $this->withTotalCount();
        $this->where('table_schema', $db);
        $this->where('table_name', $tables, 'in');
        $this->select('information_schema.tables', $count);
        return $this->getTotalCount() == $count;

    }

    /**
     * 获取数据库中的表
     * @param string $dbName
     * @return array
     * @throws QueryException
     * @throws QueryTimeoutException
     */
    public function getTables($dbName = '')
    {
        $exec = empty($dbName) ? "SHOW TABLES" : "SHOW TABLES FROM {$dbName}";
        $retval = $this->queryUnprepared($exec);
        $tables = array();
        foreach ($retval as $index => $val) {
            $tables[$index] = current($val);
        }
        return $tables;
    }

    /**
     * 获取某个表的全部字段信息
     * @param string $tableName
     * @return array
     * @throws QueryException
     * @throws QueryTimeoutException
     */
    public function getFields($tableName)
    {

        list($tableName) = explode(' ', $tableName);
        if (false === strpos($tableName, '`')) {
            if (strpos($tableName, '.')) {
                $tableName = str_replace('.', '`.`', $tableName);
            }
            $tableName = '`' . $tableName . '`';
        }
        $exec = 'SHOW COLUMNS FROM ' . $tableName;
        $retval = $this->queryUnprepared($exec);
        $columns = array();
        foreach ($retval as $column) {
            $column = array_change_key_case($column);
            $columns[$column['field']] = [
                'name'    => $column['field'],
                'type'    => $column['type'],
                'notnull' => (bool)('' === $column['null']), // not null is empty, null is yes
                'default' => $column['default'],
                'primary' => (strtolower($column['key']) == 'pri'),
                'autoinc' => (strtolower($column['extra']) == 'auto_increment'),
            ];
        }
        return $columns;
    }

    /**
     * 进行语句预处理
     * @param float|null $timeout
     * @return mixed
     * @throws ConnectException
     * @throws PrepareQueryException
     * @throws \Throwable
     */
    protected function _prepareQuery(?float $timeout = null): CoroMysqlClient\Statement
    {
        $this->connect();
        try {
            $statement = $this->coroMysqlClient->prepare($this->currentQuery, $this->_executeTimeout($timeout));
            if ($statement instanceof CoroMysqlClient\Statement) {
                return $statement;
            }
            throw new PrepareQueryException;
        } catch (\Throwable $throwable) {
            $query = $this->_replacePlaceHolders($this->currentQuery, $this->bindParams);
            $this->reset();
            if ($throwable instanceof PrepareQueryException) {
                throw new PrepareQueryException(sprintf('[%u] Prepare Query Failed: %s with statement -> %s', $this->coroMysqlClient->errno, $this->coroMysqlClient->error, $query));
            }
            throw new PrepareQueryException(sprintf('[%u] Prepare Query Failed: %s with statement -> %s', $throwable->getCode(), $throwable->getMessage(), $query));
        }
    }

    /**
     * 将语句中的绑定符号替换为具体的参数
     * @param string $str 需要替换的语句
     * @param array $vals 需要替换的内容
     * @return bool|string
     */
    protected function _replacePlaceHolders($str, $vals)
    {
        $i = 0;
        $newStr = "";

        if (empty($vals)) {
            return $str;
        }

        while ($pos = strpos($str, "?")) {
            $val = $vals[$i++];
            $echoValue = $val;

            if (is_object($val)) {
                $echoValue = '[object]';
            }
            if ($val === null) {
                $echoValue = 'NULL';
            }
            // 当值是字符串时 需要引号包裹
            if (is_string($val)) {
                $newStr .= substr($str, 0, $pos) . "'" . $echoValue . "'";
            } else {
                $newStr .= substr($str, 0, $pos) . $echoValue;
            }
            $str = substr($str, $pos + 1);
        }
        $newStr .= $str;
        return $newStr;
    }

    /**
     * 将变量添加到绑定参数数组中(辅助方法)
     * @param string $operator 绑定的条件
     * @param CoroMysql|mixed $value 需要绑定的参数
     * @return string
     */
    protected function _buildPair($operator, $value)
    {
        // 如果不是一个对象 则是需要绑定的值
        if (!is_object($value)) {
            $this->_bindParam($value);
            return ' ' . $operator . ' ? ';
        }

        // 否则从对象中取得子查询 并进行构建
        $subQuery = $value->getSubQuery();
        $this->_bindParams($subQuery['params']);
        return " " . $operator . " (" . $subQuery['query'] . ") " . $subQuery['alias'];
    }

    /**
     * 为Insert/Update语句构建数据对部分
     * @param array $tableData 表格数据
     * @param array $tableColumns 表格列
     * @param bool $isInsert 插入语句标记
     * @throws WrongOperationException
     */
    protected function _buildDataPairs($tableData, $tableColumns, $isInsert)
    {
        foreach ($tableColumns as $column) {
            $value = $tableData[$column];

            if (!$isInsert) {
                if (strpos($column, '.') === false) {
                    $this->currentQuery .= "`" . $column . "` = ";
                } else {
                    $this->currentQuery .= str_replace('.', '.`', $column) . "` = ";
                }
            }

            // 传入的值是一个子查询
            if ($value instanceof CoroMysql) {
                $this->currentQuery .= $this->_buildPair("", $value) . ", ";
                continue;
            }

            // 传入一个普通的值
            if (!is_array($value)) {
                $this->_bindParam($value);
                $this->currentQuery .= '?, ';
                continue;
            }

            // 传入的是一个函数(或函数表达符)
            $key = key($value);
            $val = $value[$key];
            switch ($key) {
                case '[I]':
                    $this->currentQuery .= $column . $val . ", ";
                    break;
                case '[F]':
                    $this->currentQuery .= $val[0] . ", ";
                    if (!empty($val[1])) {
                        $this->_bindParams($val[1]);
                    }
                    break;
                case '[N]':
                    if ($val == null) {
                        $this->currentQuery .= "!" . $column . ", ";
                    } else {
                        $this->currentQuery .= "!" . $val . ", ";
                    }
                    break;
                default:
                    throw new WrongOperationException("Wrong query operation: {$key}");
            }
        }
        $this->currentQuery = rtrim($this->currentQuery, ', ');
    }

    /**
     * 将变量添加到绑定参数数组中
     * @param string Variable value
     */
    protected function _bindParam($value)
    {
        array_push($this->bindParams, $value);
    }

    /**
     * 批量向绑定参数数组中添加变量
     * @param array $values Variable with values
     */
    protected function _bindParams($values)
    {
        foreach ($values as $value) {
            $this->_bindParam($value);
        }
    }

    /**
     * 将条件和值转换为SQL字符串
     * @param  String $operator where 查询操作
     * @param  String|array $val where 操作的值
     */
    private function _conditionToSql($operator, $val)
    {
        switch (strtolower($operator)) {
            case 'not in':
            case 'in':
                $comparison = ' ' . $operator . ' (';
                if (is_object($val)) {
                    $comparison .= $this->_buildPair("", $val);
                } else {
                    foreach ($val as $v) {
                        $comparison .= ' ?,';
                        $this->_bindParam($v);
                    }
                }
                $this->currentQuery .= rtrim($comparison, ',') . ' ) ';
                break;
            case 'not between':
            case 'between':
                $this->currentQuery .= " $operator ? AND ? ";
                $this->_bindParams($val);
                break;
            case 'not exists':
            case 'exists':
                $this->currentQuery .= $operator . $this->_buildPair("", $val);
                break;
            default:
                if (is_array($val))
                    $this->_bindParams($val);
                else if ($val === null)
                    $this->currentQuery .= $operator . " NULL";
                else if ($val != 'DBNULL' || $val == '0')
                    $this->currentQuery .= $this->_buildPair($operator, $val);
        }
    }

    /**
     * 计算查询超时参数
     * @param float|null $timeout 用户自定义超时
     * @return float|int|null
     */
    private function _executeTimeout(?float $timeout = null): float
    {
        if ($timeout == null) {
            $timeout = $this->coroMysqlConfig->getExecuteTimeOut();
            if ($timeout == null) {
                $timeout = -1;
            }
        }
        return $timeout;
    }

    /**
     * 处理表名称(附加前缀)
     * @param string|null $tableName
     * @return CoroMysql
     * @throws TableNotSetException
     */
    private function _parserTableName(?string $tableName = null)
    {
        // 如果传入了null值则使用设置的table值
        if ($tableName == null) {
            $tableName = $this->tableName;
        }

        // 未设置表名称则抛出异常
        if (empty($tableName)) {
            throw new TableNotSetException('QueryTable Not Set!');
        }

        // 附加查询表前缀
        $tablePrefix = $this->coroMysqlConfig->getPrefix();
        if (!empty($tablePrefix) && strpos($tableName, '.') === false) {
            $this->tableName = $this->coroMysqlConfig->getPrefix() . $tableName;
        } else {
            $this->tableName = $tableName;
        }

        return $this;
    }
}