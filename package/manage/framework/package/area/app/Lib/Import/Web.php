<?php namespace Area\Lib\Import;
set_time_limit(0);
use Dever;
use Area\Lib\Data;
/**
 * 获取国家统计局最新的地区数据
 *
 * @return mixed
 */
class Web extends Core
{
    private $url = 'https://www.stats.gov.cn/sj/tjbz/tjyqhdmhcxhfdm/2023/';

    public function getUrl()
    {
        return $this->url;
    }
    
    public function get()
    {
        $url = $this->url . 'index.html';
        
        $html = $this->html($url);

        preg_match_all('/<td><a href="(.*?)">(.*?)<br \/><\/a><\/td>/i', $html, $result);

        # 获取省份
        $this->getProvince($result);

        return 1;
    }

    public function getProvince($result)
    {
        $province = Dever::input('province');
        $update = [];
        if (isset($result[1]) && isset($result[2]) && $result[2]) {
            foreach ($result[2] as $k => $v) {
                $update['id'] = $this->id(trim($result[1][$k], '.html'));
                $update['name'] = strip_tags($v);
                $update = Dever::load(Data::class)->pinyin($update);
                $id = $this->up('province', $update['id'], $update);

                # 获取城市
                if ($province) {
                    if ($update['name'] == $province) {
                        $this->getCity($id, $update['name'], $result[1][$k]);
                    }
                } else {
                    $this->getCity($id, $update['name'], $result[1][$k]);
                }
            }
        }
    }

    public function getCity($province, $province_name, $link)
    {
        $city = Dever::input('city');

        $url = $this->url . $link;
        
        $html = $this->html($url);

        preg_match_all('/<tr class="citytr"><td><a href="(.*?)">(.*?)<\/a><\/td><td><a href="(.*?)">(.*?)<\/a><\/td><\/tr>/is', $html, $result);

        $update = [];
        if (isset($result[3]) && isset($result[4]) && $result[4]) {
            foreach ($result[4] as $k => $v) {
                $v = strip_tags($v);
                if ($v == '市辖区') {
                    $v = $province_name;
                }
                $update['id'] = $this->id($result[2][$k]);
                $update['name'] = $v;
                $update['province_id'] = $province;

                $update = Dever::load(Data::class)->pinyin($update);
                $id = $this->up('city', $update['id'], $update);

                if ($city) {
                    if ($update['name'] == $city) {
                        $this->getCounty($province, $id, $result[3][$k]);
                    }
                } else {
                    $this->getCounty($province, $id, $result[3][$k]);
                }
            }
        }
    }

    public function getCounty($province, $city, $source_link)
    {
        $url = $this->url . $source_link;

        $temp = explode('/', $source_link);
        $link = $temp[0];
        
        $html = $this->html($url);

        preg_match_all('/<tr class="countytr"><td><a href="(.*?)">(.*?)<\/a><\/td><td><a href="(.*?)">(.*?)<\/a><\/td><\/tr>/i', $html, $result);

        $update = [];
        if (isset($result[3]) && isset($result[4]) && $result[4]) {
            foreach ($result[4] as $k => $v) {
                $update['id'] = $this->id($result[2][$k]);
                $update['name'] = strip_tags($v);
                $update['city_id'] = $city;
                $update['province_id'] = $province;
                $update['area'] = $province . ',' . $city;
                $this->setLevelCounty($update);
                $update = Dever::load(Data::class)->pinyin($update);
                $id = $this->up('county', $update['id'], $update);

                # 获取街道
                $this->getTown($province, $city, $id, $link . '/' . $result[3][$k]);
            }
        } else {
            $city_info = Dever::db('area/city')->find($city);
            $update['id'] = $city_info['id'];
            $update['name'] = $city_info['name'] . '辖区';
            $update['city_id'] = $city;
            $update['province_id'] = $province;
            $update['area'] = $province . ',' . $city;
            $update['type'] = 1;
            $update['level'] = 1;
            $update['pinyin'] = $city_info['pinyin'];
            $update['pinyin_first'] = $city_info['pinyin_first'];

            $id = $this->up('county', $update['id'], $update);

            # 获取街道
            $this->getTown($province, $city, $id, $source_link, $html);
        }
    }

    public function getTown($province, $city, $county, $link = false, $html = false)
    {
        if ($link) {
            $url = $this->url . $link;

            $temp = explode('/', $link);
            $link = $temp[0] . '/' . $temp[1];
            
            $html = $this->html($url);
        }
        if (!$link && !$html) {
            return;
        }

        preg_match_all('/<tr class="towntr"><td><a href="(.*?)">(.*?)<\/a><\/td><td><a href="(.*?)">(.*?)<\/a><\/td><\/tr>/i', $html, $result);

        $update = [];
        if (isset($result[3]) && isset($result[4]) && $result[4]) {
            foreach ($result[4] as $k => $v) {
                $update['id'] = $this->id($result[2][$k], 9);
                $update['name'] = strip_tags($v);
                $update['county_id'] = $county;
                $update['city_id'] = $city;
                $update['province_id'] = $province;
                $update['area'] = $province . ',' . $city . ',' . $county;
                $update = Dever::load(Data::class)->pinyin($update);
                $id = $this->up('town', $update['id'], $update);

                # 获取社区
                //$this->getVillage($province, $city, $county, $id, $link . '/' . $result[3][$k]);
            }
        }
    }

    public function getVillage($province, $city, $county, $town, $link)
    {
        $url = $this->url . $link;
        
        $html = $this->html($url);

        preg_match_all('/<tr class="villagetr"><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><\/tr>/i', $html, $result);

        $update = [];
        if (isset($result[1]) && isset($result[2]) && isset($result[3])) {
            foreach ($result[3] as $k => $v) {
                $update['id'] = $this->id($result[1][$k], 12);
                $update['code'] = $result[2][$k];
                $update['name'] = strip_tags($v);
                $update['town_id'] = $town;
                $update['county_id'] = $county;
                $update['city_id'] = $city;
                $update['province_id'] = $province;
                $update['area'] = $province . ',' . $city . ',' . $county . ',' . $town;
                $update = Dever::load(Data::class)->pinyin($update);
                $this->up('village', $update['id'], $update);
            }
        }
    }

    private function html($url)
    {
        $html = Dever::curl($url)->result();

        //$html = Dever::convert($html, "UTF-8", "GBK");
        $html = preg_replace('//', '', $html); // 去掉HTML注释
        $html = preg_replace('/\s+/', ' ', $html); // 清除多余的空格
        $html = preg_replace('/>\s</', '><', $html); // 去掉标记之间的空格
        return $html;
    }
}
