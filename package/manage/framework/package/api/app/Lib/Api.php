<?php namespace Api\Lib;
use Dever;
class Api extends Platform
{
    public $type = 'api';

    # 执行某个接口
    public function run($id, $param = [])
    {
        $state = $this->setting($id, $param);
        if (!$state) {
            return $state;
        }
        return $this->curl();
    }

    # 跳转
    public function jump($id, $param = [])
    {
        $state = $this->setting($id, $param);
        if (!$state) {
            return $state;
        }
        return $this->location();
    }

    # 生成回调
    protected function createNotify($field)
    {
        $encode = $this->info['id'] . '|' . $field['account_project'] . '|' . $field['account_id'];
        if (isset($field['notify'])) {
            $encode .= '|' . $field['notify'];
        }
        $encode = \Dever\Helper\Str::encode($encode);
        return Dever::url('api/notify.common', ['s' => $encode], false, 'package/api/');
    }
}