<?php

namespace SwooleKit\CoroDatabase\CoMysql;

use EasySwoole\Component\Pool\Exception\PoolEmpty;
use EasySwoole\Component\Pool\Exception\PoolException;
use EasySwoole\Component\Pool\PoolObjectInterface;
use Exception;
use Generator;
use Swoole\Coroutine\MySQL as CoroutineMysqlClient;
use SwooleKit\CoroDatabase\CoMysql;
use SwooleKit\CoroDatabase\CoMysqlConfig;
use SwooleKit\CoroDatabase\CoMysqlException;
use SwooleKit\CoroDatabase\Helper\PDOConst;
use SwooleKit\CoroDatabase\Helper\TrackContainer;
use think\db\Query;
use Throwable;

/**
 * 连接类
 * 作为放入连接池的最小粒度单位
 * Class CoMysqlConnection
 * @package SwooleKit\CoroDatabase\CoMysql
 */
class CoMysqlConnection implements PoolObjectInterface
{
    /**
     * 当前的协程客户端
     * @var CoroutineMysqlClient
     */
    protected $coMysqlClient;

    /**
     *当前数据库配置
     * @var CoMysqlConfig
     */
    protected $coMysqlConfig;

    /**
     * 默认不做大小写转换
     * @var int
     */
    protected $attrCase = PDOConst::CASE_NATURAL;

    // ----------------------------------------
    // | 以下在每次回收前必须清除避免带入下一次查询
    // ----------------------------------------

    /**
     * 当前的构建器类
     * @var CoMysqlBuilder
     */
    protected $coMysqlBuilder;

    /**
     * 查询语句
     * @var CoroutineMysqlClient\Statement
     */
    protected $COMStatement;

    /**
     * 原先的日志容器
     * @var TrackContainer
     */
    protected $trackContainer;

    /**
     * 当前查询语句
     * @var string
     */
    protected $queryStr = '';

    /**
     * 影响行数
     * @var integer
     */
    protected $numRows = 0;

    /**
     * 查询参数绑定
     * @var array
     */
    protected $bind = [];

    /**
     * 事务指令数
     * @var integer
     */
    protected $transTimes = 0;

    /**
     * 构造函数
     * CoMysqlConnection constructor.
     * @param CoMysqlConfig $coMysqlConfig
     */
    function __construct(CoMysqlConfig $coMysqlConfig)
    {
        // 建立必要的初始对象
        $this->coMysqlConfig = $coMysqlConfig;
        $this->coMysqlClient = new CoroutineMysqlClient;
        $this->coMysqlBuilder = new CoMysqlBuilder($this);

        // 执行初始化操作
        $this->initialize();
    }

    /**
     * 初始化
     * 在子类已经实现了该方法
     * @return void
     * @access protected
     */
    protected function initialize()
    {
        // Point类型支持
        CoMysqlQuery::extend('point', function ($query, $field, $value = null, $fun = 'GeomFromText', $type = 'POINT') {
            /** @var CoMysqlQuery $query */
            if (!is_null($value)) {
                $query->data($field, ['point', $value, $fun, $type]);
            } else {
                if (is_string($field)) {
                    $field = explode(',', $field);
                }
                $query->setOption('point', $field);
            }
            return $query;
        });
    }


    /**
     * 取得数据库连接类实例
     * @access public
     * @param mixed $config 连接配置
     * @param bool|string $name 连接标识 true 强制重新连接
     * @return CoMysqlConnection
     * @throws CoMysqlException
     * @throws PoolEmpty
     * @throws PoolException
     */
    public static function instance($config = [], $name = false)
    {
        return CoMysql::getConnection();
    }

    /**
     * 获取当前连接器类对应的Builder类
     * @return string
     */
    public function getBuilderClass()
    {
        return CoMysqlBuilder::class;
    }

