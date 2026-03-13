<?php namespace Api\Lib;
use Dever;
use Api\Lib\Platform\Field;
use Api\Lib\Platform\Ssl;
use Api\Lib\Platform\Request;
use Api\Lib\Platform\Response;
class Platform
{
    public $platform;
    public $info;
    public $field;

    public function setting($id, $field = [])
    {
        $this->info($id);
        $this->platform();
        $this->field($field);
        return $this;
    }

    protected function info($id)
    {
        $this->info = is_array($id) ? $id : Dever::db('api/' . $this->type)->find($id);
        if (!$this->info) {
            Dever::error('info error');
        }
    }

    protected function platform()
    {
        $this->platform = Dever::db('api/platform')->find($this->info['platform_id']);
        if (!$this->platform) {
            Dever::error('platform error');
        }
        if ($this->info['method'] == -1) {
            $this->info['method'] = $this->platform['method'];
            $this->info['post_method'] = $this->platform['post_method'];
        }
        if ($this->info['method'] == 1) {
            $this->info['post_method'] = 1;
        }
        if ($this->info['response_type'] == -1) {
            $this->info['response_type'] = $this->platform['response_type'];
        }
    }

    protected function field($field)
    {
        if (isset($field['account_project']) && isset($field['account_id'])) {
            $this->info['account_id'] = $field['account_id'];
            $this->info['account_project'] = $field['account_project'];
            $setting = Dever::db($field['account_project'] . '/account_setting')->select(['account_id' => $field['account_id']]);
            if ($setting) {
                foreach ($setting as $k => $v) {
                    $info = Dever::db('api/platform_setting')->find($v['platform_setting_id']);
                    if ($info) {
                        $v['key'] = $info['key'];
                        if (isset($field[$v['key']])) {
                            $v['value'] = $field[$v['key']];
                        }
                        $field[$v['key']] = $v['value'];
                    } else {
                        Dever::error('account error');
                    }
                }
            } else {
                Dever::error('account error');
            }
        } else {
            Dever::error('account error');
        }
        $this->field = Dever::load(Field::class)->init($field);
        $this->field->setPlatformId($this->platform['id']);
        $this->field->setApid($this->info['id']);
        $this->field->setHost($this->platform['host']);
        $this->field->setUri($this->info['uri']);
        $this->field->setNotify($this->createNotify($field));

        $setting = Dever::db('api/api_setting')->select(['api_id' => $this->info['id']]);
        if ($setting) {
            foreach ($setting as $k => $v) {
                $this->field->set($v['key'], $this->field->value($v['value'], $v['type']));
            }
        }
    }

    # 发起请求
    protected function curl($url = false)
    {
        # 生成请求ID
        $this->field->createRequestId();
        if (!$url) {
            $url = $this->url();
        }
        $method = $this->method();
        $request = Dever::load(Request::class)->init($this->field, $this->platform['id'], $this->type, $this->info['id']);
        $body = $request->body();
        $header = $request->header();
        $json = $this->info['post_method'] == 3 ? true : false;
        $curl = Dever::curl($url, $body, $method, $json, $header);
        $curl->setResultHeader(true);
        $response_body = $curl->result();
        $response_header = $curl->header();
        $response_config = ['id' => $this->platform['id'], 'type' => $this->info['response_type']];
        $response = Dever::load(Response::class)->init($response_body, $response_header, $this->field, $response_config, $this->type, $this->info['id']);
        $result = $response->out();
        $log = [];
        $log['platform_id'] = $this->platform['id'];
        $log['api_id'] = $this->info['id'];
        $log['account_id'] = $this->info['account_id'];
        $log['account_project'] = $this->info['account_project'];
        $log['request_id'] = $this->field->request_id;
        $log['url'] = $url;
        $log['method'] = $method;
        $log['body'] = $body;
        $log['header'] = $header;
        $log['response_header'] = $response_header;
        $log['response_body'] = $response_body;
        $log['data'] = $result;
        if ($response_config['type'] == 4) {
            $log['response_body'] = 'buffer';
            $log['data'] = 'buffer';
        }
        Dever::db('api/api_log')->update($log['request_id'], $log);
        if ($this->field->log) {
            Dever::log($log, 'api');
            Dever::debug($log, 'api');
        }
        return $result;
    }

    # 直接跳转
    protected function location($url = false)
    {
        if (!$url) {
            $url = $this->url();
        }
        $method = $this->method();
        $request = Dever::load(Request::class)->init($this->field, $this->platform['id'], $this->type, $this->info['id']);
        $request->body();
        $url .= '?';
        foreach ($this->field->body as $k => $v) {
            if ($k == '#') {
                $url .= $k . $v;
            } else {
                $url .= $k . '=' . $v . '&';
            }
        }
        header('Location: '. $url);
    }

    protected function method()
    {
        $method = 'get';
        if ($this->info['post_method'] == 2) {
            $method = 'file';
        } elseif ($this->info['method'] == 2) {
            $method = 'post';
        }
        $this->field->setMethod($method);
        return $method;
    }

    protected function url()
    {
        if (strstr($this->info['uri'], 'http')) {
            $this->platform['host'] = '';
        }
        $path = Dever::db('api/api_path')->select(['api_id' => $this->info['id']]);
        if ($path) {
            $path = [];
            foreach ($path as $k => $v) {
                $v['value'] = $this->field->value($v['value']);
                if ($v['type'] == 1) {
                    $path[] = $v['value'];
                } elseif ($v['type'] == 2) {
                    $path[] = $v['key'] . '/' . $v['value'];
                } elseif ($v['type'] == 3) {
                    $path[] = $v['key'] . '=' . $v['value'];
                }
            }
            if ($path) {
                $path = implode('/', $path);
                $this->field->setPath($path);
                $this->info['uri'] .= $path;
            }
        }

        $query = Dever::db('api/api_query')->select(['api_id' => $this->info['id']]);
        if ($query) {
            $param = [];
            foreach ($query as $k => $v) {
                $param[$v['key']] = $this->field->value($v['value'], $v['type']);
                if (is_array($param[$v['key']])) {
                    $param[$v['key']] = Dever::json_encode($param[$v['key']]);
                }
            }
            if ($param) {
                $this->field->setQuery($param);
                $this->info['uri'] .= '?' . http_build_query($param);
            }
        }
        $url = $this->platform['host'] . $this->info['uri'];
        $this->field->setUrl($url);
        return $url;
    }
}