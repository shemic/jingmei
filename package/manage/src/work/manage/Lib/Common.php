<?php namespace Work\Manage\Lib;
use Dever;
class Common
{
    public function update($db, $data)
    {
        if (empty($data['code'])) {
            $data['code'] = Dever::uuid();
        }
        return $data;
    }

    public function getList($key = 'agent', $where = [], $child = '')
    {
        $result = [];
        $where['status'] = 1;
        $data = Dever::db('work/' . $key)->select($where, ['col' => 'id,name']);
        if ($data && $child) {
            $i = 0;
            foreach ($data as $v) {
                $where = [$key . '_id' => $v['id'], 'status' => 1];
                $data = Dever::db('work/' . $child)->select($where, ['col' => 'id,name']);
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

    public function getName($id, $key = 'agent')
    {
        return Dever::db('work/' . $key)->columns($id, 'name');
    }
}