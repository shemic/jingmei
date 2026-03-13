<?php namespace Manage\Api;
use Dever;
use Manage\Lib\Auth;
class Menu extends Auth
{
    protected $top;
    protected $opened;
    public function info()
    {
        # 如果后续增加Root，就要这里置为false
        $this->top = true;
        $top = Dever::db('manage/menu')->select(['parent_id' => '0', 'module_id' => $this->user['select']['module_id']]);
        $result = $menu = [];
        $result[] = array
        (
            'path' => '/',
            'name' => 'Root',
            'component' => 'Layout',
            'meta' => [
                'title' => '首页',
                'icon' => 'home-2-line',
                'breadcrumbHidden' => true,
            ],
            'children' => array
            (
                array
                (
                    'path' => 'index',
                    'name' => 'Index',
                    'component' => '@/dever/index/index',
                    'meta' => [
                        'title' => '控制台',
                        'icon' => 'home-2-line',
                        'noClosable' => true,
                    ]
                ),
            )
        );
        $this->opened = [];
        foreach ($top as $v) {
            $v = $this->getMenu($v, '');
            if ($v) {
                $result[] = $v;
            }
        }
        return ['list' => $result, 'opened' => $this->opened];
    }
    private function getMenu($v, $parent = '')
    {
        $info = array
        (
            'path' => $parent ? '/' . $parent . '/' . $v['key'] : $v['key'],
            'name' => $parent ? $parent . '_' . $v['key'] : $v['key'],
            'meta' => [
                'title' => $v['name'],
                'icon' => $v['icon'],
                //'noClosable' => true,
                'breadcrumbHidden' => false,
                'dynamicNewTab' => true,
            ]
        );
        if ($v['show'] > 1) {
            $info['meta']['hidden'] = true;
        }
        if (isset($v['active']) && $v['active']) {
            $info['meta']['activeMenu'] = $v['active'];
        }
        if ($v['parent_id'] <= 0) {
            if ($this->top) {
                $info['path'] = '/' . $v['key'];
            } else {
                $this->top = true;
                $info['path'] = '/';
            }
            $info['component'] = 'Layout';
        }
        $where = ['parent_id' => $v['id'], 'module_id' => $this->user['select']['module_id']];
        $child = Dever::db('manage/menu')->select($where);
        if ($child) {
            foreach ($child as $v1) {
                if ($v1['level'] == 3 && $v1['show'] <= 2 && $this->checkMenu($v1['id'])) {
                    continue;
                }
                if (!$parent) {
                    $this->opened[] = '/' . $v['key'] . '/' . $v1['key'];
                }
                $children = $this->getMenu($v1, $v['key']);
                if ($children) {
                    $info['children'][] = $children;
                }
            }
            if (empty($info['children'])) {
                return [];
            }
        } elseif ($v['path']) {
            $info['component'] = '@/dever/page/' . $v['path'];
        }
        if (!$child) {
            if ($v['level'] == 3 && $v['show'] <= 2 && $this->checkMenu($v['id'])) {
                return false;
            }
        }
        return $info;
    }
}