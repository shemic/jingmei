<?php namespace Upload\Api;
use Dever;
use Upload\Lib\Util;
use Upload\Lib\Save as Service;
class Save
{
    public $id = null;
    public $file = null;
    public $cate_id = null;
    public $group_id = null;
    public $user_id = null;
    public $project = null;

    public function __construct()
    {
        $this->id = Dever::input('id', 'is_numeric', '上传规则错误', 1);
        $this->file = Dever::input('file', '!empty', '上传文件错误');
        $this->cate_id = Dever::input('cate_id', 'is_numeric', '上传分类', 1);
        $this->project = Dever::input('project', 'is_string', '项目', 'api');
        $this->group_id = Dever::load(Util::class)->getGroup();
        $this->user_id = Dever::load(Util::class)->getUser();
    }

    public function act()
    {
        return Dever::load(Service::class)->init($this->id, $this->cate_id, $this->group_id, $this->user_id, $this->project)->act($this->file);
    }

    public function wangEditor()
    {
        Dever::config('setting', ['output_app' => [], 'output' => [
            'status' => ['errno', ['1' => 0, '2' => 1]],
            'msg' => 'message',
        ]]);
        return $this->act();
    }

    public function avatar()
    {
        $uid = Dever::input('uid', 'is_numeric', '用户ID');
        return Dever::load(Service::class)->init($this->id, $this->cate_id, $this->group_id, $this->user_id, $this->project)->act($this->file, 'jpg', $uid);
    }
}