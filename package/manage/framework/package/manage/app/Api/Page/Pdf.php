<?php namespace Manage\Api\Page;
use Dever;
use Manage\Lib\Page;
# pdf编辑器
class Pdf extends Page
{
    public function __construct($load = '', $input = true, $id = false)
    {
        parent::__construct('pdf', $load, $input);
    }
    public function get()
    {
        $this->checkFunc();
        if (is_string($this->config)) {
            $data = Dever::call($this->config, [$this->info]);
        } else {
            $data = $this->config;
        }
        if (isset($data['upload'])) {
            $upload = $this->getUpload($data['upload']);
            if (is_array($data['upload'])) {
                $upload += $data['upload'];
            } else {
                $upload += ['id' => $data['upload']];
            }
            if (empty($upload['id'])) {
                Dever::error('上传配置错误');
            }
            $upload['wh'] = '500*500';
            $data['upload'] = ['set' => Dever::url('image/manage.set', $upload, true)];
        }
        return $data;
    }
}