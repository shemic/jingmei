<?php namespace Manage\Lib;
use Dever;
class Module extends Auth
{
    public function getTree()
    {
        $data = Dever::db('manage/system_module')->select([]);
        $result = [];
        $result[] = [
            'id' => 'root',
            'name' => '系统模块',
            'children' => $data,
        ];
        return $result;
    }
}