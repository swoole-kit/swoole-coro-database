<?php

namespace SwooleKit\CoroDatabase\CoMysql;

use Closure;
use DateTime;
use EasySwoole\Component\Pool\Exception\PoolEmpty;
use EasySwoole\Component\Pool\Exception\PoolException;
use Exception;
use Generator;
use PDOStatement;
use SwooleKit\CoroDatabase\CoMysql;
use SwooleKit\CoroDatabase\CoMysqlException;
use think\Model;
use think\Paginator;
use Throwable;

/**
 * 查询构造器
 * Class CoMysqlQuery
 * @package SwooleKit\CoroDatabase\CoMysql
 */
class CoMysqlQuery
{
    // 数据库Connection对象
    protected static $connections = [];

    // 当前数据库Connection对象
    protected $connection;

    // 当前数据表主键
    protected $pk;

    // 当前数据表名称（不含前缀）
    protected $name = '';

    // 当前模型对象
    protected $model;

    // 当前数据表前缀
    protected $prefix = '';

    // 查询参数
    protected $options = [];

    // 参数绑定
    protected $bind = [];

    // 回调事件
    private static $event = [];

    // 扩展查询方法
    private static $extend = [];

    /**
     * 读取主库的表
     * @var array
     */
    private static $readMaster = [];

    // 日期查询快捷方式
    protected $timeExp = ['d' => 'today', 'w' => 'week', 'm' => 'month', 'y' => 'year'];
    // 日期查询表达式
    protected $timeRule = [
        'today'      => ['today', 'tomorrow'],
        'yesterday'  => ['yesterday', 'today'],
        'week'       => ['this week 00:00:00', 'next week 00:00:00'],
        'last week'  => ['last week 00:00:00', 'this week 00:00:00'],
        'month'      => ['first Day of this month 00:00:00', 'first Day of next month 00:00:00'],
        'last month' => ['first Day of last month 00:00:00', 'first Day of this month 00:00:00'],
        'year'       => ['this year 1/1', 'next year 1/1'],
        'last year'  => ['last year 1/1', 'this year 1/1'],
    ];

    /**
     * 架构函数
     * @access public
     * @param CoMysqlConnection|null $connection
     * @throws PoolEmpty
     * @throws PoolException
     * @throws CoMysqlException
     */
    public function __construct(CoMysqlConnection $connection = null)
    {
        // 如果给入了链接则始终使用同一链接(不支持多连接切换)
        $this->connection = is_null($connection) ? CoMysql::getConnection() : $connection;
        $this->prefix = $this->connection->getConfig('prefix');
    }

    /**
     * 创建一个新的查询对象
     * @access public
     * @return CoMysqlQuery
     * @throws CoMysqlException
     * @throws PoolEmpty
     * @throws PoolException
     */
    public function newQuery()
    {
        return new static($this->connection);
    }

    /**
     * 利用__call方法实现一些特殊的Model方法
     * @access public
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return mixed
     * @throws Exception
     */
    public function __call($method, $args)
    {
        if (isset(self::$extend[strtolower($method)])) {
            // 调用扩展查询方法
            array_unshift($args, $this);
            return call_user_func_array(self::$extend[strtolower($method)], $args);
        } elseif (strtolower(substr($method, 0, 5)) == 'getby') {
            // 根据某个字段获取记录
            $field = CoMysql::parseName(substr($method, 5));
            return $this->where($field, '=', $args[0])->find();
        } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
            // 根据某个字段获取记录的某个值
            $name = CoMysql::parseName(substr($method, 10));
            return $this->where($name, '=', $args[0])->value($args[1]);
        } elseif (strtolower(substr($method, 0, 7)) == 'whereor') {
            $name = CoMysql::parseName(substr($method, 7));
            array_unshift($args, $name);
            return call_user_func_array([$this, 'whereOr'], $args);
        } elseif (strtolower(substr($method, 0, 5)) == 'where') {
            $name = CoMysql::parseName(substr($method, 5));
            array_unshift($args, $name);
            return call_user_func_array([$this, 'where'], $args);
        } elseif ($this->model && method_exists($this->model, 'scope' . $method)) {
            // 动态调用命名范围
            $method = 'scope' . $method;
            array_unshift($args, $this);
            call_user_func_array([$this->model, $method], $args);
            return $this;
        } else {
            throw new Exception('method not exist:' . ($this->model ? get_class($this->model) : static::class) . '->' . $method);
        }
    }

    /**
     * 扩展查询方法
     * @access public
     * @param string|array $method 查询方法名
     * @param callable $callback
     * @return void
     */
    public static function extend($method, $callback = null)
    {
        if (is_array($method)) {
            foreach ($method as $key => $val) {
                self::$extend[strtolower($key)] = $val;
            }
        } else {
            self::$extend[strtolower($method)] = $callback;
        }
    }

    /**
     * 设置当前的数据库Connection对象
     * @access public
     * @param CoMysqlConnection $connection
     * @return $this
     */
    public function setConnection(CoMysqlConnection $connection)
    {
        $this->connection = $connection;
        $this->prefix = $this->connection->getConfig('prefix');
        return $this;
    }

    /**
     * 获取当前的数据库Connection对象
     * @access public
     * @return CoMysqlConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

//    /**
//     * 指定模型
//     * @access public
//     * @param Model $model 模型对象实例
//     * @return $this
//     */
//    public function model(Model $model)
//    {
//    }
//
//    /**
//     * 获取当前的模型对象
//     * @access public
//     * @return Model|null
//     */
//    public function getModel()
//    {
//    }
//
//    /**
//     * 设置从主库读取数据
//     * @access public
//     * @param bool $all 是否所有表有效
//     * @return $this
//     */
//    public function readMaster($all = NULL)
//    {
//    }

    /**
     * 指定当前数据表名（不含前缀）
     * @access public
     * @param string $name
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 得到当前或者指定名称的数据表
     * @access public
     * @param string $name
     * @return string
     */
    public function getTable($name = '')
    {
        if (empty($name) && isset($this->options['table'])) {
            return $this->options['table'];
        }
        $name = $name ?: $this->name;
        return $this->prefix . CoMysql::parseName($name);
    }

