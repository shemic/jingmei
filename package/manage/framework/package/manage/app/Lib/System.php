<?php namespace Manage\Lib;
use Dever;
use Dever\Project;
use Dever\Helper\Str;
class System extends Auth
{
    public function getTree()
    {
        $data = Dever::db('manage/system')->select([]);
        $result = [];
        $result[] = [
            'id' => 'root',
            'name' => '全部系统',
            'children' => $data,
        ];
        return $result;
    }

    # 创建管理员
    public function update($system, $db, $data)
    {
        if (isset($data['mobile']) && $data['mobile']) {
            $system = Dever::db('manage/system')->find(['key' => $system]);
            $data['system_key'] = $system['key'];
            $data['system_id'] = $system['id'];
            $data['info_id'] = $data['id'];
            $data['partition'] = $system['partition'];
            $db = Dever::db($system['user_table'], 'default', Dever::load(Util::class)->system($data));
            $info = $db->find(['mobile' => $data['mobile']]);
            if (!$info) {
                $password = '123456';
                $insert['name'] = Str::hide($data['mobile']);
                $insert['mobile'] = $data['mobile'];
                $insert['role'] = 1;
                $insert += Dever::load(Util::class)->createPwd($password);
                $db->insert($insert);
            }

            $db = Dever::db($system['role_table'], 'default', Dever::load(Util::class)->system($data));
            $info = $db->find(['id' => 1]);
            if (!$info) {
                $insert = [];
                $insert['name'] = '超级管理员';
                $db->insert($insert);
            }
        }
    }

    # 创建账户
    public function createUser($data, $state = true)
    {
        if (isset($data['mobile']) && $data['mobile'] && isset($data['password']) && $data['password']) {
            $info = Dever::db($data['table'])->find($data['id']);
            if ($info) {
                $system = Dever::db('manage/system')->find(['key' => $data['system']]);
                $set['system_key'] = $system['key'];
                $set['system_id'] = $system['id'];
                $set['info_id'] = 1;
                $set['partition'] = $system['partition'];
                $db = Dever::db($system['user_table'], 'default', Dever::load(Util::class)->system($set));

                $user = $db->find(['mobile' => $data['mobile']]);
                if ($state && $user) {
                    Dever::error('手机号' . $data['mobile'] . '已存在，请更换手机号');
                }

                $module = Dever::db('manage/system_module')->find(['key' => $data['module'], 'system' => 'group']);
                $insert['name'] = $info['name'];
                $insert['mobile'] = $data['mobile'];
                $insert['role'] = 2;
                $insert['module_data'] = $module['id'] . '-' . $info['id'];
                $insert += Dever::load(Util::class)->createPwd($data['password']);
                if (!$user) {
                    $db->insert($insert);
                } else {
                    $module_data = $insert['module_data'];
                    unset($insert['module_data']);
                    if (!strstr($user['module_data'], $module_data)) {
                        $insert['module_data'] = $module_data . ',' . $user['module_data'];
                    }
                    $db->update($user['id'], $insert);
                }
            }
        }
        return $data;
    }
}