    /**
     * 设置当前的数据库Builder对象
     * @access protected
     * @param CoMysqlBuilder $builder
     * @return CoMysqlConnection
     */
    protected function setBuilder(CoMysqlBuilder $builder)
    {
        $this->coMysqlBuilder = $builder;
        return $this;
    }

    /**
     * 获取当前的builder实例对象
     * @access public
     * @return CoMysqlBuilder
     */
    public function getBuilder()
    {
        if (!($this->coMysqlBuilder instanceof CoMysqlBuilder)) {
            $this->coMysqlBuilder = new CoMysqlBuilder($this);
        }
        return $this->coMysqlBuilder;
    }

    /**
     * 获取连接对象
     * 链接并不会切换此方法无意义
     * @access public
     * @return object|null
     */
    public function getLinkID()
    {
        return null;
    }

    /**
     * 解析pdo连接的dsn信息
     * @access protected
     * @param CoMysqlConfig $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        // 是否指定了端口 暂不支持sock模式
        $config = $config->toArray();
        if (!empty($config['hostport'])) {
            $dsn = 'mysql:host=' . $config['hostname'] . ';port=' . $config['hostport'];
        } else {
            $dsn = 'mysql:host=' . $config['hostname'];
        }

        $dsn .= ';dbname=' . $config['database'];
        if (!empty($config['charset'])) {
            $dsn .= ';charset=' . $config['charset'];
        }

        return $dsn;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @param string $tableName
     * @return array
     * @throws CoMysqlException
     * @throws Throwable
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

        $sql = 'SHOW COLUMNS FROM ' . $tableName;
        $stmt = $this->query($sql, [], false, true);
        $result = $stmt->fetchAll();
        $info = [];

        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);
                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => (bool)('' === $val['null']), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                ];
            }
        }

        return $this->fieldCase($info);
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @param string $dbName
     * @return array
     */
    public function getTables($dbName)
    {
        $sql    = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
        $pdo    = $this->query($sql, [], false, true);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];

        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    /**
     * SQL性能分析
     * @access protected
     * @param string $sql
     * @return array
     */
    protected function getExplain($sql)
    {
    }

    /**
     * 对返数据表字段信息进行大小写转换出来
     * @access public
     * @param array $info 字段信息
     * @return array
     */
    public function fieldCase($info)
    {
        // 字段大小写转换
        switch ($this->attrCase) {
            case PDOConst::CASE_LOWER:
                $info = array_change_key_case($info);
                break;
            case PDOConst::CASE_UPPER:
                $info = array_change_key_case($info, CASE_UPPER);
                break;
            case PDOConst::CASE_NATURAL:
            default:
                // 不做转换
        }

        return $info;
    }

    /**
     * 获取字段绑定类型
     * @access public
     * @param string $type 字段类型
     * @return integer
     */
    public function getFieldBindType($type)
    {
        if (0 === strpos($type, 'set') || 0 === strpos($type, 'enum')) {
            $bind = PDOConst::PARAM_STR;
        } elseif (preg_match('/(double|float|decimal|real|numeric)/is', $type)) {
            $bind = PDOConst::PARAM_FLOAT;
        } elseif (preg_match('/(int|serial|bit)/is', $type)) {
            $bind = PDOConst::PARAM_INT;
        } elseif (preg_match('/bool/is', $type)) {
            $bind = PDOConst::PARAM_BOOL;
        } else {
            $bind = PDOConst::PARAM_STR;
        }

        return $bind;
    }

    /**
     * 将SQL语句中的__TABLE_NAME__字符串替换成带前缀的表名（小写）
     * @access public
     * @param string $sql sql语句
     * @return string
     */
    public function parseSqlTable($sql)
    {
        if (false !== strpos($sql, '__')) {
            $prefix = $this->getConfig('prefix');
            $sql = preg_replace_callback("/__([A-Z0-9_-]+)__/sU", function ($match) use ($prefix) {
                return $prefix . strtolower($match[1]);
            }, $sql);
        }

        return $sql;
    }

