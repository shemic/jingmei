<?php namespace Area\Manage\Lib;
use Dever;
class Info
{
    public function update($db, $data)
    {
        if (isset($data['area']) && $data['area']) {
            list($data['province_id'], $data['city_id'], $data['county_id'], $data['town_id']) = explode(',', $data['area']); 
        }
        return $data;
    }
}