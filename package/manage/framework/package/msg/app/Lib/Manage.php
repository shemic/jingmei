<?php namespace Msg\Lib;
use Dever;
class Manage
{
    # 发送消息
    public function showRecord($id, $type = 'code')
    {
        $info = Dever::db('msg/code')->find($id);
        if (is_object($info['record'])) {
            $info['record'] = (array)$info['record'];
        }
        if (is_array($info['record'])) {
            $info['record'] = Dever::json_encode($info['record']);
        }
        $result['type'] = 'string';
        $result['content'] = $info['record'];
        return $result;
    }

    # 获取消息模版标题
    public function getTemplateName($id, $name = '')
    {
        if ($id) {
            $info = Dever::db('msg/template')->find($id);
            return $info['name'];
        }
        return $name;
    }
}