<?php namespace Manage\Lib;
use Dever;
class Config extends Auth
{
    public function getTree()
    {
        $data = Dever::db('manage/config')->select([]);
        $result = [];
        $result[] = [
            'id' => 'root',
            'name' => '全部配置',
            'children' => $data,
        ];
        return $result;
    }
}