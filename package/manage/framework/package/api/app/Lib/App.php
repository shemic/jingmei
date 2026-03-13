<?php namespace Api\Lib;
use Dever;
class App
{
    public function update($db, $data)
    {
        $func = Dever::db('api/app_func_work')->select(['app_id' => $data['app_id']]);
        if ($func) {
            $platform = [];
            Dever::db('api/app_platform')->delete(['app_id' => $data['app_id']]);
            foreach ($func as $k => $v) {
                $api = Dever::db('api/api')->find($v['api_id']);
                if ($api) {
                    $platform[$api['platform_id']] = $api['platform_id'];
                }
            }
            foreach ($platform as $k => $v) {
                Dever::db('api/app_platform')->insert(['app_id' => $data['app_id'], 'platform_id' => $v]);
            }
        }
    }

    public function updateApi($db, $data)
    {
        if (isset($data['notify']) && $data['notify'] == 2) {
            Dever::db('api/api_notify')->delete(['api_id' => $data['id']]);
        }
    }

    public function getPlatform($app)
    {
        $set['join'] = [
            [
                'table' => 'api_platform',
                'type' => 'left join',
                'on' => 't0.id=main.platform_id',
            ],
        ];
        $set['col'] = 't0.id as id,t0.name as name';
        $platform = Dever::db('api/app_platform')->select(['app_id' => $app], $set);
        return $platform;
    }

    public function getSetting($platform_id)
    {
        return Dever::db('api/platform_setting')->select(['platform_id' => $platform_id]);
    }

    public function getCert($account_id, $project = 'api')
    {
        if ($project) {
            $account = Dever::db($project . '/account')->find($account_id);
            if ($account) {
                $account = Dever::db('api/account')->find(['key' => $account['key']]);
            }
        } else {
            $account = Dever::db('api/account')->find($account_id);
        }
        return Dever::db('api/platform_cert')->select(['platform_id' => $account['platform_id']]);
    }

    public function getCertName($id)
    {
        $info = Dever::db('api/platform_cert')->find($id);
        return $info['name'];
    }

    public function getApi($func_id, $env = 1)
    {
        $set['join'] = [
            # t0
            [
                'table' => 'api_api',
                'type' => 'left join',
                'on' => 't0.id=main.api_id',
            ],
        ];
        $set['col'] = 't0.*';
        $set['order'] = 'main.sort asc,t0.id desc';
        $where['main.app_func_id'] = $func_id;
        if ($env) {
            $where['t0.env'] = $env;
        }
        return Dever::db('api/app_func_work')->select($where, $set);
    }

    public function getAppPlatform($app_id, $platform_id)
    {
        $app = Dever::db('api/app')->find($app_id);
        $platform = Dever::db('api/platform')->find($platform_id);
        return $app['name'] . '-' . $platform['name'];
    }
}