<?php namespace Area\Api;
use Dever;
use Area\Lib\Data as Config;
class Data
{
	/**
     * 获取地区数据
     *
     * @return mixed
     */
    public function get()
    {
        return Dever::load(\Manage\Lib\Util::class)->cascader(3, function($level, $parent) {
            if ($level == 1) {
                $data = Dever::load(Config::class)->getProvince();
            } elseif ($level == 2) {
                $data = Dever::load(Config::class)->getCity($parent);
            } elseif ($level == 3) {
                $data = Dever::load(Config::class)->getCounty($parent);
            } elseif ($level == 4) {
                $data = Dever::load(Config::class)->getTown($parent);
            } else {
                $data = Dever::load(Config::class)->getVillage($parent);
            }
            return $data;
        });
    }

    # 获取区域状态
    public function getStatus($area)
    {
        $temp = explode(',', $area);
        $num = count($temp);
        if ($num == 4 && isset($temp[3]) && $temp[3] > 0) {
            # 街道
            $where['id'] = $temp[3];
            $table = 'town';
        } elseif ($num == 3 && isset($temp[2]) && $temp[2] > 0) {
            # 区县
            $where['id'] = $temp[2];
            $table = 'county';
        } elseif ($num == 2 && isset($temp[1]) && $temp[1] > 0) {
            # 城市
            $where['id'] = $temp[1];
            $table = 'city';
        }
        if ($table) {
            $where['clear'] = true;
            $info = Dever::db('area/' . $table)->find($where);
            if ($info && $info['status'] == 2) {
                return true;
            }
        }
        return false;
    }

    # 修改区域状态
    public function upStatus($area, $status = 2)
    {
        $table = '';
        $update['status'] = $status;
        $temp = explode(',', $area);
        $num = count($temp);
        if ($num == 4 && isset($temp[3]) && $temp[3] > 0) {
            # 街道
            $update['where_id'] = $temp[3];
            $table = 'town';
        } elseif ($num == 3 && isset($temp[2]) && $temp[2] > 0) {
            # 区县
            $update['where_id'] = $temp[2];
            $table = 'county';
        } elseif ($num == 2 && isset($temp[1]) && $temp[1] > 0) {
            # 城市
            $update['where_id'] = $temp[1];
            $table = 'city';
        }

        $state = false;
        if ($table) {
            $update['clear'] = true;
            $state = Dever::db('area/' . $table)->update($update);  
        }
        return $state;
    }

