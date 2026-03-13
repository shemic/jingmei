<?php namespace Api\Manage\Api;
use Dever;
use Manage\Lib\Auth;
class Manage extends Auth
{
    # 将sku中的key解析成前端可以读取的格式
    public function keyToField($project, $key)
    {
        if ($key != -1) {
            $result = [];
            $array = explode(',', $key);
            $result['key']['set'] = [];
            foreach ($array as $k => $v) {
                $value = Dever::db($project . '/spec_value')->find($v);
                if ($value) {
                    $spec = Dever::db($project . '/spec')->find($value['spec_id']);
                    $result['key']['set']['s_' . $spec['id']] = [$spec['name'], $value['value']];
                }
            }
            return $result;
        }
    }

    # 获取参数设置表的参数名
    public function getSettingName($value = false)
    {
        if ($value) {
            $info = Dever::db('api/platform_setting')->find($value);
            $result['key']['value'] = $info['key'];
            return $result;
        }
    }

    # 获取参数设置表的参数名
    public function getCertName($value = false)
    {
        if ($value) {
            $info = Dever::db('api/platform_cert')->find($value);
            $result['type']['value'] = $info['type'];
            return $result;
        }
    }

    # 根据平台获取接口
    public function getApi($value = false)
    {
        if ($value) {
            $where['platform_id'] = $value;
            $result['api_id']['value'] = '';
            $result['api_id']['option'] = Dever::db('api/api')->select($where);
            return $result;
        }
    }

    # 根据应用获取平台
    public function getAppPlatform()
    {
        return Dever::load(\Manage\Lib\Util::class)->cascader(2, function($level, $parent) {
            if ($level == 1) {
                $data = Dever::db('api/app')->select([]);
            } elseif ($level == 2) {
                $data = Dever::load(\Api\Lib\App::class)->getPlatform($parent);
            }
            return $data;
        });
    }

    # 复制一个接口
    public function copyApi()
    {
        $id = Dever::input('id');
        if ($id) {
            $info = Dever::db('api/api')->find($id);
            unset($info['id']);
            unset($info['cdate']);
            Dever::db('api/api')->insert($info);
        }
        return '复制成功';
    }
}