    /**
     * 获取数据表信息
     * @access public
     * @param mixed $tableName 数据表名 留空自动获取
     * @param string $fetch 获取信息类型 包括 fields type bind pk
     * @return mixed
     */
    public function getTableInfo($tableName, $fetch = '')
    {
        if (is_array($tableName)) {
            $tableName = key($tableName) ?: current($tableName);
        }

        // 多表不获取字段信息
        if (strpos($tableName, ',')) {
            return false;
        } else {
            $tableName = $this->parseSqlTable($tableName);
        }

        // 修正子查询作为表名的问题
        if (strpos($tableName, ')')) {
            return [];
        }

        list($tableName) = explode(' ', $tableName);

        // 废弃了原有的缓存实现
        $info = $this->getFields($tableName);
        foreach ($info as $key => $val) {
            // 记录字段类型
            $type[$key] = $val['type'];
            $bind[$key] = $this->getFieldBindType($val['type']);
            if (!empty($val['primary'])) {
                $pk[] = $key;
            }
        }

        $fields = array_keys($info);
        $bind = $type = [];

        if (isset($pk)) {
            // 设置主键
            $pk = count($pk) > 1 ? $pk : $pk[0];
        } else {
            $pk = null;
        }

        $info = ['fields' => $fields, 'type' => $type, 'bind' => $bind, 'pk' => $pk];
        return $fetch ? $info[$fetch] : $info;
    }


    /**
     * 获取数据表的主键
     * @access public
     * @param string $tableName 数据表名
     * @return string|array
     */
    public function getPk($tableName)
    {
        return $this->getTableInfo($tableName, 'pk');
    }

    /**
     * 获取当前数据表字段信息
     * @param string $tableName 数据表名
     * @return mixed
     */
    public function getTableFields($tableName)
    {
        return $this->getTableInfo($tableName, 'fields');
    }

    /**
     * 获取当前数据表字段类型
     * @param string $tableName 数据表名
     * @return mixed
     */
    public function getFieldsType($tableName)
    {
        return $this->getTableInfo($tableName, 'type');
    }

    /**
     * 获取当前数据表绑定信息
     * @param string $tableName 数据表名
     * @return mixed
     */
    public function getFieldsBind($tableName)
    {
        return $this->getTableInfo($tableName, 'bind');
    }

    /**
     * 获取数据库的配置参数
     * @access public
     * @param string $config 配置名称
     * @return mixed
     */
    public function getConfig($config = '')
    {
        return $config === '' ? $this->coMysqlConfig : ($this->coMysqlConfig->$config ?? null);
    }

    /**
     * 设置数据库的配置参数
     * @access public
     * @param string|array $config 配置名称
     * @param mixed $value 配置值
     * @return void
     */
    public function setConfig($config, $value = '')
    {
        // 设置不允许修改
    }

    /**
     * 连接数据库方法
     * @access public
     * @param CoMysqlConfig $config 连接参数
     * @param integer $linkNum 连接序号
     * @param array|bool $autoConnection 是否自动连接主数据库（用于分布式）
     * @return CoroutineMysqlClient
     * @throws CoMysqlException
     */
    public function connect(CoMysqlConfig $config, $linkNum = 0, $autoConnection = false)
    {
        if (!$this->coMysqlClient->connected) {
            try {
                $connected = $this->coMysqlClient->connect($config->getSwooleClientConfig());
                if ($connected) {
                    return $this->coMysqlClient;
                } else {
                    $error = $this->coMysqlClient->connect_error == '' ? $this->coMysqlClient->error : $this->coMysqlClient->connect_error;
                    $errno = $this->coMysqlClient->connect_errno == 0 ? $this->coMysqlClient->errno : $this->coMysqlClient->connect_errno;
                    throw new Exception($error, $errno);
                }
            } catch (Throwable $throwable) {
                $dsn = $this->parseDsn($config);
                throw new CoMysqlException("[{$throwable->getCode()}] connect to {$dsn} failed: {$throwable->getMessage()}");
            }
        } else {
            return $this->coMysqlClient;
        }
    }

