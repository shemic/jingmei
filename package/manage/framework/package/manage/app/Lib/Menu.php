<?php namespace Manage\Lib;
use Dever;
use Dever\Project;
class Menu
{
    protected $icon;
    # 初始化菜单
    public function init($name = '')
    {
        $config = Dever::config('manage');
        if ($config) {
            $this->add('manage', $config);
        }
        if ($name) {
            $app = array($name => Dever::get(Project::class)->load($name));
        } else {
            $app = Dever::get(Project::class)->read();
        }
        foreach ($app as $k => $v) {
            $base = $v['path'] . 'manage/core.php';
            if (is_file($base)) {
                $core = include $base;
                if ($core) {
                    $k = strtolower($k);
                    $this->add($k, $core);
                }
            }
        }
        return 'ok';
    }
    private function add($app, $core)
    {
        if (isset($core['system'])) {
            foreach ($core['system'] as $k => $v) {
                $where = [];
                $where['key'] = $k;
                $data = $where;
                $data['name'] = $v['name'];
                $data['sort'] = $v['sort'];
                $data['partition'] = $v['partition'] ?? 'no';
                $data['info_table'] = $v['info_table'];
                $data['user_table'] = $v['user_table'];
                $data['role_table'] = $v['role_table'];
                Dever::db('manage/system')->up($where, $data);
            }
        }
        if (isset($core['module'])) {
            foreach ($core['module'] as $k => $v) {
                $where = [];
                $where['key'] = $k;
                $data = $where;
                $data['name'] = $v['name'];
                $data['sort'] = $v['sort'];
                $data['system'] = $v['system'];
                $data['data_table'] = $v['data_table'];
                if (isset($v['data_where']) && $v['data_where']) {
                    $data['data_where'] = Dever::json_encode($v['data_where']);
                }
                Dever::db('manage/system_module')->up($where, $data);
            }
        }
        if (isset($core['menu'])) {
            foreach ($core['menu'] as $k => $v) {
                $where = [];
                if (isset($v['app'])) {
                    $app = $v['app'];
                }
                $where['app'] = $app;
                $where['key'] = $k;
                if (isset($v['parent'])) {
                    $parent = Dever::db('manage/menu')->find(['key' => $v['parent']]);
                    if ($parent) {
                        $where['parent_id'] = $parent['id'];
                        $where['module_id'] = $parent['module_id'];
                        $where['level'] = 2;
                        if ($parent['parent_id']) {
                            $where['level'] = 3;
                        }
                    }
                } else {
                    $where['level'] = 1;
                }
                if (isset($v['module'])) {
                    $module = Dever::db('manage/system_module')->find(['key' => $v['module']]);
                    if ($module) {
                        $where['module_id'] = $module['id'];
                    }
                }
                $data = $where;
                $data['name'] = $v['name'];
                if (isset($v['icon']) && $v['icon']) {
                    $data['icon'] = $v['icon'];
                } else {
                    # 随机抽取
                    $data['icon'] = $this->getIcon();
                }
                $data['sort'] = $v['sort'];
                if (isset($v['show'])) {
                    $data['show'] = $v['show'];
                }
                if (isset($v['badge'])) {
                    $data['badge'] = $v['badge'];
                }
                if (isset($v['path'])) {
                    $data['path'] = $v['path'];
                } else {
                    $data['path'] = 'main';
                }
                if (isset($v['link'])) {
                    $data['link'] = $v['link'];
                }
                Dever::db('manage/menu')->up($where, $data);
            }
        }
    }
    public function getAll()
    {
        $data = Dever::db('manage/menu')->select(['parent_id' => '0']);
        return $data;
    }

    public function getIcon()
    {
        if (empty($this->icon)) {
            $this->icon = Dever::db('manage/icon')->select([]);
        }
        $key = array_rand($this->icon, 1);
        $icon = $this->icon[$key]['key'];
        $info = Dever::db('manage/menu')->find(['icon' => $icon]);
        if ($info) {
            return $this->getIcon();
        }
        return $icon;
    }
}