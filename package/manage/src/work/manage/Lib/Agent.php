<?php namespace Work\Manage\Lib;
use Dever;
class Agent
{
    public function getList()
    {
        return Dever::load(Common::class)->getList('cate', ['type' => 2], 'agent');
    }
}