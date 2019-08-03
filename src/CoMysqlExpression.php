<?php

namespace SwooleKit\CoroDatabase;

/**
 * 原生查询表达式
 * Class CoMysqlExpression
 * @package SwooleKit\CoroDatabase
 */
class CoMysqlExpression
{
    /**
     * 查询表达式
     * @var string
     */
    protected $value;

    /**
     * 架构函数
     * CoMysqlExpression constructor.
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * 获取表达式
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * 字符串表示
     * 使本对象可直接和字符串拼接
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->value;
    }

}