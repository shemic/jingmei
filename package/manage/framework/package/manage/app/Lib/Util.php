<?php namespace Manage\Lib;
use Dever;
use Dever\Helper\Str;
use Dever\Helper\Env;
use Dever\Helper\Secure;
use Dever\Helper\Date;
class Util
{
    # 获取后台传入的数据
    public function request($col, $other = '')
    {
        $info = Dever::input(['set', 'field'])[$col] ?? 0;
        if ($other && !$info) {
            $info = Dever::input($other);
        }
        return $info;
    }

    # 快速生成tip
    public function createTip($call)
    {
        $name = '-';
        $content = [];
        $call($name, $content);
        $result['name'] = $name;
        if ($content) {
            $result['content'] = ['type' => 'line', 'content' => $content];
        }
        return $result;
    }

    # 加入cron
    public function cron($name, $project, $api, $time = 3600)
    {
        $data = ['project' => $project, 'interface' => $api];
        $info = Dever::db('manage/cron')->find($data);
        if (!$info) {
            $data['name'] = $name;
            $data['ldate'] = time();
            $data['time'] = 3600;
            Dever::db('manage/cron')->insert($data);
        }
    }

    # 快速使用tip里的content
    public function getTip($data, $key)
    {
        return $data['content']['content'][$key]['content'] ?? '';
    }

    public function info()
    {
        $auth = $this->auth();
        $system = Dever::db('manage/system')->find($auth['extend']['system_id']);
        return Dever::db($system['user_table'])->find($auth['uid']);
    }

    public function auth()
    {
        $auth = Dever::input('authorization');
        if ($auth) {
            $auth = Str::decode($auth);
        }
        if (!$auth) {
            $auth = Env::header('authorization');
        }
        if ($auth) {
            $auth = str_replace('Bearer ', '', $auth);
            Dever::session('auth', $auth);
            $info = Secure::checkLogin($auth);
            return $info;
        }
        return false;
    }

    # 获取当前的扩展数据
    public function extend()
    {
        # 先从query的set中获取，这个不影响用户登录
        $auth = $this->request('authorization');
        if ($auth) {
            $auth = Str::decode($auth);
            $info = Secure::checkLogin($auth);
            if ($info) {
                return $info['extend'];
            }
        }
        # 从登录里获取
        $info = $this->auth();
        if (!$info) {
            $auth = Dever::session('auth');
            if (!$auth) {
                return false;
            }
            $info = Secure::checkLogin($auth);
        }
        if ($info && isset($info['extend'])) {
            return $info['extend'];
        }
        return false;
    }

    # 获取页面类
    public function page($load, $config = [], $key = 'list', $input = true)
    {
        $page = new Page($key, $load, $input, $config);
        return $page;
    }

    # 获取当前使用的系统 一般为数据库隔离使用
    public function system($info = false, $module = true, $field = false)
    {
        if (!$info) {
            # 单独的数据库隔离，不影响当前登录状态
            $info = $this->extend();
        }
        if ($info && isset($info['info_id']) && isset($info['partition'])) {
            # 这里后续增加从数据库中获取
            $value = $info['system_id'] . '_' . $info['info_id'];
            $result = [];
            if (strpos($info['partition'], '.')) {
                $temp = explode('.', $info['partition']);
                $result = $this->partition($result, $temp[0], $info['system_key'], $value);
                if ($module && isset($info['data_id']) && $info['data_id']) {
                    if ($temp[0] == $temp[1]) {
                        $value .= '/' . $info['module_id'] . '_' . $info['data_id'];
                        $result = $this->partition($result, $temp[0], $info['system_key'], $value);
                    } else {
                        $result = $this->partition($result, $temp[1], $info['system_key'], $info['module_id'] . '_' . $info['data_id']);
                    }
                }
            } else {
                $result = $this->partition($result, $info['partition'], $info['system_key'], $value);
            }
            if ($field) {
                $result['field'] = Dever::call($field);
            }
            return $result;
        }
        return false;
    }

    # 设置数据隔离
    private function partition(&$result, $type, $key, $value)
    {
        if ($type == 'field') {
            $result[$type] = [
                'type' => 'key',
                'field' => $key,
                'value' => $value,
            ];
        } elseif ($type == 'where') {
            $result[$type] = [
                $key => $value
            ];
        } else {
            $result[$type] = $value;
        }
        return $result;
    }

    # 获取token需要用到的key
    public function getToken()
    {
        $extend = $this->extend();
        if ($extend) {
            return implode('-', array_values($extend));
        }
        return '';
    }

