<?php
namespace Area\Lib;
use Dever;
class Data 
{
    public function getProvince()
    {
        return Dever::db('area/province')->select(['status' => 1], ['col' => 'id,id as value,name']);
    }

    public function getCity($province_id)
    {
        if ($province_id) {
            $where['province_id'] = $province_id;
        }
        $where['status'] = 1;
        return Dever::db('area/city')->select($where, ['col' => 'id,id as value,name']);
    }

    public function getCounty($city_id)
    {
        if ($city_id) {
            $where['city_id'] = $city_id;
        }
        $where['status'] = 1;
        return Dever::db('area/county')->select($where, ['col' => 'id,id as value,name']);
    }

    public function getTown($county_id)
    {
        if ($county_id) {
            $where['county_id'] = $county_id;
        }
        $where['status'] = 1;
        return Dever::db('area/town')->select($where, ['col' => 'id,id as value,name']);
    }

    public function getVillage($town_id)
    {
        if ($town_id) {
            $where['town_id'] = $town_id;
        }
        $where['status'] = 1;
        return Dever::db('area/village')->select($where, ['col' => 'id,id as value,name']);
    }

    # 获取城市并根据首字母排序的
    public function getCityToFirst()
    {
        $result = [];
        $data = $this->getCity(false);
        if (Dever::import('pinyin')) {
            $result = Dever::sortPinyinFirst($data, 'pinyin_first');
        }
        return $result;
    }

    /**
     * 获取详细信息
     *
     * @return mixed
     */
    public function getInfo($area, $col = 'id')
    {
        if ($area) {
            $area = explode(',', $area);
            $result = [];
            foreach ($area as $k => $v) {
                if ($k == 0) {
                    $result[$k] = $this->getName('province', $v, true, $col);
                } elseif ($k == 1) {
                    $result[$k] = $this->getName('city', $v, true, $col);
                    if ($col == 'id' && isset($result[1]['name']) && $result[0]['name'] == $result[1]['name']) {
                        unset($result[1]);
                    }
                } elseif ($k == 2) {
                    $result[$k] = $this->getName('county', $v, true, $col);
                } elseif ($k == 3) {
                    $result[$k] = $this->getName('town', $v, true, $col);
                } elseif ($k == 4) {
                    $result[$k] = $this->getName('village', $v, true, $col);
                }
            }
            return $result;
        }
        return [];
    }

    /**
     * 根据地区id转成名称
     *
     * @return mixed
     */
    public function string($area, $im = ',', $name = '不限', $unset = true, $check = false)
    {
        if ($area) {
            if (is_string($area)) {
                $area = explode(',', $area);
            }
            
            $result = [];
            foreach ($area as $k => $v) {
                if ($k == 0) {
                    $result[$k] = $this->getName('province', $v, false, 'id', $name);
                } elseif ($k == 1) {
                    $result[$k] = $this->getName('city', $v, false, 'id', $name);
                    if (isset($result[0]) && $result[0] == $result[1] && $unset) {
                        unset($result[1]);
                    }
                } elseif ($k == 2) {
                    $parent = $area[0] . ',' . $area[1];
                    $result[$k] = $this->getName('county', $v, false, 'id', $name, $check, $parent);
                } elseif ($k == 3) {
                    $parent = $area[0] . ',' . $area[1] . ',' . $area[2];
                    $result[$k] = $this->getName('town', $v, false, 'id', $name, $check, $parent);
                } elseif ($k == 4) {
                    $result[$k] = $this->getName('village', $v, false, 'id', $name);
                } else {
                    $result[$k] = '';
                }
                if (isset($result[$k]) && !$result[$k]) {
                    unset($result[$k]);
                }
            }
            return implode($im, $result);
        }
        return '';
    }


    private function getName($table, $value, $state = false, $col = 'id', $name = '不限', $check = false, $area = [])
    {
        if (($col == 'id' && $value > 0) || ($col != 'id' && $value)) {
            $where[$col] = $value;
            $data = Dever::db('area/' . $table)->find($where);
            if ($state) {
                return $data;
            }
            if ($data) {
                $name = $data['name'];
                if ($check && $area && $data['area'] != $area) {
                    $name = '<font style="color:red">'.$name.'（错误）</font>';
                }
            }
        }
        return $name;
    }

    public function pinyin($data)
    {
        if (Dever::project('pinyin') && $data['name']) {
            $data['pinyin'] = Dever::load(\Pinyin\Lib\Convert::class)->getPinyin($data['name']);
            $data['pinyin_first'] = Dever::load(\Pinyin\Lib\Convert::class)->getPinyinFirst($data['name']);
        }
        return $data;
    }
}