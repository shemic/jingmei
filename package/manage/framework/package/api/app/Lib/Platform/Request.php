<?php namespace Api\Lib\Platform;
use Dever;
class Request
{
    private $field;
    private $platform_id;
    private $type;
    private $type_id;
    public function init($field, $platform_id, $type, $type_id)
    {
        $this->field = $field;
        $this->platform_id = $platform_id;
        $this->type = $type;
        $this->type_id = $type_id;
        return $this;
    }

    public function body()
    {
        $body = [];
        $this->field->setBody($body);
        $this->load($body, 'body');
        $this->field->setBodyJson($body ? Dever::json_encode($body) : '');
        return $body;
    }

    public function header()
    {
        $header = [];
        $this->field->setHeader($header);
        $this->load($header, 'header');
        $this->field->setHeaderJson($header ? Dever::json_encode($header) : '');
        return $header;
    }

    protected function load(&$data, $type)
    {
        $this->get($data, 'platform', $type, ['platform_id' => $this->platform_id]);
        $this->get($data, $this->type, $type, [$this->type . '_id' => $this->type_id]);
    }

    protected function get(&$data, $prefix, $type, $where)
    {
        $request = Dever::db('api/' . $prefix . '_request_' . $type)->select($where);
        if ($request) {
            foreach ($request as $k => $v) {
                $value = $this->field->value($v['value'], $v['type']);
                if ($value) {
                    if (strstr($v['key'], '.')) {
                        $keys = explode('.', $v['key']);
                        $temp = &$data;
                        foreach ($keys as $key) {
                            $temp = &$temp[$key];
                        }
                        $temp = $value;
                    } else {
                        $data[$v['key']] = $value;
                    }
                    $this->field->set($v['key'], $value);
                    $this->field->add($v['key'], $value, $type);
                }
            }
        }
    }
}