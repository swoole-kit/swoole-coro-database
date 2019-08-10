<?php

namespace SwooleKit\CoroDatabase\Helper;

use SplStack;

/**
 * 数据库的日志栈
 * Class TrackContainer
 * @package SwooleKit\CoroDatabase\Helper
 */
class TrackContainer
{
    /**
     * 日志栈
     * @var SplStack
     */
    protected $splStack;

    const TYPE_DEBUG = '[DEBUG]';
    const TYPE_EXPLAIN = '[EXPLAIN]';
    const TYPE_STATEMENT = '[STATEMENT]';

    /**
     * 架构函数
     * TrackContainer constructor.
     */
    function __construct()
    {
        $this->stackTruncate();
    }

    /**
     * 日志写入堆栈
     * @param $content
     * @param string $contentType
     */
    public function stackPush($content, $contentType = TrackContainer::TYPE_DEBUG)
    {
        $this->splStack->push(implode(' ', [$contentType, $content]));
    }

    /**
     * 栈导出为数组
     * @return array
     */
    public function __toArray()
    {
        $stackArray = [];
        $this->splStack->rewind();
        while ($this->splStack->valid()) {
            array_push($stackArray, $this->splStack->current());
            $this->splStack->next();
        }
        return array_reverse($stackArray);
    }

    /**
     * 清空当前栈并创建新的栈
     * 栈是后进先出并且设置遍历保持栈元素
     * @return void
     */
    public function stackTruncate()
    {
        unset($this->splStack);
        $this->splStack = new SplStack;
        $this->splStack->setIteratorMode(SplStack::IT_MODE_LIFO | SplStack::IT_MODE_KEEP);
    }
}