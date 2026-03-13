<?php namespace Manage\Api;
use Dever;
class Icon
{
    public function list()
    {
        $set['num'] = Dever::input('pgnum', '', '', 16);
        $key = Dever::input('title');
        $where = [];
        if ($key) {
            $where['key'] = ['like', $key];
        }
        $data['list'] = Dever::db('manage/icon')->select($where, $set);
        $data['total'] = Dever::page('total');
        return $data;
    }
}