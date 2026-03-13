<?php namespace Api\Lib;
use Dever;
# 官方平台账户
class Account
{
    public function update($db, $data)
    {
        if (isset($data['app_platform']) && $data['app_platform']) {
            $app_platform = $data['app_platform'];
            if (is_string($app_platform)) {
                $app_platform = explode(',', $app_platform);
            }
            $data['app_id'] = $app_platform[0];
            $data['platform_id'] = $app_platform[1] ?? 0;
            if (!$data['platform_id']) {
                Dever::error('请选择平台');
            }
        }
        return $data;
    }

    # 同步某个项目下的账户信息
    public function sync($project)
    {
        # 跨库了
        //return Dever::db($project . '/account')->copy('api_account', ['a.sync' => 1], ['key', 'name']);
        $col = '`key`,name,cdate';
        $list = Dever::db('api/account')->select(['sync' => 1], ['col' => $col]);
        Dever::db($project . '/account')->inserts(['field' => $col, 'value' => $list]);
    }

    # 根据应用功能获取账户列表
    public function getList($key)
    {
        $func = Dever::db('api/app_func')->find(['key' => $key, 'status' => 1]);
        if ($func) {
            $work = Dever::db('api/app_func_work')->columns(['app_func_id' => $func['id'], 'status' => 1], 'platform_id');
            $account = Dever::db('api/account')->select(['platform_id' => ['in', $work]]);
            return $account;
        }
        return [];
    }

    # 获取某个项目的账户信息
    public function get($account, $project = 'api', $setting = false)
    {
        if (is_array($account)) {
            return $account;
        }
        if (is_numeric($account)) {
            $where = ['id' => $account];
        } else {
            $where = ['key' => $account];
        }
        $account = Dever::db('api/account')->find($where);
        if ($account && $project != 'api') {
            $info = Dever::db($project . '/account')->find(['key' => $account['key']]);
            if ($info) {
                $info['app_id'] = $account['app_id'];
                $info['platform_id'] = $account['platform_id'];
                $account = $info;
            } else {
                Dever::error('账户无效');
            }
        }
        if ($account && $setting) {
            $account['setting'] = [];
            $setting = Dever::db($project . '/account_setting')->select(['account_id' => $account['id']]);
            if ($setting) {
                foreach ($setting as $k => $v) {
                    $info = Dever::db('api/platform_setting')->find($v['platform_setting_id']);
                    if ($info) {
                        $v['key'] = $info['key'];
                        $account['setting'][$v['key']] = $v['value'];
                    } else {
                        Dever::error('account error');
                    }
                }
            }
        }
        return $account;
    }

    public function run($account, $func, $param = [], $env = 1, $method = 'run', $project = 'api')
    {
        $account = $this->get($account, $project);
        if (!$account) {
            Dever::error('账户无效');
        }
        if (!is_array($func)) {
            $func = Dever::db('api/app_func')->find(['app_id' => $account['app_id'], 'key' => $func]);
        }
        if (!$func) {
            Dever::error('功能无效');
        }
        $api = Dever::load(App::class)->getApi($func['id'], $env);
        if (!$api) {
            Dever::error('接口无效');
        }
        $param['account_project'] = $project;
        $param['account_id'] = $account['id'];
        $result = [];
        if ($func['type'] == 1) {
            # 仅执行第一个
            $result = Dever::load(Api::class)->$method($api[0], $param);
            if (is_array($result)) {
                $result['account_id'] = $account['id'];
                $result['api_id'] = $api[0]['id'];
            }
        } elseif ($func['type'] == 2) {
            # 同步执行
            foreach ($api as $k => $v) {
                $result = Dever::load(Api::class)->$method($v, $param);
                if ($result && is_array($result)) {
                    $param = array_merge($result, $param);
                }
            }
        } elseif ($func['type'] == 3) {
            # 异步执行
            $result = true;
            foreach ($api as $k => $v) {
                $param['api_id'] = $v['id'];
                \Dever\Helper\Cmd::run('task.api', $param, 'api');
            }
        }
        return $result;
    }
}