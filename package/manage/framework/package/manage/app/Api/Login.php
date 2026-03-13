<?php namespace Manage\Api;
use Dever;
use Dever\Helper\Str;
use Dever\Helper\Code;
use Manage\Lib\Util;
class Login
{
    # 获取系统信息
    public function getSystem()
    {
        $system = Dever::input('system', 'is_string', '系统', 'platform');
        $system = Dever::db('manage/system')->find(['key' => $system]);
        if (!$system) {
            Dever::error('当前系统不存在');
        }
        $system['placeholder'] = '请输入' . $system['name'] . '号';
        return $system;
    }

    # 登录
    public function act()
    {
        //$this->checkCode();
        $system = $this->getSystem();
        $number = Dever::input('number', '', $system['name'] . '号', 'default');
        $info = Dever::db($system['info_table'])->find(['number' => $number]);
        if (!$info) {
            Dever::error('登录失败，当前' . $system['name'] . '号错误');
        }
        if ($system['partition'] == 'no') {
            # 不分库
            $db = Dever::db($system['user_table']);
            $role_db = Dever::db($system['role_table']);
        } else {
            # 分库
            $info['info_id'] = $info['id'];
            $info['partition'] = $system['partition'];
            $info['system_key'] = $system['key'];
            $info['system_id'] = $system['id'];
            $partition = Dever::load(Util::class)->system($info);
            $db = Dever::db($system['user_table'], 'default', $partition);
            $role_db = Dever::db($system['role_table'], 'default', $partition);
        }
        $where['mobile'] = Dever::input('mobile', Dever::rule('mobile'), '手机号');
        $password = Dever::input('password', 'is_string', '密码');
        $admin = $db->find($where);
        if (!$admin) {
            $total = $db->find(1);
            if (!$total) {
                $insert['name'] = Str::hide($where['mobile']);
                $insert['mobile'] = $where['mobile'];
                $insert['role'] = 1;
                $insert += Dever::load(Util::class)->createPwd($password);
                $id = $db->insert($insert);
                $admin = $db->find($id);
            } else {
                Dever::error('登录失败');
            }
        }
        if (!$admin) {
            Dever::error('登录失败，管理员信息无效');
        }
        if ($admin['status'] == 2) {
            Dever::error('登录失败，账户已被封禁');
        }
        if (Dever::load(Util::class)->hash($password, $admin['salt']) != $admin['password']) {
            Dever::error('登录失败，账户密码无效');
        }
        # 根据角色获取module_id
        $system_user = Dever::db('manage/system_user')->find(['uid' => $admin['id'], 'system_id' => $system['id'], 'info_id' => $info['id']]);
        $module_id = $data_id = 0;
        if ($system_user) {
            $module_id = $system_user['module_id'];
            $data_id = $system_user['data_id'];
        } elseif ($admin['role']) {
            $module = '';
            $role = $role_db->select(array('id' => ['in', $admin['role']]));
            foreach ($role as $k => $v) {
                if ($v['module']) {
                    $module .= $v['module'] . ',';
                }
            }
            if ($module) {
                $where['id'] = ['in', $module];
            } else {
                $where['system'] = $system['key'];
            }
            $module = Dever::db('manage/system_module')->select($where);
            if ($module) {
                $module_id = $module[0]['id'];
                $child = Dever::db($module[0]['data_table'])->select([]);
                if ($child) {
                    if ($admin['module_data']) {
                        foreach ($child as $k => $v) {
                            $key = $module_id . '-' . $v['id'];
                            if (strstr($admin['module_data'], $key)) {
                                $data_id = $v['id'];
                                break;
                            }
                        }
                    } else {
                        $data_id = $child[0]['id'];
                    }
                }
            }
        }
        if (!$module_id || !$data_id) {
            Dever::error('登录失败，账户无效');
        }
        return Dever::load(Util::class)->token($admin['id'], $admin['mobile'], $system['partition'], $system['key'], $system['id'], $info['id'], $module_id, $data_id);
    }
    private function checkCode()
    {
        $code = Dever::input('verificationCode');
        if (!$code) {
            Dever::error('请输入验证码');
        }
        $save = Dever::session('code');
        if ($code != $save) {
            Dever::error('验证码错误');
        }
    }
    public function code()
    {
        echo Dever::session('code', Code::create(), 3600);die;
    }
    public function out()
    {
        return 'ok';
    }
    public function loadMenu()
    {
        return Dever::load(\Manage\Lib\Menu::class)->init();
    }
}