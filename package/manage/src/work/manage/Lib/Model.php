<?php namespace Work\Manage\Lib;
use Dever;
class Model
{
    public function getList($type = false)
    {
        $result = [];
        $where = ['status' => 1];
        $platform = Dever::db('work/platform')->select($where, ['col' => 'id,name']);
        if ($platform) {
            $i = 0;
            foreach ($platform as $k => $v) {
                $where = ['platform_id' => $v['id'], 'status' => 1];
                if ($type) {
                    $where['type'] = $type;
                }
                $data = Dever::db('work/model')->select($where, ['col' => 'id,name']);
                if ($data) {
                    $v['children'] = $data;
                    $result[$i] = $v;
                    $i++;
                }
            }
        }
        return $result;
    }

    public function getName($value)
    {
        if ($value) {
            $value = explode(',', $value);
            $platform = Dever::db('work/platform')->column($value[0], 'name');
            $model = Dever::db('work/model')->column($value[1], 'name');
            return $platform . ' / ' . $model;
        }
        return '-';
    }
}