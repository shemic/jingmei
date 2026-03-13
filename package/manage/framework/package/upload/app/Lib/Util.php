<?php namespace Upload\Lib;
use Dever;
class Util
{
    public function getGroup($key = '', $name = '')
    {
        $data['key'] = Dever::input('group_key', 'is_string', '分组标识', $key);
        $info = Dever::db('upload/group')->find($data);
        $data['name'] = Dever::input('group_name', 'is_string', '分组名称', $name);
        if ($info) {
            Dever::db('upload/group')->update($info['id'], $data);
            $id = $info['id'];
        } else {
            $id = Dever::db('upload/group')->insert($data);
        }
        return $id;
    }

    public function getUser($token = '', $table = '', $uid = '')
    {
        $data['token'] = Dever::input('user_token', 'is_string', '用户标识', $token);
        $data['table'] = Dever::input('user_table', 'is_string', '用户表', $table);
        $data['table_id'] = Dever::input('user_id', 'is_numeric', '用户ID', $uid);
        $info = Dever::db('upload/user')->find($data);
        if ($info) {
            Dever::db('upload/user')->update($info['id'], $data);
            $id = $info['id'];
        } else {
            $id = Dever::db('upload/user')->insert($data);
        }
        return $id;
    }

    # 获取存储位置列表，不再使用save表，直接用万接
    public function getSaveList()
    {
        $data[-1] = '本地存储';
        $account = Dever::load(\Api\Lib\Account::class)->getList('save');
        if ($account) {
            foreach ($account as $k => $v) {
                $data[$v['id']] = $v['name'];
            }
        }
        return $data;
    }

    # 获取存储位置信息
    public function getSaveInfo($id, $project = 'api')
    {
        $data['type'] = 1;
        $data['method'] = 1;
        if ($id > 0) {
            $data = Dever::load(\Api\Lib\Account::class)->get($id, $project, true);
            if ($data['key'] == 'qiniu') {
                $data['type'] = 2;
            } elseif ($data['key'] == 'aliyun') {
                $data['type'] = 3;
            }
            $data['method'] = 1;
        }
        return $data;
    }
}