//    /**
//     * 切换数据库连接
//     * @access public
//     * @param mixed $config 连接配置
//     * @param bool|string $name 连接标识 true 强制重新连接
//     * @return $this
//     * @throws Exception
//     */
//    public function connect($config = NULL, $name = NULL)
//    {
//
//    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param string $sql sql指令
     * @param array $bind 参数绑定
     * @param boolean $master 是否在主服务器读操作(暂时不用)
     * @param bool|string $class 指定返回的数据集对象(暂时不用)
     * @return mixed
     * @throws CoMysqlException
     * @throws Throwable
     */
    public function query($sql, $bind = [], $master = false, $class = false)
    {
        return $this->connection->query($sql, $bind, $master, $class);
    }

    /**
     * 执行语句
     * @access public
     * @param string $sql sql指令
     * @param array $bind 参数绑定
     * @return int
     * @throws CoMysqlException
     * @throws Throwable
     */
    public function execute($sql, $bind = NULL)
    {
        return $this->connection->execute($sql, $bind);
    }

//    /**
//     * 监听SQL执行
//     * @access public
//     * @param callable $callback 回调方法
//     * @return void
//     */
//    public function listen($callback)
//    {
//    }

    /**
     * 获取最近插入的ID
     * @access public
     * @param string $sequence 自增序列名(PGSql)
     * @return string
     */
    public function getLastInsID($sequence = NULL)
    {
        return $this->connection->getLastInsID($sequence);
    }

    /**
     * 获取返回或者影响的记录数
     * @access public
     * @return integer
     */
    public function getNumRows()
    {
        return $this->connection->getNumRows();
    }

    /**
     * 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->connection->getLastSql();
    }

    /**
     * 获取sql记录
     * @access public
     * @return array
     */
    public function getSqlLog()
    {
        return $this->connection->getSqlLog();
    }

    /**
     * 执行数据库事务
     * @access public
     * @param callable $callback 数据操作方法回调
     * @return mixed
     * @throws CoMysqlException
     * @throws Throwable
     */
    public function transaction($callback)
    {
        return $this->connection->transaction($callback);
    }

