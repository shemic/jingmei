<?php namespace Api\Lib;
use Dever;
class Spec
{
    # 获取规格数据
    public function manage($table, $field, $id)
    {
        $result = [];
        if ($id) {
            $result = Dever::db($table)->select([$field => $id]);
            if ($result) {
                foreach ($result as &$v) {
                    $v['type'] = 'show';
                    $v['width'] = '100';
                    $v['show'] = true;
                    $v['fixed'] = true;
                    $v['key'] = $v['name'];
                    $value = Dever::db($table . '_value')->select([$field => $id, 'spec_id' => $v['id']]);
                    if ($value) {
                        foreach ($value as $k1 => $v1) {
                            $value[$k1]['parent'] = $v['key'];
                            $value[$k1]['name'] = $v1['value'];
                            $value[$k1]['key'] = $v1['value'];
                            if ($v1['is_checked'] == 1) {
                                $value[$k1]['checked'] = true;
                            } else {
                                $value[$k1]['checked'] = false;
                            }
                        }
                    }
                    $v['value'] = $value;
                }
            }
        }
        return $result;
    }

    # 获取列表
    public function getList($where, $sku_id, $app)
    {
        $select = [];
        $where['state'] = 1;
        if ($sku_id) {
            $sku = Dever::db($app . '/sku')->find(['id' => $sku_id]);
            if ($sku) {
                $select = explode(',', $sku['key']);
            }
        }
        $data = Dever::db($app . '/spec')->select($where, ['col' => 'id,name']);
        if ($data) {
            foreach ($data as &$v) {
                $w = $where;
                $w['spec_id'] = $v['id'];
                unset($v['id']);
                $v['value'] = Dever::db($app . '/spec_value')->select($w, ['col' => 'id,value,pic']);
                if ($select) {
                    foreach ($v['value'] as &$v1) {
                        $v1['selected'] = false;
                        if (in_array($v1['id'], $select)) {
                            $v1['selected'] = true;
                        }
                    }
                }
            }
        }
        return $data;
    }
}