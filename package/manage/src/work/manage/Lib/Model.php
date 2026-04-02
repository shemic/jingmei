<?php namespace Work\Manage\Lib;
use Dever;
class Model
{
    public function getList($type = false)
    {
        $result = [];
        $where = ['status' => 1];
        $platform = Dever::db('work/platform')->select($where, ['col' => 'id,name']);
        $modelType = Dever::db('work/model')->config['struct']['type']['value'];
        if ($platform) {
            $i = 0;
            foreach ($platform as $k => $v) {
                $where = ['platform_id' => $v['id'], 'status' => 1];
                if ($type) {
                    $where['type'] = $type;
                }
                $data = Dever::db('work/model')->select($where, ['col' => 'id,name,type']);
                if ($data) {
                    foreach ($data as $kk => $vv) {
                        $data[$kk]['name'] = $vv['name'] . '[' . $modelType[$vv['type']] . ']';
                    }
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

    public function getSelectName($value, $table, $field)
    {
        if ($value) {
            $data = Dever::db('work/' . $table)->select([$field => $value]);
            if ($data) {
                $result = [];
                foreach ($data as $k => $v) {
                    $result[] = $this->getName($v['model']);
                }
                return implode('<br>', $result);
            }
        }
        return '-';
    }
}