    /**
     * 释放查询结果
     * @access public
     */
    public function free()
    {
        $this->COMStatement = null;
    }

    /**
     * 获取协程客户端对象
     * @access public
     * @param bool $noConnection 不进行初始化链接
     * @return CoroutineMysqlClient|false
     * @throws CoMysqlException
     */
    public function getCoMysqlClient($noConnection = true)
    {
        // 当前不是一个客户端类则需要new
        if (!($this->coMysqlClient instanceof CoroutineMysqlClient)) {
            $this->coMysqlClient = new CoroutineMysqlClient;
        }

        return $noConnection ? $this->coMysqlClient : $this->connect($this->coMysqlConfig);
    }

    /**
     * 执行查询 使用生成器返回数据
     * @access public
     * @param string $sql sql指令
     * @param array $bind 参数绑定
     * @param bool $master 是否在主服务器读操作
     * @param Model $model 模型对象实例
     * @param array $condition 查询条件
     * @param mixed $relation 关联查询
     * @return Generator
     */
    public function getCursor($sql, $bind = NULL, $master = NULL, $model = NULL, $condition = NULL, $relation = NULL)
    {
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param string $sql sql指令
     * @param array $bind 参数绑定
     * @param bool $master 是否在主服务器读操作
     * @param bool $statement 是否返回Statement对象
     * @return array|CoroutineMysqlClient\Statement
     * @throws CoMysqlException
     * @throws Throwable
     */
    public function query($sql, $bind = [], $master = false, $statement = false)
    {
        $this->initConnect();

        // 记录绑定参数和执行的语句
        $this->bind = $bind;
        $this->queryStr = $sql;

        try {

            // 进行预处理和判断当前是否存储过程调用
            $this->COMStatement = $this->coMysqlClient->prepare($sql);
            $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);

            // 语句预处理完成后执行查询(和参数绑定是同一个步骤)
            if ($this->COMStatement) {
                $this->COMStatement->execute($this->bind);
                return $this->getResult($statement, $procedure);
            }

            // 如果执行到这里说明预处理失败了
            throw new Exception($this->coMysqlClient->error, $this->coMysqlClient->errno);

        } catch (Throwable $throwable) {
            throw $throwable;
        }
    }

    /**
     * 执行语句
     * 区别是这个方法只返回影响行数
     * @access public
     * @param string $sql sql指令
     * @param array $bind 参数绑定
     * @param CoMysqlQuery $query 查询对象
     * @return int
     * @throws CoMysqlException
     * @throws Throwable
     */
    public function execute($sql, $bind = [], CoMysqlQuery $query = null)
    {
        $this->initConnect();

        // 记录绑定参数和执行的语句
        $this->bind = $bind;
        $this->queryStr = $sql;

        try {

            // 进行预处理和判断当前是否存储过程调用
            $this->COMStatement = $this->coMysqlClient->prepare($sql);

            // 语句预处理完成后执行查询(和参数绑定是同一个步骤)
            if ($this->COMStatement) {
                $this->COMStatement->execute($this->bind);
                $this->numRows = $this->COMStatement->affected_rows;
                return $this->numRows;
            }

            // 如果执行到这里说明预处理失败了
            throw new Exception($this->coMysqlClient->error, $this->coMysqlClient->errno);

        } catch (Throwable $throwable) {
            throw $throwable;
        }

    }

    /**
     * 查找单条记录
     * @access public
     * @param Query $query 查询对象
     * @return array|null|PDOStatement|string
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function find(Query $query)
    {
    }

    /**
     * 使用游标查询记录
     * @access public
     * @param Query $query 查询对象
     * @return Generator
     */
    public function cursor(Query $query)
    {
    }

    /**
     * 获取缓存数据
     * @access protected
     * @param Query $query 查询对象
     * @param mixed $cache 缓存设置
     * @param array $options 缓存
     * @return mixed
     */
    protected function getCacheData(Query $query, $cache, $data, $key = NULL)
    {
    }

