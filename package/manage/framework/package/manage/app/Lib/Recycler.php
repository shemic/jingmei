<?php namespace Manage\Lib;
use Dever;
class Recycler extends Auth
{
    # 获取后台展示需要的数据
    public function getData($where)
    {
        if (empty($where['table'])) {
            Dever::error('参数错误');
        }
        $data['head'] = $data['body'] = [];
        $page = new Page('list', $where['table']);
        $data['title'] = $page->getTitle() . '【回收站】';
        $data['field'] = $page->setting('field', $data['head']);

        $set['num'] = Dever::input('pgnum', '', '', 10);
        list($db, $menu) = Dever::load(Util::Class)->db($where['table']);
        $recycler = Dever::db('manage/recycler')->select(['table' => $db->config['load']], $set);
        foreach ($recycler as $k => $v) {
            $content = Dever::json_decode($v['content']);
            foreach ($content as $key => $value) {
                $content[$key] = $page->getValue($key, $value, $content);
            }
            $content['id'] = $v['id'];
            $data['body'][] = $content;
        }
        if ($data['head']) {
            $head = [];
            foreach ($data['head'] as $k => $v) {
                if ($v['type'] == 'show') {
                    $head[] = $v;
                }
            }
            $data['head'] = $head;
        }

        return $data;
    }
}