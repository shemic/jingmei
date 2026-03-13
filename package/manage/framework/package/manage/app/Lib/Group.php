<?php namespace Manage\Lib;
use Dever;
use Dever\Helper\Str;
class Group extends Auth
{
    public function getTree()
    {
        $data = Dever::db('manage/group')->select([]);
        $result = [];
        $result[] = [
            'id' => 'root',
            'name' => '全部集团',
            'children' => $data,
        ];
        return $result;
    }

    # 后续废弃，转移到system中
    public function update($data)
    {
        if ($data['mobile']) {
            $system = Dever::db('manage/system')->find(2);
            $data['system_key'] = $system['key'];
            $data['system_id'] = $system['id'];
            $data['info_id'] = $data['id'];
            $data['partition'] = $system['partition'];
            $db = Dever::db($system['user_table'], 'default', Dever::load(Util::class)->system($data));
            $info = $db->find(1);
            if (!$info) {
                $password = '123456';
                $insert['name'] = Str::hide($data['mobile']);
                $insert['mobile'] = $data['mobile'];
                $insert['role'] = 1;
                $insert += Dever::load(util::class)->createPwd($password);
                $db->insert($insert);
            }
        }
    }
    
    # 后续废弃，转移到system中
    # 创建账户
    public function createUser($module, $data_id, $name, $mobile, $password, $state = false)
    {
        if ($mobile && $password) {
            $system = Dever::db('manage/system')->find(2);
            $data['system_key'] = $system['key'];
            $data['system_id'] = $system['id'];
            $data['info_id'] = 1;
            $data['partition'] = $system['partition'];
            $db = Dever::db($system['user_table'], 'default', Dever::load(Util::class)->system($data));

            $info = $db->find(['mobile' => $mobile]);
            if ($state && $info) {
                Dever::error('手机号' . $mobile . '已存在，请更换手机号');
            }

            $module = Dever::db('manage/system_module')->find(['key' => $module, 'system' => 'group']);
            $insert['name'] = $name;
            $insert['mobile'] = $mobile;
            $insert['role'] = 2;
            $insert['module_data'] = $module['id'] . '-' . $data_id;
            if (!$info) {
                $insert += Dever::load(Util::class)->createPwd($password);
                $db->insert($insert);
            } else {
                $insert += Dever::load(Util::class)->createPwd($password);
                $module_data = $insert['module_data'];
                unset($insert['module_data']);
                if (!strstr($info['module_data'], $module_data)) {
                    $insert['module_data'] = $module_data . ',' . $info['module_data'];
                }
                $db->update($info['id'], $insert);
            }
        }
    }
}