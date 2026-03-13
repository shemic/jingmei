<?php namespace Upload\Lib;
use Dever;
class Manage
{
    # 文件表按照月份分区
    public function getFileField()
    {
        return [
            'type' => 'range',
            'field' => 'cdate', 
            'value' => 'date("Y-m", strtotime("+1 month"))'
        ];
        return $type;
    }
}