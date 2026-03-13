<?php namespace Api\Lib\Platform;
use Dever;
class Sign
{
    private $field;
    private $config;
    private $info;
    public function init($field, $config)
    {
        $this->field = $field;
        $this->config = $config;
        return $this;
    }

    public function get()
    {
        $this->load();
        $this->encrypt();
        $this->after();
        $this->field->set($this->config['name'], $this->info);
        return $this->info;
    }

    public function check($arg = '')
    {
        if ($arg) {
            $this->config['arg'] = $arg;
        }
        $sign = $this->field->{$this->config['name']};
        if (!$sign) {
            Dever::error('签名验证失败');
        }
        if ($this->config['encrypt'] > 0) {
            $this->load();
            $check = $this->field->ssl->decrypt($this->config['encrypt'], $sign, $this->info);
        } else {
            $check = $sign == $this->get();
        }
        if (!$check) {
            Dever::error('签名验证失败');
        }
    }

    protected function load()
    {
        $this->create();
        if ($this->config['kv_sort'] == 2) {
            ksort($this->info);
        } elseif ($this->config['kv_sort'] == 3) {
            $this->info = array_values($this->info);
            sort($this->info, SORT_STRING);
        }
        $this->join();
        $this->toString();
    }

    protected function create()
    {
        $this->info = [];
        if ($this->config['arg']) {
            $col = explode("\n", $this->config['arg']);
            foreach ($col as $k => $v) {
                if ($v == 'body') {
                    $this->info = array_merge($this->field->body, $this->info);
                } elseif ($v == 'header') {
                    $this->info = array_merge($this->field->header, $this->info);
                } elseif ($v == 'query') {
                    $this->info = array_merge($this->field->query, $this->info);
                } else {
                    $k = $v;
                    if (strstr($v, '=')) {
                        $t = explode('=', $v);
                        $v = $t[1];
                        $k = $t[0];
                    }
                    $this->info[$k] = $this->field->value($v);
                }
            }
        } else {
            $this->info = $this->field->body;
        }
    }

    protected function join()
    {
        if (strstr($this->config['kv_join'], '\\')) {
            $this->config['kv_join'] = preg_replace_callback(
                '/\\\\([nrtf])/', // 匹配 \n, \r, \t, \f 等特殊字符
                function ($matches) {
                    $map = [
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        'f' => "\f"
                    ];
                    return $map[$matches[1]]; // 直接从映射中获取替换值
                },
                $this->config['kv_join']
            );
        }
    }

    protected function toString()
    {
        $string = '';
        foreach ($this->info as $k => $v) {
            if ($this->config['kv_value_empty'] == 2 && null === $v) {
                continue;
            }
            if (is_array($v)) {
                $v = Dever::json_encode($v);
            }
            if ($this->config['kv_key_handle']) {
                $k = Dever::load(\Api\Lib\Util::class)->format($this->config['kv_key_handle'], $k);
            }
            if ($this->config['kv_value_handle']) {
                $v = Dever::load(\Api\Lib\Util::class)->format($this->config['kv_value_handle'], $v);
            }
            if ($this->config['kv_type'] == 1) {
                $string .= $v;
            } elseif ($this->config['kv_type'] == 2) {
                $string .= $k;
            } elseif ($this->config['kv_type'] == 3) {
                $string .= $k . '=' . $v;
            } elseif ($this->config['kv_type'] == 4) {
                $string .= $k . $v;
            } elseif ($this->config['kv_type'] == 5) {
                $string .= $k . ':' . $v;
            }
            
            $string .= "{$this->config['kv_join']}";
        }
        if ($this->config['kv_join_handle'] == 1) {
            $this->info = rtrim($string, $this->config['kv_join']);
        } else {
            $this->info = $string;
        }
    }

    protected function encrypt()
    {
        $log = [];
        $log['request_id'] = $this->field->request_id;
        $log['name'] = $this->config['name'];
        $log['string'] = $this->info;
        if ($this->config['encrypt'] == -2) {
            $this->info = md5($this->info);
        } elseif ($this->config['encrypt'] == -3) {
            $this->info = hash("sha256", $this->info);
        } elseif ($this->config['encrypt'] == -4) {
            $this->info = sha1($this->info);
        } else {
            $this->info = $this->field->ssl->encrypt($this->config['encrypt'], $this->info);
        }
        $log['encode'] = $this->info;
        if ($this->field->log) {
            Dever::log($log, 'api_sign');
            Dever::debug($log, 'api_sign');
        }
    }

    protected function after()
    {
        if ($this->config['after']) {
            $this->info = Dever::load(\Api\Lib\Util::class)->format($this->config['after'], $this->info);
        }
    }
}