    # 将token设置到route权限中，方便后续读取
    # 系统、模块、模块账户、数据id
    public function setAuth($system, $module_id, $info_id, $data_id = '')
    {
        if (is_string($system)) {
            $system = Dever::db('manage/system')->find(['key' => $system]);
        }
        if (is_string($module_id)) {
            $module_id = Dever::db('manage/system_module')->column(['key' => $module_id], 'id');
        }
        $token = $this->token(-1, '', $system['partition'], $system['key'], $system['id'], $info_id, $module_id, $data_id);
        return Dever::get(\Dever\Route::class)->data['authorization'] = Secure::encode($token['token']);
    }

    # 生成token
    public function token($uid, $mobile, $partition, $system_key, $system_id, $info_id, $module_id, $data_id)
    {
        $extend['partition'] = $partition;
        $extend['system_key'] = $system_key;
        $extend['system_id'] = $system_id;
        $extend['info_id'] = $info_id;
        $extend['module_id'] = $module_id;
        $extend['data_id'] = $data_id;
        if ($uid && $uid > 0) {
            $select['uid'] = $uid;
            $select['system_id'] = $system_id;
            $select['info_id'] = $info_id;
            $info = Dever::db('manage/system_user')->find($select);
            $select += $extend;
            if (!$info) {
                Dever::db('manage/system_user')->insert($select);
            } else {
                Dever::db('manage/system_user')->update($info['id'], $select);
            }
        }
        return array('token' => Secure::login($uid, $extend));
    }

    # 生成密码
    public function createPwd($password)
    {
        $data['salt'] = Str::salt(8);
        $data['password'] = $this->hash($password, $data['salt']);
        return $data;
    }

    # 生成用户密码（与 Go 后端保持一致）
    public function createUserPwd($password)
    {
        $data['salt'] = Str::salt(8);
        $data['password'] = $this->hashUser($password, $data['salt']);
        return $data;
    }

    # 生成时间
    public function crateDate($date)
    {
        return Date::mktime($date);
    }

    # hash加密
    public function hash($password, $salt)
    {
        return hash('sha256', $password . $salt);
    }

    # 用户密码 hash 加密（与 Go 后端保持一致）
    public function hashUser($password, $salt)
    {
        return hash('sha256', $salt . $password);
    }

    # 自动更新key
    public function updateKey($db, $data)
    {
        if ($data['name'] && !$data['key']) {
            if (Dever::project('pinyin')) {
                $where = [];
                if (isset($data['id']) && $data['id']) {
                    $where['id'] = ['!=', $data['id']];
                }
                $data['key'] = Dever::load(\Pinyin\Lib\Convert::class)->getPinyin($data['name']);

                # 检查是否存在
                $where['key'] = $data['key'];
                $info = $db->find($where);
                if ($info) {
                    $data['key'] .= '-' . date('YmdHis');
                }
            }
        }
        return $data;
    }

    # 设置联动
    public function cascader($total, $func)
    {
        $total = Dever::input('total', 'is_numeric', '联动总数', $total);
        $level = Dever::input('level', 'is_numeric', '联动级别', 1);
        $parent = Dever::input('parent', 'isset', '联动ID', 0);
        if ($parent < 0) {
            Dever::error('error');
        }
        $data = $func($level, $parent);
        if ($level >= $total) {
            foreach ($data as &$v) {
                $v['leaf'] = true;
            }
        }
        $result['total'] = $total;
        $result['list'] = $data;
        return $result;
    }

    # 根据load获取db
    public function db($load)
    {
        $menu = [];
        $load = explode('/', ltrim($load, '/'));
        if (isset($load[2])) {
            $app = $load[1];
            $table = $load[2];
        } else {
            $app = $load[0];
            $table = $load[1];
        }
        $parent = Dever::db('manage/menu')->find(['key' => $app]);
        if ($parent) {
            $menu = Dever::db('manage/menu')->find(['parent_id' => $parent['id'], 'key' => $table]);
            if ($menu) {
                $app = $menu['app'];
            }
        }
        $set = Dever::project($app);
        $file = $set['path'] . 'manage/'.$table.'.php';
        $manage = [];
        if (is_file($file)) {
            $manage = include $file;
            if ($source = Dever::issets($manage, 'source')) {
                if (strpos($source, '/')) {
                    $source = explode('/', $source);
                    $app = $source[0];
                    $table = $source[1];
                } else {
                    $table = $source;
                }
            }
        }
        $db = Dever::db($app . '/' . $table);
        $db->config['manage'] = $manage;
        return [$db, $menu];
    }

    # 获取项目
    public function project()
    {
        $result = [];
        $app = Dever::get(\Dever\Project::class)->read();
        foreach ($app as $k => $v) {
            $result[] = [
                'id' => $k,
                'name' => $v['lang'] ?? $k,
            ];
        }
        return $result;
    }
}
