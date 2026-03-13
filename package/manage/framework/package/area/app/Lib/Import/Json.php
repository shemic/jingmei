<?php namespace Area\Lib\Import;
set_time_limit(0);
use Dever;
use Area\Lib\Data;
class Json extends Core
{
    private $url = 'https://github.com/modood/Administrative-divisions-of-China';

    public function getUrl()
    {
        return $this->url;
    }
    
    public function get()
    {
        $this->getProvince();
        $this->getCity();
        $this->getCounty();
        $this->getTown();
        return 'ok';
    }

    private function load($type)
    {
        $file = DEVER_APP_PATH . 'file/'.$type.'.json';
        $content = file_get_contents($file);
        $content = json_decode($content, true);
        return $content;
    }

    public function getProvince()
    {
        $data = $this->load('provinces');
        if ($data) {
            foreach ($data as $k => $v) {
                $update['id'] = $this->id($v['code']);
                $update['name'] = $v['name'];
                $update = Dever::load(Data::class)->pinyin($update);
                $this->up('province', $update['id'], $update);
            }
        }
    }

    public function getCity()
    {
        $data = $this->load('cities');
        if ($data) {
            foreach ($data as $k => $v) {
                $update['id'] = $this->id($v['code']);
                $update['name'] = $v['name'];
                $update['province_id'] = $this->id($v['provinceCode']);
                $update = Dever::load(Data::class)->pinyin($update);
                $this->up('city', $update['id'], $update);
            }
        }
    }

    public function getCounty()
    {
        $data = $this->load('areas');
        if ($data) {
            foreach ($data as $k => $v) {
                $update['id'] = $this->id($v['code']);
                $update['name'] = $v['name'];
                $update['city_id'] = $this->id($v['cityCode']);
                $update['province_id'] = $this->id($v['provinceCode']);
                $update['area'] = $update['province_id'] . ',' . $update['city_id'];
                $this->setLevelCounty($update);
                $update = Dever::load(Data::class)->pinyin($update);
                $this->up('county', $update['id'], $update);
            }
        }
    }

    public function getTown()
    {
        $data = $this->load('streets');
        if ($data) {
            foreach ($data as $k => $v) {
                $update['id'] = $this->id($v['code'], 9);
                $update['name'] = $v['name'];
                $update['county_id'] = $this->id($v['areaCode']);
                $update['city_id'] = $this->id($v['cityCode']);
                $update['province_id'] = $this->id($v['provinceCode']);
                $update['area'] = $update['province_id'] . ',' . $update['city_id'] . ',' . $update['county_id'];
                $this->setLevelCounty($update);
                $update = Dever::load(Data::class)->pinyin($update);
                $this->up('town', $update['id'], $update);
            }
        }
    }
}
