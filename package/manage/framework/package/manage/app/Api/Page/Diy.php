<?php namespace Manage\Api\Page;
use Dever;
use Manage\Lib\Page;
# 自定义页面
class Diy extends Page
{
    public function __construct($load = '', $input = true, $id = false)
    {
        parent::__construct('diy', $load, $input);
    }
    public function get()
    {
        $this->checkFunc();
        if (is_string($this->config)) {
            $data = Dever::call($this->config, [$this->info]);
        } else {
            $data = $this->config;
        }
        $where = $this->config['where'] ?? [];
        if (isset($data['search'])) {
            $data['search'] = $this->search($where);
            $data['search']['type'] = 'search';
        }
        if (isset($data['data']) && $data['data']) {
            $data = Dever::call($data['data'], [$where, $data]);
        }
        return $data;
    }
}