    /**
     * 查找记录
     * @access public
     * @param Query $query 查询对象
     * @return array|PDOStatement|string
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function select(Query $query)
    {
    }

    /**
     * 插入记录
     * @access public
     * @param Query $query 查询对象
     * @param boolean $replace 是否replace
     * @param boolean $getLastInsID 返回自增主键
     * @param string $sequence 自增序列名
     * @return integer|string
     */
    public function insert(Query $query, $replace = NULL, $getLastInsID = NULL, $sequence = NULL)
    {
    }

    /**
     * 批量插入记录
     * @access public
     * @param Query $query 查询对象
     * @param mixed $dataSet 数据集
     * @param bool $replace 是否replace
     * @param integer $limit 每次写入数据限制
     * @return integer|string
     * @throws Exception
     * @throws Throwable
     */
    public function insertAll(Query $query, $dataSet = NULL, $replace = NULL, $limit = NULL)
    {
    }

    /**
     * 通过Select方式插入记录
     * @access public
     * @param Query $query 查询对象
     * @param string $fields 要插入的数据表字段名
     * @param string $table 要插入的数据表名
     * @return integer|string
     * @throws PDOException
     */
    public function selectInsert(Query $query, $fields, $table)
    {
    }

    /**
     * 更新记录
     * @access public
     * @param Query $query 查询对象
     * @return integer|string
     * @throws Exception
     * @throws PDOException
     */
    public function update(Query $query)
    {
    }

    /**
     * 删除记录
     * @access public
     * @param Query $query 查询对象
     * @return int
     * @throws Exception
     * @throws PDOException
     */
    public function delete(Query $query)
    {
    }

    /**
     * 得到某个字段的值
     * @access public
     * @param Query $query 查询对象
     * @param string $field 字段名
     * @param bool $default 默认值
     * @return mixed
     */
    public function value(Query $query, $field, $default = NULL)
    {
    }

    /**
     * 得到某个列的数组
     * @access public
     * @param Query $query 查询对象
     * @param string $field 字段名 多个字段用逗号分隔
     * @param string $key 索引
     * @return array
     */
    public function column(Query $query, $field, $key = NULL)
    {
    }

    /**
     * 得到某个字段的值
     * @access public
     * @param Query $query 查询对象
     * @param string $aggregate 聚合方法
     * @param string $field 字段名
     * @return mixed
     */
    public function aggregate(Query $query, $aggregate, $field)
    {

    }

    /**
     * 执行查询但只返回PDOStatement对象
     * @access public
     * @param CoMysqlQuery $query
     */
    public function pdo(CoMysqlQuery $query)
    {
        // 因机制原因无法单独获取Statement
    }

    /**
     * 根据参数绑定组装最终的SQL语句 便于调试
     * @access public
     * @param string $sql 带参数绑定的sql语句
     * @param array $bind 参数绑定列表
     * @return string
     */
    public function getRealSql($sql, array $bind = NULL)
    {
        if (is_array($sql)) {
            $sql = implode(';', $sql);
        }

        foreach ($bind as $key => $val) {
            $value = is_array($val) ? $val[0] : $val;
            $type = is_array($val) ? $val[1] : PDOConst::PARAM_STR;

            if (PDOConst::PARAM_INT == $type || PDOConst::PARAM_FLOAT == $type) {
                $value = (float)$value;
            } elseif (PDOConst::PARAM_STR == $type) {
                $value = '\'' . addslashes($value) . '\'';
            }

            // 判断占位符
            $sql = is_numeric($key) ?
                substr_replace($sql, $value, strpos($sql, '?'), 1) :
                str_replace(':' . $key, $value, $sql);
        }

        return rtrim($sql);
    }

