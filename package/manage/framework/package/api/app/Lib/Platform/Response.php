<?php namespace Api\Lib\Platform;
use Dever;
class Response
{
    private $body;
    private $header;
    private $field;
    private $type;
    private $type_id;
    private $config;

    public function init($body, $header, $field, $config, $type, $type_id)
    {
        $this->body = $body;
        $this->header = $header;
        $this->field = $field;
        $this->type = $type;
        $this->type_id = $type_id;
        $this->config = $config;
        return $this;
    }

    public function out()
    {
        $this->header();
        $body = $this->body();
        $this->verify();
        
        $data = $this->handle($this->body);
        $this->status($data);
        return $data;
    }

    public function header()
    {
        $header = Dever::db('api/' . $this->type . '_response_header')->select([$this->type . '_id' => $this->type_id]);
        if (!$header) {
            $header = Dever::db('api/platform_response_header')->select(['platform_id' => $this->config['id']]);
        }
        if ($header) {
            foreach ($header as $k => $v) {
                if (isset($this->header[$v['value']])) {
                    $value = $this->field->value($this->header[$v['value']], $v['type'], false);
                    $this->field->set($v['key'], $value);
                }
            }
        }
    }

    public function body()
    {
        if ($this->config['type'] == 1) {
            $this->body = $this->filter($this->body);
            return $this->body;
        }
        $this->field->setBodyJson($this->body);
        $this->parse();
        $this->field->setBody($this->body);
    }

    # 响应是否验签
    protected function verify()
    {
        # 这里和notify里的verify一样就行，暂时不做，一般响应都不验签
    }

    protected function parse()
    {
        if ($this->config['type'] == 2) {
            $this->body = Dever::json_decode($this->body);
        } elseif ($this->config['type'] == 3) {
            $this->body = (array) simplexml_load_string($this->body, null, LIBXML_NOCDATA);
        } elseif ($this->config['type'] == 4) {
            
        } else {
            if (strstr($this->body, ',')) {
                $this->body = explode(',', $this->body);
            } elseif (strstr($this->body, ' ')) {
                $this->body = explode(' ', $this->body);
            } elseif (strstr($this->body, '|')) {
                $this->body = explode('|', $this->body);
            } else {
                $this->body = explode("\n", $this->body);
            }
        }
    }

    protected function status($data)
    {
        $msg = '';
        $status = 1;
        $code = Dever::db('api/platform_response_code')->select(['platform_id' => $this->config['id']]);
        if ($code) {
            foreach ($code as $k => $v) {
                if (isset($data[$v['key']]) && $data[$v['key']] == $v['value']) {
                    $status = $v['type'];
                    if ($v['msg'] && isset($data[$v['msg']])) {
                        $msg = $data[$v['msg']];
                    }
                    break;
                }
            }
        }
        if ($status == 2) {
            if (!$msg) $msg = 'error';
            Dever::error($msg);
        }
    }

    protected function handle($data)
    {
        $result = [];
        $body = Dever::db('api/' . $this->type . '_response_body')->select([$this->type . '_id' => $this->type_id]);
        if (!$body) {
            $body = Dever::db('api/platform_response_body')->select(['platform_id' => $this->config['id']]);
        }
        $value = Dever::load(Value::class)->init($this->field);
        $result = $value->get($body, $data);
        $this->save($value, $result);
        return $result;
    }

    protected function save($value, $data)
    {
        $save = Dever::db('api/' . $this->type . '_save')->select([$this->type . '_id' => $this->type_id]);
        if ($save) {
            $table = [];
            foreach ($save as $k => $v) {
                if (!isset($table[$v['table']])) {
                    $table[$v['table']] = [
                        'total' => 0,
                        'data' => [],
                        'where' => [],
                    ];
                }
                if (strstr($v['value'], '.')) {
                    $v['value'] = explode('.', $v['value']);
                    $v['value'] = $value->extracted($data, $v['value']);
                    $table[$v['table']]['total'] = count($v['value']);
                } else {
                    if (isset($data[$v['value']])) {
                        $v['value'] = $data[$v['value']];
                    }
                }
                if ($v['type'] == 3) {
                    # 转时间戳
                    foreach ($v['value'] as $k1 => $v1) {
                        $v['value'][$k1] = strtotime($v1);
                    }
                }
                $table[$v['table']]['data'][$v['key']] = $v['value'];
                if ($v['type'] == 2) {
                    $table[$v['table']]['where'][$v['key']] = $v['value'];
                }
            }
            if ($table) {
                foreach ($table as $k => $v) {
                    $update = [];
                    foreach ($v['data'] as $k1 => $v1) {
                        for ($i = 0; $i < $v['total']; $i++) {
                            $update[$i]['data'][$k1] = (is_array($v1) && isset($v1[$i])) ? $v1[$i] : $v1;
                        }
                    }
                    foreach ($v['where'] as $k1 => $v1) {
                        for ($i = 0; $i < $v['total']; $i++) {
                            $update[$i]['where'][$k1] = (is_array($v1) && isset($v1[$i])) ? $v1[$i] : $v1;
                        }
                    }
                    foreach ($update as $k1 => $v1) {
                        $id = $this->saveData($k, $v1['where'], $v1['data']);
                        if ($id && $k == 'platform_cert') {
                            # 这个比较特殊
                            $project = $this->field->account_project;
                            $account_id = $this->field->account_id;
                            if ($project && $account_id) {
                                $cert = [
                                    'account_id' => $account_id,
                                    'platform_cert_id' => $id,
                                ];
                                $v1['where'] += $cert;
                                $v1['data'] += $cert;
                                $this->saveData($project . '/account_cert', $v1['where'], $v1['data']);
                            }
                        } else {
                            $this->saveData($k, $v1['where'], $v1['data']);
                        }
                    }
                }
            }
        }
    }

    protected function saveData($table, $where, $data)
    {
        if ($where) {
            $info = Dever::db($table)->find($where);
            if ($info) {
                Dever::db($table)->update($info['id'], $data);
                $id = $info['id'];
            } else {
                $id = Dever::db($table)->insert($data);
            }
        } else {
            $id = Dever::db($table)->insert($data);
        }
        return $id;
    }

    protected function filter($data)
    {
        return preg_replace("/<!--[^\!\[]*?(?<!\/\/)-->/","",$data);
    }
}