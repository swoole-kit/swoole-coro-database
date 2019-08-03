<?php

namespace SwooleKit\CoroDatabase\CoMysql;

use SwooleKit\CoroDatabase\CoMysql;

/**
 * 查询构造器
 * Class CoMysqlQuery
 * @package SwooleKit\CoroDatabase\CoMysql
 */
class CoMysqlQuery
{
    /**
     * 数据库连接
     * @var CoMysql
     */
    protected $coMysql;

    // 以下为构造器状态
    protected $pk;           // 当前数据表主键
    protected $name;         // 当前数据表名称(不含前缀)
    protected $bind = [];    // 当前绑定参数对
    protected $prefix = '';  // 当前数据表前缀
    protected $options = []; // 当前查询条件集

    // 日期查询快捷方式和日期表达式
    protected $timeExp = ['d' => 'today', 'w' => 'week', 'm' => 'month', 'y' => 'year'];
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
     * @param CoMysql|null $coMysql
     */
    public function __construct(CoMysql $coMysql = null)
    {
        $this->coMysql = $coMysql;
        $this->prefix = $this->coMysql->getCoMysqlConfig()->getPrefix();
    }

}