    /**
     * 参数绑定
     * 支持 ['name'=>'value','id'=>123] 对应命名占位符
     * 或者 ['value',123] 对应问号占位符
     * @access public
     * @param array $bind 要绑定的参数列表
     * @return void
     */
    protected function bindValue(array $bind = NULL)
    {
        // 由于Swoole只允许问号绑定 且无需提前绑定 废弃
    }

    /**
     * 存储过程的输入输出参数绑定
     * @access public
     * @param array $bind 要绑定的参数列表
     * @return void
     */
    protected function bindParam($bind)
    {
        // 由于Swoole只允许问号绑定 且无需提前绑定 废弃
    }

    /**
     * 获得数据集数组
     * @access protected
     * @param bool $stmt 是否返回Statement
     * @param bool $procedure 是否存储过程
     * @return array|CoroutineMysqlClient\Statement
     */
    protected function getResult($stmt = false, $procedure = false)
    {
        if ($stmt) {
            // 返回PDOStatement对象处理
            return $this->COMStatement;
        }

        if ($procedure) {
            // 存储过程返回结果
            return $this->procedure();
        }

        $result = $this->COMStatement->fetchAll(); // 废弃 fetchType 只能返回数组

        $this->numRows = count($result);

        return $result;
    }

    /**
     * 获得存储过程数据集
     * @access protected
     * @return array
     */
    protected function procedure()
    {
        $item = [];

        do {
            $result = $this->getResult();
            if ($result) {
                $item[] = $result;
            }
        } while ($this->COMStatement->nextResult());

        $this->numRows = count($item);

        return $item;
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
        $this->startTrans();
        try {
            $result = null;
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, [$this]);
            }
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 启动XA事务
     * @access public
     * @param string $xid XA事务id
     * @return void
     */
    public function startTransXa($xid)
    {
    }

    /**
     * 预编译XA事务
     * @access public
     * @param string $xid XA事务id
     * @return void
     */
    public function prepareXa($xid)
    {
    }

    /**
     * 提交XA事务
     * @access public
     * @param string $xid XA事务id
     * @return void
     */
    public function commitXa($xid)
    {
    }

    /**
     * 回滚XA事务
     * @access public
     * @param string $xid XA事务id
     * @return void
     */
    public function rollbackXa($xid)
    {
    }

    /**
     * 启动事务
     * @access public
     * @return void
     * @throws CoMysqlException
     */
    public function startTrans()
    {
        $this->initConnect();

        // 进入事务增加一层事务指令数
        ++$this->transTimes;

        // 仅有一次事务则开始事务 否则新建保存点
        // todo 需要考虑断线事务处理
        if (1 == $this->transTimes) {
            $this->coMysqlClient->begin();
        } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
            $this->coMysqlClient->query(
                $this->parseSavepoint('trans' . $this->transTimes)
            );
        }
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @access public
     * @return void
     * @throws CoMysqlException
     */
    public function commit()
    {
        $this->initConnect();

        if ($this->transTimes == 1) {
            $this->coMysqlClient->commit();
        }

        // 否则减去一层事务指令数
        --$this->transTimes;
    }

    /**
     * 事务回滚
     * 多层嵌套事务回滚支持
     * @access public
     * @return void
     * @throws CoMysqlException
     */
    public function rollback()
    {
        $this->initConnect();

        // 仅有一次事务则直接执行回滚 否则回滚当前保存点
        if ($this->transTimes == 1) {
            $this->coMysqlClient->rollBack();
        } elseif ($this->transTimes > 1 && $this->supportSavepoint()) {
            $this->coMysqlClient->query(
                $this->parseSavepointRollBack('trans' . $this->transTimes)
            );
        }

        // 每执行一次回滚都减去一层事务指令数
        $this->transTimes = max(0, $this->transTimes - 1);
    }

    /**
     * 是否支持事务嵌套
     * 当前Mysql支持保存点嵌套
     * @return bool
     */
    protected function supportSavepoint()
    {
        return true;
    }

