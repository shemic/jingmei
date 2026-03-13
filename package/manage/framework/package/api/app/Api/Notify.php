<?php namespace Api\Api;
use Dever;
use Api\Lib\Api;
use Api\Lib\Platform\Sign;
use Api\Lib\Platform\Value;
class Notify extends Api
{
    private $notify;
    public function common()
    {
        $input = Dever::input();
        $file = file_get_contents("php://input");
        if ($file) {
            $file = Dever::json_decode($file);
            if ($file) {
                $input = array_merge($file, $input);
            }
        }
        Dever::log($input, 'api_notify');
        if (!isset($input['s'])) {
            $this->error('error');
        }
        $s = \Dever\Helper\Str::decode($input['s']);
        $param = [];
        if ($s) {
            $data = explode('|', $s);
            $api_id = $data[0];
            $account_project = $data[1];
            $account_id = $data[2];
            if (!$api_id) {
                $this->error('error');
            }
            if (!$account_project) {
                $this->error('error');
            }
            if (!$account_id) {
                $this->error('error');
            }
            if (isset($data[3])) {
                $method = $data[3];
                unset($data[0]);
                unset($data[1]);
                unset($data[2]);
                unset($data[3]);
                $param = array_values($data);
                Dever::call($method . '_start', $param);
            }
        } else {
            $this->error('error');
        }
        unset($input['s']);
        unset($input['l']);
        $setting['account_project'] = $account_project;
        $setting['account_id'] = $account_id;

        $state = $this->setting($api_id, $setting);
        if (!$state) {
            $this->error('error');
        }
        if (!$input) {
            $this->error('error');
        }
        if ($this->info['notify'] == 2) {
            $this->error('error');
        }
        $this->notify = Dever::db('api/api_notify')->find(['api_id' => $api_id]);
        if (!$this->notify) {
            $this->error('error');
        }
        $body = $this->body($input);
        $this->header();
        $this->verify();
        # 判断是否成功
        $status = $this->status($body);
        if ($status < 3 && isset($method)) {
            $param[] = $status;
            $param[] = $body;
            $msg = Dever::call($method . '_end', $param);
            if ($msg) {
                $this->error($msg);
            }
        }

        # 返回给上游信息
        if ($status == 1) {
            echo $this->notify['success'];die;
        } elseif ($status == 2) {
            $this->error('error');
        }
    }

    protected function body($body)
    {
        $config = Dever::db('api/api_notify_body')->select(['api_id' => $this->info['id']]);
        $result = Dever::load(Value::class)->init($this->field)->get($config, $body);
        return $result;
    }

    protected function header()
    {
        $header = getallheaders();
        $config = Dever::db('api/platform_response_header')->select(['platform_id' => $this->platform['id']]);
        if ($config) {
            foreach ($config as $k => $v) {
                if (isset($header[$v['value']])) {
                    $value = $this->field->value($header[$v['value']], $v['type'], false);
                    $this->field->set($v['key'], $value);
                }
            }
        }
        $config = Dever::db('api/api_response_header')->select(['api_id' => $this->info['id']]);
        if ($config) {
            foreach ($config as $k => $v) {
                if (isset($header[$v['value']])) {
                    $value = $this->field->value($header[$v['value']], $v['type'], false);
                    $this->field->set($v['key'], $value);
                }
            }
        }
    }

    protected function verify()
    {
        if (!$this->notify['sign_id']) {
            Dever::error('签名验证失败');
        }
        $info = Dever::db('api/platform_sign')->find($this->notify['sign_id']);
        Dever::get(Sign::class, true, $info['id'])->init($this->field, $info)->get();
    }

    protected function status($body)
    {
        # 1成功 2失败 3不做任何操作
        $status = 3;
        $config = Dever::db('api/api_notify_code')->select(['api_id' => $this->info['id']]);
        if ($config) {
            foreach ($config as $k => $v) {
                if (isset($body[$v['key']]) && $body[$v['key']] == $v['value']) {
                    $status = $v['type'];
                }
            }
        }
        return $status;
    }

    protected function error($msg)
    {
        if ($this->notify && $this->notify['error']) {
            $temp = explode("\n", $this->notify['error']);
            if (!isset($temp[1])) {
                $temp[1] = 500;
            }
            $this->code($temp[1]);
            echo $this->notify['error'];die;
        }
        echo $msg;die;
    }

    protected function code($code)
    {
        if ($code == 500) {
            header("HTTP/1.1 500 Internal Server Error");
            header("Status: 500 Internal Server Error");
        }
    }
}