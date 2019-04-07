<?php
/**
 * Created by PhpStorm.
 * User: evalor
 * Date: 2019-04-05
 * Time: 18:08
 */

namespace SwooleKit\CoroDatabase;

/**
 *
 * Class CoroMysqlModel
 * @package SwooleKit\CoroDatabase
 */
class CoroMysqlModel
{
    protected $coroMysql;                         // 协程客户端
    protected $tableName;                         // 不含前缀的表名称
    protected $autoWriteTimestamp = false;        // 是否自动完成时间戳

    protected $createTimeField = 'create_time';   // 创建时间字段名
    protected $updateTimeField = 'update_time';   // 更新时间字段名


    /**
     * 模型统一使用外部传入的链接
     * CoroMysqlModel constructor.
     * @param CoroMysql $coroMysql
     */
    public function __construct(CoroMysql $coroMysql)
    {
        $this->coroMysql = $coroMysql;
    }

    /**
     * 使模型可以被静态调用
     * @param CoroMysql $coroMysql
     * @return CoroMysqlModel
     */
    static function call(CoroMysql $coroMysql)
    {
        $static = new static($coroMysql);
        return $static;
    }

    /**
     * 调用了未知的方法视为调用数据库
     * @param string $name 方法名称
     * @param mixed|array $arguments 方法参数
     * @return CoroMysql
     */
    function __call($name, $arguments)
    {
        return $this->coroMysql->$name(...$arguments);
    }


}