//    /**
//     * 执行数据库Xa事务
//     * @access public
//     * @param callable $callback 数据操作方法回调
//     * @param array $dbs 多个查询对象或者连接对象
//     * @return mixed
//     * @throws PDOException
//     * @throws Exception
//     * @throws Throwable
//     */
//    public function transactionXa($callback, array $dbs = NULL)
//    {
//
//    }


    /**
     * 启动事务
     * @access public
     * @return void
     * @throws CoMysqlException
     */
    public function startTrans()
    {
        $this->connection->startTrans();
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return void
     * @throws CoMysqlException
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * 事务回滚
     * @access public
     * @return void
     * @throws CoMysqlException
     */
    public function rollback()
    {
        $this->connection->rollback();
    }

    /**
     * 批处理执行SQL语句
     * 批处理的指令都认为是execute操作
     * @access public
     * @param array $sql SQL批处理指令
     * @return boolean
     * @throws CoMysqlException
     * @throws Throwable
     */
    public function batchQuery($sql = [])
    {
        return $this->connection->batchQuery($sql);
    }

    /**
     * 获取数据库的配置参数
     * @access public
     * @param string $name 参数名称
     * @return boolean
     */
    public function getConfig($name = '')
    {
        return $this->connection->getConfig($name);
    }

    /**
     * 获取数据表字段信息
     * @access public
     * @param string $tableName 数据表名
     * @return array
     */
    public function getTableFields($tableName = '')
    {
        if ('' == $tableName) {
            $tableName = isset($this->options['table']) ? $this->options['table'] : $this->getTable();
        }

        return $this->connection->getTableFields($tableName);
    }

    /**
     * 获取数据表字段类型
     * @access public
     * @param string $tableName 数据表名
     * @param string $field 字段名
     * @return array|string
     */
    public function getFieldsType($tableName = NULL, $field = NULL)
    {
    }

    /**
     * 是否允许返回空数据（或空模型）
     * @access public
     * @param bool $allowEmpty 是否允许为空
     * @return $this
     */
    public function allowEmpty($allowEmpty = NULL)
    {
    }

    /**
     * 得到分表的的数据表名
     * @access public
     * @param array $data 操作的数据
     * @param string $field 分表依据的字段
     * @param array $rule 分表规则
     * @return string
     */
    public function getPartitionTableName($data, $field, $rule = NULL)
    {
    }

    /**
     * 得到某个字段的值
     * @access public
     * @param string $field 字段名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function value($field, $default = NULL)
    {
    }

    /**
     * 得到某个列的数组
     * @access public
     * @param string $field 字段名 多个字段用逗号分隔
     * @param string $key 索引
     * @return array
     */
    public function column($field, $key = NULL)
    {
    }

    /**
     * 聚合查询
     * @access public
     * @param string $aggregate 聚合方法
     * @param string $field 字段名
     * @param bool $force 强制转为数字类型
     * @return mixed
     */
    public function aggregate($aggregate, $field, $force = NULL)
    {
    }

    /**
     * COUNT查询
     * @access public
     * @param string $field 字段名
     * @return integer|string
     */
    public function count($field = NULL)
    {
    }

    /**
     * SUM查询
     * @access public
     * @param string $field 字段名
     * @return float|int
     */
    public function sum($field)
    {
    }

    /**
     * MIN查询
     * @access public
     * @param string $field 字段名
     * @param bool $force 强制转为数字类型
     * @return mixed
     */
    public function min($field, $force = NULL)
    {
    }

    /**
     * MAX查询
     * @access public
     * @param string $field 字段名
     * @param bool $force 强制转为数字类型
     * @return mixed
     */
    public function max($field, $force = NULL)
    {
    }

    /**
     * AVG查询
     * @access public
     * @param string $field 字段名
     * @return float|int
     */
    public function avg($field)
    {
    }

    /**
     * 设置记录的某个字段值
     * 支持使用数据库字段和方法
     * @access public
     * @param string|array $field 字段名
     * @param mixed $value 字段值
     * @return integer
     */
    public function setField($field, $value = NULL)
    {
    }

    /**
     * 字段值(延迟)增长
     * @access public
     * @param string $field 字段名
     * @param integer $step 增长值
     * @return integer|true
     * @throws Exception
     */
    public function setInc($field, $step = NULL)
    {
    }

    /**
     * 字段值（延迟）减少
     * @access public
     * @param string $field 字段名
     * @param integer $step 减少值
     * @return integer|true
     * @throws Exception
     */
    public function setDec($field, $step = NULL)
    {
    }

    /**
     * 查询SQL组装 join
     * @access public
     * @param mixed $join 关联的表名
     * @param mixed $condition 条件
     * @param string $type JOIN类型
     * @return $this
     */
    public function join($join, $condition = NULL, $type = NULL)
    {
    }

    /**
     * LEFT JOIN
     * @access public
     * @param mixed $join 关联的表名
     * @param mixed $condition 条件
     * @return $this
     */
    public function leftJoin($join, $condition = NULL)
    {
    }

    /**
     * RIGHT JOIN
     * @access public
     * @param mixed $join 关联的表名
     * @param mixed $condition 条件
     * @return $this
     */
    public function rightJoin($join, $condition = NULL)
    {
    }

    /**
     * FULL JOIN
     * @access public
     * @param mixed $join 关联的表名
     * @param mixed $condition 条件
     * @return $this
     */
    public function fullJoin($join, $condition = NULL)
    {
    }

    /**
     * 获取Join表名及别名 支持
     * ['prefix_table或者子查询'=>'alias'] 'prefix_table alias' 'table alias'
     * @access public
     * @param array|string $join
     * @return array|string
     */
    protected function getJoinTable($join, $alias = NULL)
    {
    }

    /**
     * 查询SQL组装 union
     * @access public
     * @param mixed $union
     * @param boolean $all
     * @return $this
     */
    public function union($union, $all = NULL)
    {
    }

    /**
     * 查询SQL组装 union all
     * @access public
     * @param mixed $union
     * @return $this
     */
    public function unionAll($union)
    {
    }

    /**
     * 指定查询字段 支持字段排除和指定数据表
     * @access public
     * @param mixed $field
     * @param boolean $except 是否排除
     * @param string $tableName 数据表名
     * @param string $prefix 字段前缀
     * @param string $alias 别名前缀
     * @return $this
     */
    public function field($field, $except = NULL, $tableName = NULL, $prefix = NULL, $alias = NULL)
    {
    }

    /**
     * 表达式方式指定查询字段
     * @access public
     * @param string $field 字段名
     * @return $this
     */
    public function fieldRaw($field)
    {
    }

    /**
     * 设置数据
     * @access public
     * @param mixed $field 字段名或者数据
     * @param mixed $value 字段值
     * @return $this
     */
    public function data($field, $value = NULL)
    {
    }

    /**
     * 字段值增长
     * @access public
     * @param string|array $field 字段名
     * @param integer $step 增长值
     * @return $this
     */
    public function inc($field, $step = NULL, $op = NULL)
    {
    }

    /**
     * 字段值减少
     * @access public
     * @param string|array $field 字段名
     * @param integer $step 增长值
     * @return $this
     */
    public function dec($field, $step = NULL)
    {
    }

    /**
     * 使用表达式设置数据
     * @access public
     * @param string $field 字段名
     * @param string $value 字段值
     * @return $this
     */
    public function exp($field, $value)
    {
    }

    /**
     * 使用表达式设置数据
     * @access public
     * @param mixed $value 表达式
     * @return Expression
     */
    public function raw($value)
    {
    }

    /**
     * 指定JOIN查询字段
     * @access public
     * @param string|array $table 数据表
     * @param string|array $field 查询字段
     * @param string|array $on JOIN条件
     * @param string $type JOIN类型
     * @return $this
     */
    public function view($join, $field = NULL, $on = NULL, $type = NULL)
    {
    }

    /**
     * 设置分表规则
     * @access public
     * @param array $data 操作的数据
     * @param string $field 分表依据的字段
     * @param array $rule 分表规则
     * @return $this
     */
    public function partition($data, $field, $rule = NULL)
    {
    }

    /**
     * 指定AND查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $op 查询表达式
     * @param mixed $condition 查询条件
     * @return $this
     */
    public function where($field, $op = NULL, $condition = NULL)
    {
    }

    /**
     * 指定表达式查询条件
     * @access public
     * @param string $where 查询条件
     * @param array $bind 参数绑定
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereRaw($where, $bind = NULL, $logic = NULL)
    {
    }

    /**
     * 指定表达式查询条件 OR
     * @access public
     * @param string $where 查询条件
     * @param array $bind 参数绑定
     * @return $this
     */
    public function whereOrRaw($where, $bind = NULL)
    {
    }

    /**
     * 指定OR查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $op 查询表达式
     * @param mixed $condition 查询条件
     * @return $this
     */
    public function whereOr($field, $op = NULL, $condition = NULL)
    {
    }

    /**
     * 指定XOR查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $op 查询表达式
     * @param mixed $condition 查询条件
     * @return $this
     */
    public function whereXor($field, $op = NULL, $condition = NULL)
    {
    }

    /**
     * 指定Null查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereNull($field, $logic = NULL)
    {
    }

    /**
     * 指定NotNull查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereNotNull($field, $logic = NULL)
    {
    }

    /**
     * 指定Exists查询条件
     * @access public
     * @param mixed $condition 查询条件
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereExists($condition, $logic = NULL)
    {
    }

    /**
     * 指定NotExists查询条件
     * @access public
     * @param mixed $condition 查询条件
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereNotExists($condition, $logic = NULL)
    {
    }

    /**
     * 指定In查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $condition 查询条件
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereIn($field, $condition, $logic = NULL)
    {
    }

    /**
     * 指定NotIn查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $condition 查询条件
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereNotIn($field, $condition, $logic = NULL)
    {
    }

    /**
     * 指定Like查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $condition 查询条件
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereLike($field, $condition, $logic = NULL)
    {
    }

    /**
     * 指定NotLike查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $condition 查询条件
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereNotLike($field, $condition, $logic = NULL)
    {
    }

    /**
     * 指定Between查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $condition 查询条件
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereBetween($field, $condition, $logic = NULL)
    {
    }

    /**
     * 指定NotBetween查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param mixed $condition 查询条件
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereNotBetween($field, $condition, $logic = NULL)
    {
    }

    /**
     * 比较两个字段
     * @access public
     * @param string|array $field1 查询字段
     * @param string $operator 比较操作符
     * @param string $field2 比较字段
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereColumn($field1, $operator, $field2 = NULL, $logic = NULL)
    {
    }

    /**
     * 设置软删除字段及条件
     * @access public
     * @param false|string $field 查询字段
     * @param mixed $condition 查询条件
     * @return $this
     */
    public function useSoftDelete($field, $condition = NULL)
    {
    }

    /**
     * 指定Exp查询条件
     * @access public
     * @param mixed $field 查询字段
     * @param string $where 查询条件
     * @param array $bind 参数绑定
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function whereExp($field, $where, array $bind = NULL, $logic = NULL)
    {
    }

    /**
     * 分析查询表达式
     * @access public
     * @param string $logic 查询逻辑 and or xor
     * @param mixed $field 查询字段
     * @param mixed $op 查询表达式
     * @param mixed $condition 查询条件
     * @param array $param 查询参数
     * @param bool $strict 严格模式
     * @return $this
     */
    protected function parseWhereExp($logic, $field, $op, $condition, $param = NULL, $strict = NULL)
    {
    }

    /**
     * 分析查询表达式
     * @access protected
     * @param string $logic 查询逻辑 and or xor
     * @param mixed $field 查询字段
     * @param mixed $op 查询表达式
     * @param mixed $condition 查询条件
     * @param array $param 查询参数
     * @return mixed
     */
    protected function parseWhereItem($logic, $field, $op, $condition, $param = NULL)
    {
    }

    /**
     * 数组批量查询
     * @access protected
     * @param array $field 批量查询
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    protected function parseArrayWhereItems($field, $logic)
    {
    }

    /**
     * 去除某个查询条件
     * @access public
     * @param string $field 查询字段
     * @param string $logic 查询逻辑 and or xor
     * @return $this
     */
    public function removeWhereField($field, $logic = NULL)
    {
    }

    /**
     * 去除查询参数
     * @access public
     * @param string|bool $option 参数名 true 表示去除所有参数
     * @return $this
     */
    public function removeOption($option = NULL)
    {
    }

    /**
     * 条件查询
     * @access public
     * @param mixed $condition 满足条件（支持闭包）
     * @param Closure|array $query 满足条件后执行的查询表达式（闭包或数组）
     * @param Closure|array $otherwise 不满足条件后执行
     * @return $this
     */
    public function when($condition, $query, $otherwise = NULL)
    {
    }

    /**
     * 指定查询数量
     * @access public
     * @param mixed $offset 起始位置
     * @param mixed $length 查询数量
     * @return $this
     */
    public function limit($offset, $length = NULL)
    {
    }

    /**
     * 指定分页
     * @access public
     * @param mixed $page 页数
     * @param mixed $listRows 每页数量
     * @return $this
     */
    public function page($page, $listRows = NULL)
    {
    }

    /**
     * 分页查询
     * @param int|array $listRows 每页数量 数组表示配置参数
     * @param int|bool $simple 是否简洁模式或者总记录数
     * @param array $config 配置参数
     *                            page:当前页,
     *                            path:url路径,
     *                            query:url额外参数,
     *                            fragment:url锚点,
     *                            var_page:分页变量,
     *                            list_rows:每页数量
     *                            type:分页类名
     * @return Paginator
     * @throws DbException
     */
    public function paginate($listRows = NULL, $simple = NULL, $config = NULL)
    {
    }

    /**
     * 指定当前操作的数据表
     * @access public
     * @param mixed $table 表名
     * @return $this
     */
    public function table($table)
    {
    }

    /**
     * USING支持 用于多表删除
     * @access public
     * @param mixed $using
     * @return $this
     */
    public function using($using)
    {
    }

    /**
     * 指定排序 order('id','desc') 或者 order(['id'=>'desc','create_time'=>'desc'])
     * @access public
     * @param string|array $field 排序字段
     * @param string $order 排序
     * @return $this
     */
    public function order($field, $order = NULL)
    {
    }

    /**
     * 表达式方式指定Field排序
     * @access public
     * @param string $field 排序字段
     * @param array $bind 参数绑定
     * @return $this
     */
    public function orderRaw($field, $bind = NULL)
    {
    }

    /**
     * 指定Field排序 order('id',[1,2,3],'desc')
     * @access public
     * @param string|array $field 排序字段
     * @param string $values 排序值
     * @param string $order
     * @return $this
     */
    public function orderField($field, array $values = NULL, $order = NULL)
    {
    }

    /**
     * 随机排序
     * @access public
     * @return $this
     */
    public function orderRand()
    {
    }

    /**
     * 查询缓存
     * @access public
     * @param mixed $key 缓存key
     * @param integer|DateTime $expire 缓存有效期
     * @param string $tag 缓存标签
     * @return $this
     */
    public function cache($key = NULL, $expire = NULL, $tag = NULL)
    {
    }

    /**
     * 指定group查询
     * @access public
     * @param string $group GROUP
     * @return $this
     */
    public function group($group)
    {
    }

    /**
     * 指定having查询
     * @access public
     * @param string $having having
     * @return $this
     */
    public function having($having)
    {
    }

    /**
     * 指定查询lock
     * @access public
     * @param bool|string $lock 是否lock
     * @return $this
     */
    public function lock($lock = NULL)
    {
    }

    /**
     * 指定distinct查询
     * @access public
     * @param string $distinct 是否唯一
     * @return $this
     */
    public function distinct($distinct)
    {
    }

    /**
     * 指定数据表别名
     * @access public
     * @param mixed $alias 数据表别名
     * @return $this
     */
    public function alias($alias)
    {
    }

    /**
     * 指定强制索引
     * @access public
     * @param string $force 索引名称
     * @return $this
     */
    public function force($force)
    {
    }

    /**
     * 查询注释
     * @access public
     * @param string $comment 注释
     * @return $this
     */
    public function comment($comment)
    {
    }

    /**
     * 获取执行的SQL语句
     * @access public
     * @param boolean $fetch 是否返回sql
     * @return $this
     */
    public function fetchSql($fetch = NULL)
    {
    }

    /**
     * 不主动获取数据集
     * @access public
     * @param bool $pdo 是否返回 PDOStatement 对象
     * @return $this
     */
    public function fetchPdo($pdo = NULL)
    {
    }

    /**
     * 设置是否返回数据集对象（支持设置数据集对象类名）
     * @access public
     * @param bool|string $collection 是否返回数据集对象
     * @return $this
     */
    public function fetchCollection($collection = NULL)
    {
    }

    /**
     * 设置从主服务器读取数据
     * @access public
     * @return $this
     */
    public function master()
    {
    }

    /**
     * 设置是否严格检查字段名
     * @access public
     * @param bool $strict 是否严格检查字段
     * @return $this
     */
    public function strict($strict = NULL)
    {
    }

    /**
     * 设置查询数据不存在是否抛出异常
     * @access public
     * @param bool $fail 数据不存在是否抛出异常
     * @return $this
     */
    public function failException($fail = NULL)
    {
    }

    /**
     * 设置自增序列名
     * @access public
     * @param string $sequence 自增序列名
     * @return $this
     */
    public function sequence($sequence = NULL)
    {
    }

    /**
     * 设置需要隐藏的输出属性
     * @access public
     * @param mixed $hidden 需要隐藏的字段名
     * @return $this
     */
    public function hidden($hidden)
    {
    }

    /**
     * 设置需要输出的属性
     * @access public
     * @param array $visible 需要输出的属性
     * @return $this
     */
    public function visible(array $visible)
    {
    }

    /**
     * 设置需要追加输出的属性
     * @access public
     * @param array $append 需要追加的属性
     * @return $this
     */
    public function append(array $append)
    {
    }

    /**
     * 设置数据字段获取器
     * @access public
     * @param string|array $name 字段名
     * @param callable $callback 闭包获取器
     * @return $this
     */
    public function withAttr($name, $callback = NULL)
    {
    }

    /**
     * 设置JSON字段信息
     * @access public
     * @param array $json JSON字段
     * @param bool $assoc 是否取出数组
     * @return $this
     */
    public function json(array $json = NULL, $assoc = NULL)
    {
    }

    /**
     * 设置字段类型信息
     * @access public
     * @param array $type 字段类型信息
     * @return $this
     */
    public function setJsonFieldType(array $type)
    {
    }

    /**
     * 获取字段类型信息
     * @access public
     * @param string $field 字段名
     * @return string|null
     */
    public function getJsonFieldType($field)
    {
    }

    /**
     * 添加查询范围
     * @access public
     * @param array|string|Closure $scope 查询范围定义
     * @param array $args 参数
     * @return $this
     */
    public function scope($scope, $args)
    {
    }

    /**
     * 指定数据表主键
     * @access public
     * @param string $pk 主键
     * @return $this
     */
    public function pk($pk)
    {
    }

    /**
     * 查询日期或者时间
     * @access public
     * @param string $name 时间表达式
     * @param string|array $rule 时间范围
     * @return $this
     */
    public function timeRule($name, $rule)
    {
    }

    /**
     * 查询日期或者时间
     * @access public
     * @param string $field 日期字段名
     * @param string|array $op 比较运算符或者表达式
     * @param string|array $range 比较范围
     * @return $this
     */
    public function whereTime($field, $op, $range = NULL)
    {
    }

    /**
     * 查询日期或者时间范围
     * @access public
     * @param string $field 日期字段名
     * @param string $startTime 开始时间
     * @param string $endTime 结束时间
     * @return $this
     */
    public function whereBetweenTime($field, $startTime, $endTime = NULL)
    {
    }

    /**
     * 获取当前数据表的主键
     * @access public
     * @param string|array $options 数据表名或者查询参数
     * @return string|array
     */
    public function getPk($options = NULL)
    {
    }

    /**
     * 参数绑定
     * @access public
     * @param mixed $value 绑定变量值
     * @param integer $type 绑定类型
     * @param string $name 绑定标识
     * @return $this|string
     */
    public function bind($value, $type = 2, $name = NULL)
    {
    }

    /**
     * 参数绑定
     * @access public
     * @param string $sql 绑定的sql表达式
     * @param array $bind 参数绑定
     * @return void
     */
    protected function bindParams($sql, array $bind = NULL)
    {
    }

    /**
     * 检测参数是否已经绑定
     * @access public
     * @param string $key 参数名
     * @return bool
     */
    public function isBind($key)
    {
    }

    /**
     * 查询参数赋值
     * @access public
     * @param string $name 参数名
     * @param mixed $value 值
     * @return $this
     */
    public function option($name, $value)
    {
    }

    /**
     * 查询参数赋值
     * @access protected
     * @param array $options 表达式参数
     * @return $this
     */
    protected function options(array $options)
    {
    }

    /**
     * 获取当前的查询参数
     * @access public
     * @param string $name 参数名
     * @return mixed
     */
    public function getOptions($name = NULL)
    {
    }

    /**
     * 设置当前的查询参数
     * @access public
     * @param string $option 参数名
     * @param mixed $value 参数值
     * @return $this
     */
    public function setOption($option, $value)
    {
    }

    /**
     * 设置关联查询JOIN预查询
     * @access public
     * @param string|array $with 关联方法名称
     * @return $this
     */
    public function with($with)
    {
    }

    /**
     * 关联预载入 JOIN方式（不支持嵌套）
     * @access protected
     * @param string|array $with 关联方法名
     * @param string $joinType JOIN方式
     * @return $this
     */
    public function withJoin($with, $joinType = NULL)
    {
    }

    /**
     * 使用搜索器条件搜索字段
     * @access public
     * @param array $fields 搜索字段
     * @param array $data 搜索数据
     * @param string $prefix 字段前缀标识
     * @return $this
     */
    public function withSearch(array $fields, array $data = NULL, $prefix = NULL)
    {
    }

    /**
     * 关联统计
     * @access protected
     * @param string|array $relation 关联方法名
     * @param string $aggregate 聚合查询方法
     * @param string $field 字段
     * @param bool $subQuery 是否使用子查询
     * @return $this
     */
    protected function withAggregate($relation, $aggregate = NULL, $field = NULL, $subQuery = NULL)
    {
    }

    /**
     * 关联统计
     * @access public
     * @param string|array $relation 关联方法名
     * @param bool $subQuery 是否使用子查询
     * @return $this
     */
    public function withCount($relation, $subQuery = NULL)
    {
    }

    /**
     * 关联统计Sum
     * @access public
     * @param string|array $relation 关联方法名
     * @param string $field 字段
     * @param bool $subQuery 是否使用子查询
     * @return $this
     */
    public function withSum($relation, $field, $subQuery = NULL)
    {
    }

    /**
     * 关联统计Max
     * @access public
     * @param string|array $relation 关联方法名
     * @param string $field 字段
     * @param bool $subQuery 是否使用子查询
     * @return $this
     */
    public function withMax($relation, $field, $subQuery = NULL)
    {
    }

    /**
     * 关联统计Min
     * @access public
     * @param string|array $relation 关联方法名
     * @param string $field 字段
     * @param bool $subQuery 是否使用子查询
     * @return $this
     */
    public function withMin($relation, $field, $subQuery = NULL)
    {
    }

    /**
     * 关联统计Avg
     * @access public
     * @param string|array $relation 关联方法名
     * @param string $field 字段
     * @param bool $subQuery 是否使用子查询
     * @return $this
     */
    public function withAvg($relation, $field, $subQuery = NULL)
    {
    }

    /**
     * 关联预加载中 获取关联指定字段值
     * example:
     * Model::with(['relation' => function($query){
     *     $query->withField("id,name");
     * }])
     *
     * @param string | array $field 指定获取的字段
     * @return $this
     */
    public function withField($field)
    {
    }

    /**
     * 设置当前字段添加的表别名
     * @access public
     * @param string $via
     * @return $this
     */
    public function via($via = NULL)
    {
    }

    /**
     * 设置关联查询
     * @access public
     * @param string|array $relation 关联名称
     * @return $this
     */
    public function relation($relation)
    {
    }

    /**
     * 插入记录
     * @access public
     * @param array $data 数据
     * @param boolean $replace 是否replace
     * @param boolean $getLastInsID 返回自增主键
     * @param string $sequence 自增序列名
     * @return integer|string
     */
    public function insert(array $data = NULL, $replace = NULL, $getLastInsID = NULL, $sequence = NULL)
    {
    }

    /**
     * 插入记录并获取自增ID
     * @access public
     * @param array $data 数据
     * @param boolean $replace 是否replace
     * @param string $sequence 自增序列名
     * @return integer|string
     */
    public function insertGetId(array $data, $replace = NULL, $sequence = NULL)
    {
    }

    /**
     * 批量插入记录
     * @access public
     * @param array $dataSet 数据集
     * @param boolean $replace 是否replace
     * @param integer $limit 每次写入数据限制
     * @return integer|string
     */
    public function insertAll(array $dataSet, $replace = NULL, $limit = NULL)
    {
    }

    /**
     * 通过Select方式插入记录
     * @access public
     * @param string $fields 要插入的数据表字段名
     * @param string $table 要插入的数据表名
     * @return integer|string
     * @throws PDOException
     */
    public function selectInsert($fields, $table)
    {
    }

    /**
     * 更新记录
     * @access public
     * @param mixed $data 数据
     * @return integer|string
     * @throws Exception
     * @throws PDOException
     */
    public function update(array $data = NULL)
    {
    }

    /**
     * 删除记录
     * @access public
     * @param mixed $data 表达式 true 表示强制删除
     * @return int
     * @throws Exception
     * @throws PDOException
     */
    public function delete($data = NULL)
    {
    }

    /**
     * 执行查询但只返回PDOStatement对象
     * @access public
     * @return PDOStatement|string
     */
    public function getPdo()
    {
    }

    /**
     * 使用游标查找记录
     * @access public
     * @param array|string|Query|Closure $data
     * @return Generator
     */
    public function cursor($data = NULL)
    {
    }

    /**
     * 查找记录
     * @access public
     * @param array|string|Query|Closure $data
     * @return Collection|array|PDOStatement|string
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function select($data = NULL)
    {
    }

    /**
     * 查询数据转换为模型数据集对象
     * @access protected
     * @param array $resultSet 数据集
     * @return ModelCollection
     */
    protected function resultSetToModelCollection(array $resultSet)
    {
    }

    /**
     * 处理数据集
     * @access public
     * @param array $resultSet
     * @return void
     */
    protected function resultSet($resultSet)
    {
    }

    /**
     * 查找单条记录
     * @access public
     * @param array|string|Query|Closure $data
     * @return array|null|PDOStatement|string|Model
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function find($data = NULL)
    {
    }

    /**
     * 处理空数据
     * @access protected
     * @return array|Model|null
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    protected function resultToEmpty()
    {
    }

    /**
     * 查找单条记录
     * @access public
     * @param mixed $data 主键值或者查询条件（闭包）
     * @param mixed $with 关联预查询
     * @param bool $cache 是否缓存
     * @param bool $failException 是否抛出异常
     * @return static|null
     * @throws exception\DbException
     */
    public function get($data, $with = NULL, $cache = NULL, $failException = NULL)
    {
    }

    /**
     * 查找单条记录 如果不存在直接抛出异常
     * @access public
     * @param mixed $data 主键值或者查询条件（闭包）
     * @param mixed $with 关联预查询
     * @param bool $cache 是否缓存
     * @return static|null
     * @throws exception\DbException
     */
    public function getOrFail($data, $with = NULL, $cache = NULL)
    {
    }

    /**
     * 查找所有记录
     * @access public
     * @param mixed $data 主键列表或者查询条件（闭包）
     * @param array|string $with 关联预查询
     * @param bool $cache 是否缓存
     * @return static[]|false
     * @throws exception\DbException
     */
    public function all($data = NULL, $with = NULL, $cache = NULL)
    {
    }

    /**
     * 分析查询表达式
     * @access public
     * @param mixed $data 主键列表或者查询条件（闭包）
     * @param string $with 关联预查询
     * @param bool $cache 是否缓存
     * @return Query
     */
    protected function parseQuery($data, $with, $cache)
    {
    }

    /**
     * 处理数据
     * @access protected
     * @param array $result 查询数据
     * @return void
     */
    protected function result($result)
    {
    }

    /**
     * 使用获取器处理数据
     * @access protected
     * @param array $result 查询数据
     * @param array $withAttr 字段获取器
     * @return void
     */
    protected function getResultAttr($result, $withAttr = NULL)
    {
    }

    /**
     * JSON字段数据转换
     * @access protected
     * @param array $result 查询数据
     * @param array $json JSON字段
     * @param bool $assoc 是否转换为数组
     * @param array $withRelationAttr 关联获取器
     * @return void
     */
    protected function jsonResult($result, $json = NULL, $assoc = NULL, $withRelationAttr = NULL)
    {
    }

    /**
     * 查询数据转换为模型对象
     * @access public
     * @param array $result 查询数据
     * @param array $options 查询参数
     * @param bool $resultSet 是否为数据集查询
     * @param array $withRelationAttr 关联字段获取器
     * @return void
     */
    protected function resultToModel($result, $options = NULL, $resultSet = NULL, $withRelationAttr = NULL)
    {
    }

    /**
     * 获取模型的更新条件
     * @access protected
     * @param array $options 查询参数
     */
    protected function getModelUpdateCondition(array $options)
    {
    }

    /**
     * 查询失败 抛出异常
     * @access public
     * @param array $options 查询参数
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    protected function throwNotFound($options = NULL)
    {
    }

    /**
     * 查找多条记录 如果不存在则抛出异常
     * @access public
     * @param array|string|Query|Closure $data
     * @return array|PDOStatement|string|Model
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function selectOrFail($data = NULL)
    {
    }

    /**
     * 查找单条记录 如果不存在则抛出异常
     * @access public
     * @param array|string|Query|Closure $data
     * @return array|PDOStatement|string|Model
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function findOrFail($data = NULL)
    {
    }

    /**
     * 查找单条记录 如果不存在则抛出异常
     * @access public
     * @param array|string|Query|Closure $data
     * @return array|PDOStatement|string|Model
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function findOrEmpty($data = NULL)
    {
    }

    /**
     * 分批数据返回处理
     * @access public
     * @param integer $count 每次处理的数据数量
     * @param callable $callback 处理回调方法
     * @param string|array $column 分批处理的字段名
     * @param string $order 字段排序
     * @return boolean
     * @throws DbException
     */
    public function chunk($count, $callback, $column = NULL, $order = NULL)
    {
    }

    /**
     * 获取绑定的参数 并清空
     * @access public
     * @param bool $clear
     * @return array
     */
    public function getBind($clear = NULL)
    {
    }

    /**
     * 创建子查询SQL
     * @access public
     * @param bool $sub
     * @return string
     * @throws DbException
     */
    public function buildSql($sub = NULL)
    {
    }

    /**
     * 视图查询处理
     * @access public
     * @param array $options 查询参数
     * @return void
     */
    protected function parseView($options)
    {
    }

    /**
     * 把主键值转换为查询条件 支持复合主键
     * @access public
     * @param array|string $data 主键数据
     * @return void
     * @throws Exception
     */
    public function parsePkWhere($data)
    {
    }

    /**
     * 分析表达式（可用于查询或者写入操作）
     * @access protected
     * @param Query $query 查询对象
     * @return array
     */
    protected function parseOptions()
    {
    }

    /**
     * 注册回调方法
     * @access public
     * @param string $event 事件名
     * @param callable $callback 回调方法
     * @return void
     */
    public static function event($event, $callback)
    {
    }

    /**
     * 触发事件
     * @access protected
     * @param string $event 事件名
     * @return bool
     */
    public function trigger($event)
    {
    }

}