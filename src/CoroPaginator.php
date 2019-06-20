<?php

namespace SwooleKit\CoroDatabase;

/**
 * 分页数据对象
 * Class CoroPaginator
 * @package SwooleKit\CoroDatabase
 */
class CoroPaginator
{
    protected $items;
    protected $currentPage;
    protected $lastPage;
    protected $total;
    protected $listRows;
    protected $hasMore;

    /**
     * CoroPaginator constructor.
     * @param array|null $items 数据对象
     * @param integer $listRows 每页多少记录
     * @param integer $currentPage 当前的页码
     * @param integer $total 总的记录数
     */
    function __construct($items, $listRows, $currentPage, $total)
    {
        $this->items = $items;
        $this->total = $total;
        $this->listRows = $listRows;
        $this->currentPage = $currentPage;
        $this->lastPage = (int)ceil($total / $listRows);
        $this->hasMore = $this->currentPage < $this->lastPage;
    }

    /**
     * 转化为数组
     * @return array
     */
    function toArray()
    {
        return (array)$this->items;
    }

    /**
     * 总的记录数
     * @return int
     */
    function total()
    {
        return $this->total;
    }

    /**
     * 每页记录数
     * @return int
     */
    function listRows()
    {
        return $this->listRows;
    }

    /**
     * 当前的页码
     * @return int
     */
    function currentPage()
    {
        return $this->currentPage;
    }

    /**
     * 最后一页的页码
     * @return int
     */
    function lastPage()
    {
        return $this->lastPage;
    }

    /**
     * 是否仍有下一页
     * @return bool
     */
    function hasMore()
    {
        return $this->hasMore;
    }
}