<?php namespace Api\Lib;
use Dever;
class Util
{
    # 生成订单号
    public function createNumber($prefix, $table, $where = [], $key = 'order_num')
    {
        $number = \Dever\Helper\Str::order($prefix);
        $where[$key] = $number;
        $state = Dever::db($table)->find($where);
        if (!$state) {
            return $number;
        }
        return $this->createNumber($prefix, $table, $where, $key);
    }

    # 获取某个setting值
    public function setting($key, $account, $project = 'api')
    {
        $setting = Dever::db('api/platform_setting')->find(['key' => $key]);
        if ($setting) {
            $setting = Dever::db($project . '/account_setting')->find(['account_id' => $account, 'platform_setting_id' => $setting['id']]);
        }
        $value = $setting['value'] ?? 0;
        return $value;
    }

    # 获取openid 仅jspai和小程序需要openid
    public function openid($account, $env, $uid, $result = [], $project = 'api')
    {
        $account = Dever::load(Account::class)->get($account, $project);
        if ($account) {
            $info = Dever::db('api/openid')->find(['account_id' => $account['id'], 'uid' => $uid, 'env' => $env]);
            if ($info) {
                $result['openid'] = $info['openid'];
            } else {
                # 这里需要修改
                if ($env == 3 || $env == 2) {
                    # jsapi 一般需要oauth授权
                    if (empty($result['t'])) {
                        $result['t'] = \Dever\Helper\Secure::login($uid);
                    }
                    $result['account'] = $account['key'];
                    $result['link'] = Dever::url('api/oauth.code', $result);
                } elseif ($env == 5) {
                    # 小程序
                    $param['js_code'] = Dever::input('applet_code', 'is_string', '登录信息');
                    $data = Dever::load(Account::class)->run($account, 'applet_login', $param, $env, 'run', $project);
                    if (isset($data['openid'])) {
                        $result['openid'] = $data['openid'];
                        $update['uid'] = $uid;
                        $update['account_id'] = $account['id'];
                        $update['env'] = $env;
                        $update['openid'] = $data['openid'];
                        Dever::db('api/openid')->insert($update);
                    }
                }
            }
        }
        return $result;
    }

    # 获取token
    public function token($account, $env, $project = 'api')
    {
        $account = Dever::load(Account::class)->get($account, $project);
        $result = '';
        if ($env == 5) {
            $appid = 'applet_appid';
            $secret = 'applet_secret';
        }
        $appid = $this->setting($appid, $account['id'], $project);
        $secret = $this->setting($secret, $account['id'], $project);
        if ($account && $appid && $secret) {
            $param = ['appid' => $appid, 'secret' => $secret];
            $info = Dever::db('api/token')->find($param);
            if ($info && time() <= $info['edate']) {
                $result = $info['token'];
            } else {
                $data = Dever::load(Account::class)->run($account, 'access_token', $param, 1, 'run', $project);
                if (isset($data['access_token'])) {
                    $result = $data['access_token'];
                    $param['token'] = $result;
                    $param['edate'] = time() + $data['expires_in'];
                    if ($info) {
                        Dever::db('api/token')->update($info['id'], $param);
                    } else {
                        Dever::db('api/token')->insert($param);
                    }
                }
            }
        }
        return $result;
    }

    # 获取参数类型
    public function fieldType($platform_id)
    {
        $data = [];
        $data[] = array
        (
            'id' => 1,
            'name' => '格式转换',
            'children' => Dever::db('api/format')->select([]),
        );

        $where = ['platform_id' => $platform_id];

        $data[] = array
        (
            'id' => 2,
            'name' => '加密',
            'children' => Dever::db('api/platform_ssl')->select($where),
        );

        $data[] = array
        (
            'id' => 3,
            'name' => '签名',
            'children' => Dever::db('api/platform_sign')->select($where),
        );
        return $data;
    }

    # 获取签名列表
    public function getPlatformSign($platform_id)
    {
        return Dever::db('api/platform_sign')->select(['platform_id' => $platform_id]);
    }

    # 格式转换
    public function format($id, $value)
    {
        $info = Dever::db('api/format')->find($id);
        if ($info) {
            $info['method'] = str_replace('{value}', "'{value}'", $info['method']);
            $value = \Dever\Helper\Str::val($info['method'], ['value' => $value]);
        }
        return $value;
    }
}