    /**
     * 获取三级地区数据：json格式，生成js文件
     *
     * @return mixed
     */
    public function createJson()
    {
        $path = Dever::data() . 'upload/';
        $create = Dever::input('create');
        if (!$create) {
            $create = 1;
        }
        $type = Dever::input('type');
        if (!$type) {
            $type = 'js';
        }
        if ($type == 'klist') {
            $file = $path . 'city.' . $type . '.js';
        } else {
            $file = $path . 'city.' . $type;
        }
        
        if (!is_file($file)) {
            $create = 2;
        }
        if ($create == 2) {
            $array = [];

            $klist = Dever::load(Config::class)->getProvince();

            if ($type == 'klist') {
                $province = $klist;
            } else {
                $province = array_merge($array, $klist);
            }
            $province_data = [];
            $city_data = [];
            $county_data = [];
            $town_data = [];

            foreach ($province as $k => $v) {
                $province_data[$k]['name'] = $v['name'];
                $province_data[$k]['id'] = $v['value'];

                if ($v['value'] <= 0) {
                    continue;
                }

                $klist[$k]['text'] = $v['name'];
                $klist[$k]['value'] = $v['value'];
                $klist[$k]['children'] = Dever::load(Config::class)->getCity($v['value']);

                if ($type == 'klist') {
                    $city = $klist[$k]['children'];
                } else {
                    $city = array_merge($array, $klist[$k]['children']);
                }

                foreach ($city as $k1 => $v1) {
                    $city_data[$v['value']][$k1]['province'] = $v['name'];
                    $city_data[$v['value']][$k1]['name'] = $v1['name'];
                    $city_data[$v['value']][$k1]['id'] = $v1['value'];

                    if ($v1['value'] <= 0) {
                        continue;
                    }

                    $klist[$k]['children'][$k1]['text'] = $v1['name'];
                    $klist[$k]['children'][$k1]['value'] = $v1['value'];
                    $klist[$k]['children'][$k1]['children'] = Dever::load(Config::class)->getCounty($v1['value']);

                    if ($type == 'klist') {
                        $county = $klist[$k]['children'][$k1]['children'];
                    } else {
                        $county = array_merge($array, $klist[$k]['children'][$k1]['children']);
                    }

                    foreach ($county as $k2 => $v2) {
                        $county_data[$v1['value']][$k2]['city'] = $v1['name'];
                        $county_data[$v1['value']][$k2]['name'] = $v2['name'];
                        $county_data[$v1['value']][$k2]['id'] = $v2['value'];

                        if ($v2['value'] <= 0) {
                            continue;
                        }

                        $klist[$k]['children'][$k1]['children'][$k2]['text'] = $v2['name'];
                        $klist[$k]['children'][$k1]['children'][$k2]['value'] = $v2['value'];
                        $klist[$k]['children'][$k1]['children'][$k2]['children'] = Dever::load(Config::class)->getTown($v2['value']);

                        if ($type == 'klist') {
                            $town = $klist[$k]['children'][$k1]['children'][$k2]['children'];
                        } else {
                            $town = array_merge($array, $klist[$k]['children'][$k1]['children'][$k2]['children']);
                        }

                        foreach ($town as $k3 => $v3) {
                            $town_data[$v2['value']][$k3]['county'] = $v2['name'];
                            $town_data[$v2['value']][$k3]['name'] = $v3['name'];
                            $town_data[$v2['value']][$k3]['id'] = $v3['value'];

                            if ($v3['value'] <= 0) {
                                continue;
                            }

                            $klist[$k]['children'][$k1]['children'][$k2]['children'][$k3]['text'] = $v3['name'];
                            $klist[$k]['children'][$k1]['children'][$k2]['children'][$k3]['value'] = $v3['value'];
                        }
                    }
                }
            }

            if ($type == 'klist') {
                 $content = 'var cities = ' . Dever::json_encode($klist) . ';';
            } elseif ($type == 'js') {
                $content = 'var provinces = ' . Dever::json_encode($province_data) . ';';
                $content .= 'var citys = ' . Dever::json_encode($city_data) . ';';
                $content .= 'var areas = ' . Dever::json_encode($county_data) . ';';
                $content .= 'var towns = ' . Dever::json_encode($town_data) . ';';
            } elseif ($type == 'plist') {
                $content = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<array>' . "\r\n";

                foreach ($province_data as $k => $v) {
                    $content .= '    <dict>
        <key>province</key>
        <string>'.$v['name'].'</string>
        <key>citys</key>
        <array>';

                    if (isset($city_data[$v['id']])) {
                        foreach ($city_data[$v['id']] as $k1 => $v1) {
                            $content .= "\r\n" . '            <dict>
                <key>city</key>
                <string>'.$v1['name'].'</string>
                <key>districts</key>
                <array>';

                            if (isset($county_data[$v1['id']])) {
                                foreach ($county_data[$v1['id']] as $k2 => $v2) {
                                    $content .= "\r\n" . '                    <string>'.$v2['name'].'</string>';
                                }

                                $content .= "\r\n                ";
                            }

                            $content .= '</array>' . "\r\n" . '            </dict>';
                        }

                        $content .= "\r\n        ";
                    }
                    

                    $content .= '</array>' . "\r\n" . '    </dict>' . "\r\n";
                }
                $content .= '</array>' . "\r\n" . '</plist>';
            }
            file_put_contents($file, $content);
        }
        return $file;
    }
}