    /**
     * 生成定义保存点的SQL
     * @param $name
     * @return string
     */
    protected function parseSavepoint($name)
    {
        return 'SAVEPOINT ' . $name;
    }

    /**
     * 生成回滚到保存点的SQL
     * @param $name
     * @return string
     */
    protected function parseSavepointRollBack($name)
    {
        return 'ROLLBACK TO SAVEPOINT ' . $name;
    }

    /**
     * 批处理执行SQL语句
     * 其实是查询的语法糖写法
     * @param array $sqlArray SQL批处理指令
     * @param array $bind 参数绑定
     * @return boolean
     * 批处理的指令都认为是execute操作
     * @throws CoMysqlException
     * @throws Throwable
     * @access public
     */
    public function batchQuery($sqlArray = NULL, $bind = NULL)
    {
        if (!is_array($sqlArray)) {
            return false;
        }

        // 自动启动事务支持
        $this->startTrans();

        try {
            foreach ($sqlArray as $sql) {
                $this->execute($sql, $bind);
            }
            // 提交事务
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 获得查询次数
     * @param boolean $execute 是否包含所有查询
     * @return integer
     * @todo 暂未记录执行次数
     * @access public
     */
    public function getQueryTimes($execute = false)
    {
        return 0;
    }

    /**
     * 获得执行次数
     * @return integer
     * @todo 暂未记录执行次数
     * @access public
     */
    public function getExecuteTimes()
    {
        return 0;
    }

    /**
     * 关闭数据库（或者重新连接）
     * @access public
     * @return $this
     */
    public function close()
    {
        // 断开数据库连接
        $this->coMysqlClient->close();

        return $this;
    }

    /**
     * 是否断线
     * @param Throwable $e 异常对象
     * @return bool
     * @todo 可以直接从客户端的Socket看状态
     * @access protected
     */
    protected function isBreak($e)
    {
        $info = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
        ];

        $error = $e->getMessage();

        foreach ($info as $msg) {
            if (false !== stripos($error, $msg)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取最近一次查询的sql语句
     * @access public
     * @return string
     */
    public function getLastSql()
    {
        return $this->getRealSql($this->queryStr, $this->bind);
    }

    /**
     * 获取最近插入的ID
     * @param string $sequence 自增序列名
     * @return string
     * @todo 暂不支持自增序列设置
     * @access public
     */
    public function getLastInsID($sequence = null)
    {
        return $this->coMysqlClient->insert_id;
    }

    /**
     * 获取返回或者影响的记录数
     * @access public
     * @return integer
     */
    public function getNumRows()
    {
        return $this->numRows;
    }

    /**
     * 获取最近的错误信息
     * @access public
     * @return string
     */
    public function getError()
    {
        if ($this->COMStatement) {
            $error = "[{$this->COMStatement->errno}] {$this->COMStatement->error}";
        } else {
            $error = '';
        }

        if ('' != $this->queryStr) {
            $error .= "\n [ SQL语句 ] : " . $this->getLastsql();
        }

        return $error;
    }

    /**
     * 数据库调试 记录当前SQL及分析性能
     * @param boolean $start 调试开始标记 true 开始 false 结束
     * @param string $sql 执行的SQL语句 留空自动获取
     * @param bool $master 主从标记
     * @return void
     * @todo 跟踪日志这块需要单独抽出来完善
     * @access protected
     */
    protected function debug($start, $sql = NULL, $master = NULL)
    {

    }

    /**
     * 监听SQL执行
     * @param callable $callback 回调方法
     * @return void
     * @todo 暂不支持事件监听
     * @access public
     */
    public function listen($callback)
    {

    }

    /**
     * 触发SQL事件
     * @param string $sql SQL语句
     * @param float $runtime SQL运行时间
     * @param mixed $explain SQL分析
     * @param bool $master 主从标记
     * @return bool
     * @todo 暂不支持事件触发
     * @access protected
     */
    protected function triggerSql($sql, $runtime, $explain = [], $master = false)
    {
        return true;
    }

    /**
     * 记录调试日志
     * @param $debug
     */
    public function logDebug($debug)
    {
        $this->trackContainer->stackPush($debug, TrackContainer::TYPE_DEBUG);
    }

    /**
     * 记录查询分析
     * @param $explain
     */
    public function logExplain($explain)
    {
        $this->trackContainer->stackPush($explain, TrackContainer::TYPE_EXPLAIN);
    }

    /**
     * 记录查询语句
     * @param $statement
     */
    public function logStatement(string $statement)
    {
        $this->trackContainer->stackPush($statement, TrackContainer::TYPE_STATEMENT);
    }

    /**
     * 获取执行日志
     * @return array
     */
    public function getSqlLog()
    {
        return $this->trackContainer->__toArray();
    }

    /**
     * 初始化数据库连接
     * @param boolean $master 是否主服务器
     * @return void
     * @throws CoMysqlException
     * @todo 尚未支持多库初始化
     * @access protected
     */
    protected function initConnect($master = NULL)
    {
        $this->connect($this->coMysqlConfig);
    }

    /**
     * 连接分布式服务器
     * @param boolean $master 主服务器
     * @return CoroutineMysqlClient
     * @throws CoMysqlException
     * @todo 尚未支持主从服务器区分
     * @access protected
     */
    protected function multiConnect($master = false)
    {
        return $this->connect($this->coMysqlConfig);
    }

    /**
     * 析构方法
     * 析构由连接池unset代为处理
     * @access public
     */
    public function __destruct()
    {

    }

    /**
     * 缓存数据
     * @param string $key 缓存标识
     * @param mixed $data 缓存数据
     * @param array $config 缓存参数
     * @todo 尚未支持 缓存数据
     * @access public
     */
    protected function cacheData($key, $data, $config = [])
    {

    }

    /**
     * 生成缓存标识
     * @param CoMysqlQuery $query 查询对象
     * @param mixed $value 缓存数据
     * @return string
     * @todo 尚未支持 生成缓存标识
     * @access protected
     */
    protected function getCacheKey(CoMysqlQuery $query, $value)
    {
        if (is_scalar($value)) {
            $data = $value;
        } elseif (is_array($value) && isset($value[1], $value[2]) && in_array($value[1], ['=', 'eq'])) {
            $data = $value[2];
        }

        $prefix = 'think:' . $this->getConfig('database') . '.';

        if (isset($data)) {
            return $prefix . $query->getTable() . '|' . $data;
        }

        try {
            return md5($prefix . serialize($query->getOptions()) . serialize($query->getBind(false)));
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 数据库连接参数解析
     * @param mixed $config
     * @return array
     * @todo 尚未支持 数据库连接参数解析
     * @access private
     */
    private static function parseConfig($config)
    {
        return [];
    }

    /**
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
     * @access private
     * @param string $dsnStr
     * @return array
     */
    private static function parseDsnConfig($dsnStr)
    {
        $info = parse_url($dsnStr);

        if (!$info) {
            return [];
        }

        $dsn = [
            'type'     => $info['scheme'],
            'username' => isset($info['user']) ? $info['user'] : '',
            'password' => isset($info['pass']) ? $info['pass'] : '',
            'hostname' => isset($info['host']) ? $info['host'] : '',
            'hostport' => isset($info['port']) ? $info['port'] : '',
            'database' => !empty($info['path']) ? ltrim($info['path'], '/') : '',
            'charset'  => isset($info['fragment']) ? $info['fragment'] : 'utf8',
        ];

        if (isset($info['query'])) {
            parse_str($info['query'], $dsn['params']);
        } else {
            $dsn['params'] = [];
        }

        return $dsn;
    }

    function gc()
    {
        // TODO: Implement gc() method.
    }

    function objectRestore()
    {
        // TODO: Implement objectRestore() method.
    }

    function beforeUse(): bool
    {
        // TODO: Implement beforeUse() method.
        return true;
    }
}