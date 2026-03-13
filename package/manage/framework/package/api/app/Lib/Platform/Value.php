<?php namespace Api\Lib\Platform;
use Dever;
class Value
{
    private $field;
    public function init($field)
    {
        $this->field = $field;
        return $this;
    }

    public function get($config, $data)
    {
        $result = [];
        if ($config) {
            foreach ($data as $k => $v) {
                $this->field->set($k, $v);
            }
            $source = [];
            $dest = [];
            foreach ($config as $k => $v) {
                $temp = $this->convert($data, $v['value'], $v['key'], $v['type']);
                if ($temp) {
                    $result = array_replace_recursive($result, $temp);
                }
            }
        }
        if ($result) {
            $result = $this->value($result);
        } else {
            $result = $data;
        }
        return $result;
    }

    public function convert($array, $source, $dest, $type = '')
    {
        $default = $source;
        $source = explode('.', $source);
        $dest = explode('.', $dest);
        $extracted = $this->extracted($array, $source, $default, $type);
        return $this->transform($extracted, $dest);
    }

    public function extracted(&$array, $source, $default, $type = '')
    {
        $current = array_shift($source);
        if (substr($current, -2) == '[]') {
            $current = substr($current, 0, -2);
            $result = [];
            if (isset($array[$current]) && is_array($array[$current])) {
                foreach ($array[$current] as $item) {
                    $sub = $this->extracted($item, $source, $default, $type);
                    if ($sub !== null) {
                        $result[] = $sub;
                    }
                }
            }
            return $result;
        } else {
            $result = '';
            if (isset($array[$current])) {
                if (empty($source)) {
                    $result = $array[$current];
                } else {
                    return $this->extracted($array[$current], $source, $default, $type);
                }
            } elseif ($this->field->$current) {
                $result = $this->field->$current;
            } else {
                $result = $default;
            }
            if ($type) {
                $result .= '||' . $type;
            }
            return $result;
        }
        return null;
    }

    protected function transform($value, $dest)
    {
        $current = array_shift($dest);
        if (substr($current, -2) == '[]') {
            $current = substr($current, 0, -2);
            $result = [];
            $result[$current] = [];
            foreach ($value as $item) {
                $result[$current][] = $this->transform($item, $dest);
            }
            return $result;
        } else {
            if (empty($dest)) {
                return [$current => $value];
            } else {
                return [$current => $this->transform($value, $dest)];
            }
        }
    }

    protected function value($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        /*
        foreach ($data as $k => $v) {
            if (!is_array($v)) {
                $temp = explode('||', $v);
                $this->field->set($k, $temp[0]);
            }
        }*/
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                if (isset($v[0])) {
                    foreach ($v as $k1 => $v1) {
                        $data[$k][$k1] = $this->value($v1);
                    }
                } else {
                    $data[$k] = $this->value($v, $key);
                }
            } else {
                $temp = explode('||', $v);
                if (empty($temp[1])) {
                    $temp[1] = '';
                }
                $value = $temp[0];
                $type = $temp[1];
                if (strstr($value, '.')) {
                    $value = $this->extracted($data, explode('.', $value), $value);
                }
                $state = false;
                # 临时特殊处理，以后封装
                if (strstr($value, '\n')) {
                    $array = explode('\n', $value);
                    foreach ($array as &$v1) {
                        if (isset($data[$v1])) {
                            $v1 = $data[$v1];
                        }
                    }
                    $value = implode("\n", $array);
                    $state = true;
                } 
                
                $data[$k] = $this->field->value($value, $type, $state);
                if (!$state && strpos($data[$k], '{') === 0) {
                    $data[$k] = Dever::json_decode($data[$k]);
                }
                $this->field->set($k, $data[$k]);
            }
        }
        return $data;
    }
}