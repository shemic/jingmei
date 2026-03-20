<?php namespace Service\Manage\Lib;
use Dever;
class Template
{
    public function getCate()
    {
        $id = Dever::input('id');
        if ($id) {
            $info = Dever::db('service/template_data')->find($id);
            $template_id = $info['template_id'];
        } else {
            $template_id = Dever::load(\Manage\Lib\Util::class)->request('template_id');
        }
        return Dever::db('service/template_cate')->select(['template_id' => $template_id, 'status' => 1]);
    }
}