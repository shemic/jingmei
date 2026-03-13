<?php namespace Work\Manage\Lib;
use Dever;
class Tool
{
    public function getList()
    {
       return Dever::load(Common::class)->getList('cate', ['type' => 3], 'tool');
    }
}