<?php namespace Shenzhu\Manage\Lib;
use Dever;
class Role
{
    public function update($db, $data)
    {
        $data['update'] = 1;
        return $data;
    }

    public function sync($db, $data)
    {
        $url = 'http://172.18.0.1:8089/shenzhu/sync_role?role_id=' . $data['id'];
        $data = Dever::curl($url)->result();
    }

    public function getName($value)
    {
        if ($value) {
            $value = explode(',', $value);
            $cate = Dever::db('shenzhu/cate')->find(['id' => $value[0]], ['col' => 'name']);
            $role = Dever::db('shenzhu/role')->find(['id' => $value[1]], ['col' => 'name']);
            return $cate['name'] . '/' . $role['name'];
        } else {
            return '-';
        }
    }

    public function getList()
    {
        $result = [];
        $where['status'] = 1;
        $data = Dever::db('shenzhu/cate')->select($where, ['col' => 'id,name']);
        if ($data) {
            $i = 0;
            foreach ($data as $v) {
                $where = ['cate_id' => $v['id'], 'status' => 1];
                $data = Dever::db('shenzhu/role')->select($where, ['col' => 'id,name']);
                if ($data) {
                    $v['children'] = $data;
                    $result[$i] = $v;
                    $i++;
                }
            }
        } else {
            $result = $data;
        }
        return $result;
    }
}