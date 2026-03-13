<?php namespace Manage\Api\Page;
use Dever;
use Manage\Lib\Page;
# 详情页
class View extends Page
{
    public function __construct($load = '', $input = true, $id = false)
    {
        parent::__construct('view', $load, $input);
    }
    public function get()
    {
        $this->checkFunc();
        if (is_string($this->config)) {
            $data = Dever::call($this->config, [$this->info]);
        } else {
            $data = $this->config;
        }
        return $data;
    }
}