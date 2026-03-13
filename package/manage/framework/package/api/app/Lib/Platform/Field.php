<?php namespace Api\Lib\Platform;
use Dever;
class Field
{
    private $data = [];
    public $ssl;
    public function init($data)
    {
        $this->data = $data;
        $this->ssl = Dever::load(Ssl::class)->init($this);
        $this->set('time', time());
        $this->set('timestamp', \Dever\Helper\Secure::timestamp());
        $this->set('nonce', \Dever\Helper\Secure::nonce());
        return $this;
    }

    public function add($index, $value, $key = 'body')
    {
        $this->data[$key][$index] = $value;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        } else {
            return null;
        }
    }

    public function createRequestId()
    {
        $value = Dever::db('api/api_log')->insert([]);
        $this->set('request_id', $value);
    }

    public function setPlatformId($value)
    {
        $this->set('platform_id', $value);
    }

    public function setApid($value)
    {
        $this->set('api_id', $value);
    }

    public function setHost($value)
    {
        $this->set('body', $value);
    }

    public function setUri($value)
    {
        $this->set('uri', $value);
    }

    public function setPath($value)
    {
        $this->set('path', $value);
    }

    public function setQuery($value)
    {
        $this->set('query', $value);
    }

    public function setUrl($value)
    {
        $this->set('url', $value);
    }

    public function setNotify($value)
    {
        $this->set('notify', $value);
    }

    public function setMethod($value)
    {
        $this->set('method', $value);
    }

    public function setBody($value)
    {
        $this->set('body', $value);
    }

    public function setBodyJson($value)
    {
        $this->set('body_json', $value);
    }

    public function setHeader($value)
    {
        $this->set('header', $value);
    }

    public function setHeaderJson($value)
    {
        $this->set('header_json', $value);
    }

    public function setNumber($value)
    {
        $this->set('number', $value);
    }

    public function value($source, $type = '', $state = true)
    {
        $value = trim($source, " ");
        if ($this->data && isset($this->data[$value])) {
            $value = $this->data[$value];
        } elseif (strstr($value, 'key=') && strstr($value, '&')) {
            $value = $this->parse($value);
        } elseif ($a = strstr($value, '{') || strstr($value, '(')) {
            $value = $this->eval($value);
        }
        if (!$type && $source == $value) {
            /*
            $field = Dever::db('api/platform_field')->find(['platform_id' => $this->data['platform_id'], 'key' => $value]);
            if ($field) {
                $value = $field['value'];
                $type = $field['type'];
            } else {
                $sign = Dever::db('api/platform_sign')->find(['platform_id' => $this->data['platform_id'], 'name' => $value]);
                if ($sign) {
                    $value = '';
                    $type = '3,' . $sign['id'];
                }
            }*/
            $sign = Dever::db('api/platform_sign')->find(['platform_id' => $this->data['platform_id'], 'name' => $value]);
            if ($sign) {
                $value = '';
                $type = '3,' . $sign['id'];
            }
        }
        return $this->handle($type, $value, $state);
    }

    protected function parse($value)
    {
        parse_str($value, $temp);
        $k = $temp['key'];
        unset($temp['key']);
        if (isset($this->data[$k]) && isset($temp[$this->data[$k]])) {
            $value = $temp[$this->data[$k]];
        }
        return $value;
    }

    protected function eval($value)
    {
        $func = function ($r) {
            return $this->value($r[1]);
        };
        $value = preg_replace_callback('/{(.*?)}/', $func, $value);
        $value = '$value = '.$value.';';
        eval($value);
        return $value;
    }

    protected function handle($type, $value, $state)
    {
        if (strpos($type, ',')) {
            list($type, $type_id) = explode(',', $type);
            if ($type == 1) {
                $info = Dever::db('api/format')->find($type_id);
                if ($info) {
                    $value = \Dever\Helper\Str::val($info['method'], ['value' => $value]);
                }
            } elseif ($type == 2) {
                # state == true 是编码 == false 是解码
                if ($state) {
                    $value = $this->ssl->encrypt($type_id, $value);
                } else {
                    $value = $this->ssl->decrypt($type_id, $value);
                }
            } elseif ($type == 3) {
                $info = Dever::db('api/platform_sign')->find($type_id);
                if ($info) {
                    if ($value) {
                        $info['arg'] = $value;
                    }
                    $value = Dever::get(Sign::class, true, $info['id'])->init($this, $info)->get();
                }
            }
        }
        return $value;
    }
}