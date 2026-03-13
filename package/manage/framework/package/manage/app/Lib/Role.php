<?php namespace Manage\Lib;
use Dever;
class Role extends Auth
{
    public function update($db, $data)
    {
        if ($data['auth']) {
            $auth = explode(',', $data['auth']);
            $data['auth'] = [];
            $module = $menu = $func = [];
            foreach ($auth as $k => $v) {
                if (!strstr($v, '-')) {
                    if (strstr($v, 'v')) {
                        $menu[] = trim($v, 'v');
                    } else {
                        $func[] = $v;
                    }
                    $data['auth'][] = $v;
                }
            }
            if ($func) {
                $funcData = Dever::db('manage/menu_func')->select(array('id' => ['in', $func]), ['group' => 'menu_id']);
                foreach ($funcData as $k => $v) {
                    $menu[] = $v['menu_id'];
                }
                $menuData = Dever::db('manage/menu')->select(array('id' => ['in', $menu]), ['group' => 'module_id']);
                foreach ($menuData as $k => $v) {
                    $module[] = $v['module_id'];
                }
            }
            $data['auth'] = implode(',', $data['auth']);
            $data['menu'] = implode(',', $menu);
            $data['module'] = implode(',', $module);
        }
        return $data;
    }

    public function getAuthData()
    {
        $result = [];
        $extend = Dever::load(Util::class)->extend();
        if ($extend && $extend['system_id']) {
            $system_id = $extend['system_id'];
        } else {
            $system_id = 1;
        }
        $info = Dever::db('manage/system')->find($system_id);
        $where = [];
        $where['system'] = $info['key'];
        $module = Dever::db('manage/system_module')->select($where);
        foreach ($module as $k => $v) {
            $result[$k]['value'] = 's-' . $v['id'];
            $result[$k]['label'] = $v['name'];
            $result[$k]['children'] = Dever::db('manage/menu')->tree(array('module_id' => $v['id'], 'show' => ['<', '3']), ['parent_id', '0', 'id'], [$this, 'getAuthInfo'], ['col' => 'id,name as label,parent_id,`key`,func']);
        }
        return $result;
    }
    public function getAuthInfo($k, $info)
    {
        if ($info['func'] == 1) {
            $info['value'] = 'm-' . $info['id'];
            $info['children'] = Dever::db('manage/menu_func')->select(['menu_id' => $info['id']], ['col' => 'id as value,name as label']);
            if (!$info['children']) {
                return [];
            }
        } else {
            $info['value'] = 'v' . $info['id'];
        }
        return $info;
    }
    # 展示系统
    public function showSystem($data)
    {
        return Dever::db('manage/system')->show(array('id' => ['in', $data]));
    }
    # 展示系统模块
    public function showModule($data)
    {
        return Dever::db('manage/system_module')->show(array('id' => ['in', $data]));
    }
    # 展示菜单
    public function showMenu($data)
    {
        return Dever::db('manage/menu')->show(array('id' => ['in', $data]));
    }
    # 展示权限
    public function showFunc($data)
    {
        return Dever::db('manage/menu_func')->show(array('id' => ['in', $data]));
    }
}