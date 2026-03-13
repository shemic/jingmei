<?php namespace Api\Lib;
use Dever;
class Sku
{
    # 获取当前最低价格
    public function getPrice($where, $spec_type, $app)
    {
        $result = [];
        if ($spec_type == 2) {
            $where['key'] = '-1';
            $sku = Dever::db($app . '/sku')->find($where);
        } elseif ($spec_type == 3) {
            $where['price'] = ['!=', 'null'];
            //$where['code'] = ['!=', 'null'];
            $sku = Dever::db($app . '/sku')->find($where, ['order' => 'price asc']);
        }
        if (isset($sku) && $sku && $sku['key']) {
            $result['price'] = $sku['price'];
            $result['id'] = $sku['id'];
            if (isset($sku['unum'])) {
                $result['unum'] = $sku['unum'];
            }
            $result['key'] = str_replace(',', '_', $sku['key']);
            $result['name'] = $this->getName($sku['key'], $app);
        } else {
            Dever::error('价格传入错误');
        }
        return $result;
    }

    # 获取价格信息
    public function getInfo($where, $app)
    {
        $where['state'] = 1;
        $info = Dever::db($app . '/sku')->find($where);
        if ($info) {
            unset($info['state']);
            unset($info['cdate']);
            $info['name'] = $this->getName($info['key'], $app);
            if (!$info['pic']) {
                $key = explode(',', $info['key']);
                $value = Dever::db($app . '/spec_value')->find($key[0], ['col' => 'id,value,pic']);
                if ($value && $value['pic']) {
                    $info['pic'] = $value['pic'];
                }
            }
        }
        return $info;
    }

    # 获取价格列表 废弃 一次性读取不好
    /*
    public function getList($where, $sku_id, $app)
    {
        $result = [];
        $where['state'] = 1;
        $sku = Dever::db($app . '/sku')->select($where);
        if ($sku) {
            $spec = [];
            $result['info'] = [];
            foreach ($sku as $k => $v) {
                unset($v['state']);
                unset($v['cdate']);
                $k = $v['key'];
                $v['name'] = $this->getName($v['key'], $app);
                //$result['price'][$k] = $v;
                if ($sku_id && $sku_id == $v['id']) {
                    $result['info'] = $v;
                    $spec = explode(',', $v['key']);
                }
            }
            $result['spec'] = Dever::load(Spec::class)->getList($where, $app, $spec);
        }
        return $result;
    }*/

    # 获取某个sku的名称
    public function getName($key, $app, $show = false)
    {
        if (!$key || $key == '-1') {
            return '默认规格';
        } else {
            $name = [];
            $where['id'] = ['in', $key];
            $data = Dever::db($app . '/spec_value')->select($where, array('order' => 'field(id, '.$key.')'));
            if ($data) {
                foreach ($data as $k => $v) {
                    $info = Dever::db($app . '/spec')->find($v['spec_id']);
                    if ($show) {
                        $name[] = $info['name'] . ':' . $v['value'];
                    } else {
                        $name[] = $v['value'];
                    }
                }
            }
            return implode(' & ', $name);
        }
    }

    # 获取
}