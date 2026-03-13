<?php namespace User\Manage\Lib;
use Dever;
class Run
{
    public function handle($db, $data)
    {
        if (empty($data['input_text']) && empty($data['input_file'])) {
            Dever::error('请输入文本或者上传文件');
        }
        
        if ($data['input_file']) {
            $data['input_file'] = Dever::load(\Upload\Lib\View::class)->local($data['input_file']);
            $data['input_file'] = str_replace('/www/', '/mnt/d/code/project/dm/container/web/', $data['input_file']);
        }

        $type = Dever::db('work/' . $data['type'])->find($data['type_code']);
        $param = [];
        $param['project_code'] = $data['project_code'];
        $param['app_code'] = $type['code'];
        if ($data['input_type'] == 'file') {
            $param['content'] = $data['input_file'];
        } else {
            $param['content'] = $data['input_text'];
        }
        print_r($param);die;

        return 'end';
    }
}