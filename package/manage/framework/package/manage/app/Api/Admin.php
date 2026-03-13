<?php namespace Manage\Api;
use Dever;
use Manage\Lib\Auth;
use Manage\Lib\Util;
class Admin extends Auth
{
    public function info()
    {
        $this->user['module']['show'] = true;
        $this->user['module']['id'] = (int) $this->user['select']['module_id'];
        $this->user['module']['name'] = '当前模块';
        $this->user['module']['list'] = $this->module();
        $this->user['module']['login'] = 'login';
        $this->user['module']['uri'] = ['system' => $this->system['key'], 'number' => $this->system_info['number']];
        return $this->user;
    }

    # 获取当前的模块列表
    public function module()
    {
        $where = [];
        if ($this->user['auth']['module']) {
            $where['id'] = ['in', $this->user['auth']['module']];
        } else {
            $where['system'] = $this->system['key'];
        }
        $result = [];
        $module = Dever::db('manage/system_module')->select($where);
        $i = 0;
        foreach ($module as $k => $v) {
            $data_where = $v['data_where'];
            if ($data_where) {
                if (strstr($data_where, '{uid}')) {
                    $data_where = str_replace('{uid}', $this->user['id'], $data_where);
                }
                $data_where = Dever::json_decode($data_where);
            } else {
                $data_where = [];
            }
            $child = Dever::db($v['data_table'])->select($data_where);
            
            if ($child) {
                $data = [];
                foreach ($child as $k1 => $v1) {
                    $v1['select'] = false;
                    if ($v['id'] == $this->user['select']['module_id'] && $v1['id'] == $this->user['select']['data_id']) {
                        $this->user['module']['name'] = $v1['name'];
                        $v1['select'] = true;
                    }
                    $key = $v['id'] . '-' . $v1['id'];
                    if ($this->user['module_data']) {
                        if (strstr($this->user['module_data'], $key)) {
                            $data[] = $v1;
                        }
                    } else {
                        $data[] = $v1;
                    }
                }
                if ($data) {
                    $result[$i] = $v;
                    $result[$i]['child'] = $data;
                    $i++;
                }
            }
        }
        if ($i <= 1) {
            $this->user['module']['show'] = false;
        }
        return $result;
    }

    # 根据角色获取模块下的数据
    public function getModuleData($value = false)
    {
        if (!$value) {
            $result['module_data']['option'] = [];
            return $result;
        }
        $result = [];
        $role = Dever::db($this->system['role_table'])->select(array('id' => ['in', $value]));
        if ($role) {
            $info = $module = [];
            foreach ($role as $k => $v) {
                if ($v['module']) {
                    $child = Dever::db('manage/system_module')->select(array('id' => ['in', $v['module']]));
                    if ($child) {
                        foreach ($child as $k1 => $v1) {
                            if (isset($info[$v1['id']])) {
                                continue;
                            }
                            $info[$v1['id']] = true;
                            $v1['value'] = 's-' . $v1['id'];
                            $v1['label'] = $v1['name'];
                            $v1['children'] = [];
                            $data = Dever::db($v1['data_table'])->select([], array('col' => 'concat('.$v1['id'].', \'-\', id) as value, name as label'));
                            if ($data) {
                                $v1['children'] = array_merge($v1['children'], $data);
                            }
                            $module[] = $v1;
                        }
                    }
                }
            }
            $result['module_data']['option'] = $module;
        }
        
        return $result;
    }

    # 切换模块
    public function setModule()
    {
        $module_id = Dever::input('module_id');
        $this->checkModule($this->user['select']['module_id']);
        $data_id = Dever::input('data_id');
        if ($this->user['module_data'] && !strstr($this->user['module_data'], $module_id . '-' . $data_id)) {
            Dever::error('无模块权限');
        }
        $result = Dever::load(Util::class)->token($this->user['id'], $this->user['mobile'], $this->user['select']['partition'], $this->user['select']['system_key'], $this->user['select']['system_id'], $this->user['select']['info_id'], $module_id, $data_id);
        return $result;
    }

    # 修改资料
    public function setInfo()
    {
        $username = Dever::input('username');
        $password = Dever::input('password');
        $data = [];
        if ($username) {
            $data['name'] = $username;
        }
        if ($password) {
            $data += Dever::load(Util::class)->createPwd($password);
        }
        $state = false;
        if ($data) {
            $state = Dever::db($this->system['user_table'])->update($this->uid, $data);
        }
        if (!$state) {
            Dever::error('修改失败');
        }
        return 'yes';
    }
}
