<?php namespace Manage\Lib;
use Dever;
class Auth
{
    protected $login = true;
    public $uid;
    protected $user;
    protected $system;
    protected $system_info;
    protected $info;
    protected $func;
    public $data = [];
    public function __construct()
    {
        $info = Dever::load(Util::class)->auth();
        if (!$info && $this->login) {
            $info = [];
            $info['uid'] = 1;
            $info['extend']['system_id'] = 'no';
            $info['extend']['system_id'] = 1;
            $info['extend']['info_id'] = 1;
            $info['extend']['module_id'] = 1;
            $info['extend']['data_id'] = 1;
            //Dever::error('请先登录');
        }
        $this->system = Dever::db('manage/system')->find($info['extend']['system_id']);
        if (!$this->system) {
            Dever::error('当前系统不存在');
        }
        $this->system_info = Dever::db($this->system['info_table'])->find($info['extend']['info_id']);
        if (!$this->system) {
            Dever::error('当前系统设置错误');
        }

        $this->uid = $info['uid'];
        $this->user = Dever::db($this->system['user_table'])->find($this->uid);
        if (!$this->user) {
            Dever::error('请先登录');
        }
        $this->user['table'] = $this->system['user_table'];
        $this->user['auth'] = ['module' => '', 'menu' => '', 'func' => ''];
        if ($this->user['role']) {
            $role = Dever::db($this->system['role_table'])->select(array('id' => ['in', $this->user['role']]));
            foreach ($role as $k => $v) {
                $this->user['auth']['module'] .= $v['module'] . ',';
                $this->user['auth']['menu'] .= $v['menu'] . ',';
                $this->user['auth']['func'] .= $v['auth'] . ',';
            }
        }
        if ($this->user['auth']['module']) {
            $this->user['auth']['module'] = rtrim($this->user['auth']['module'], ',');
        }
        if ($this->user['auth']['menu']) {
            $this->user['auth']['menu'] = rtrim($this->user['auth']['menu'], ',');
        }
        if ($this->user['auth']['func']) {
            $this->user['auth']['func'] = ',' . $this->user['auth']['func'];
        }
        $this->user['select'] = $info['extend'] ?? false;
        if (!$this->user['select']) {
            # 分别为系统id，系统基本信息id，模块id，模块数据id
            $this->user['select'] = ['partition' => 'no', 'system_id' => 1, 'info_id' => 1, 'module_id' => 1, 'data_id' => 1];
        }
        $this->checkModule($this->user['select']['module_id']);
        Dever::setData('muser', $this->user);
    }

    # 设置功能权限
    public function getFunc($key, $name, $sort = 1, $param = '')
    {

        if (!$key) {
            $key = md5(base64_encode($name));
        }
        /*
        if ($param) {
            if (is_array($param)) {
                $param = Dever::json_encode($name);
            }
            $key = $key . '_' . md5($param);
        }*/

        if (!$this->menu) {
            return false;
        }
        $data['menu_id'] = $this->menu['id'];
        $data['key'] = $key;
        $key = $key . $data['menu_id'];
        if (isset($this->func[$key]['id'])) {
            return $this->func[$key]['id'];
        }
        $this->func[$key] = Dever::db('manage/menu_func')->find($data);
        $name = $this->menu['name'] . '-' . $name;
        if (!$this->func[$key]) {
            $data['name'] = $name;
            $data['sort'] = $sort;
            $id = Dever::db('manage/menu_func')->insert($data);
            Dever::db('manage/menu')->update($this->menu['id'], ['func' => 1]);
        } else {
            /*
            if ($info['name'] != $name) {
                $data['name'] = $name;
                $data['sort'] = $sort;
                Dever::db('manage/menu_func')->update($info['id'], $data);
                Dever::db('manage/menu')->update($this->menu['id'], ['func' => 1]);
            }*/
            $id = $this->func[$key]['id'];
        }

        if ($this->user['id'] == 1) {
            return $id;
        }
        if ($this->user['auth']['func'] && strstr($this->user['auth']['func'], ',' . $id . ',')) {
            return $id;
        }
        return false;
    }

    # 检测系统模块权限
    protected function checkModule($module_id)
    {
        if ($this->user['id'] == 1) {
            return;
        }
        if ($this->user['auth']['module'] && !Dever::check($this->user['auth']['module'], $module_id)) {
            Dever::error('无系统权限');
        }
    }

    # 检测菜单权限
    protected function checkMenu($menu, $result = true)
    {
        if ($this->user['id'] == 1) {
            if ($result) {
                return false;
            }
            return;
        }
        if ($this->user['auth']['menu'] && !Dever::check($this->user['auth']['menu'], $menu)) {
            if ($result) {
                return true;
            }
            Dever::error('无菜单访问权限');
        }
        if ($result) {
            return false;
        }
    }

    # 检测功能权限
    protected function checkFunc()
    {
        $id = Dever::input('func');
        if (!$id) {
            return false;
        }
        if ($this->user['id'] == 1) {
            return $id;
        }
        if ($this->user['auth']['func'] && strstr($this->user['auth']['func'], ',' . $id . ',')) {
            return $id;
        }
        if (isset($this->menu) && $this->menu && $this->menu['show'] != 1) {
            return $id;
        }
        Dever::error('无操作权限');
    }
}