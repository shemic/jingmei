<?php namespace Upload\Manage\Api;
use Dever;
use Manage\Lib\Auth;
use Upload\Lib\Util;
use Upload\Lib\Save;
class Manage extends Auth
{
    # 获取图片组件信息
    public function getImage($value = false)
    {
        Dever::project('image');
        $result = [];
        $option = [];
        if ($value == 1) {
            $option = Dever::db('image/thumb')->select([]);
        } elseif ($value == 2) {
            $option = Dever::db('image/crop')->select([]);
        } elseif ($value == 3) {
            $option = Dever::db('image/water_pic')->select([]);
        } elseif ($value == 4) {
            $option = Dever::db('image/water_txt')->select([]);
        }
        $result['type_id']['type'] = 'select';
        $result['type_id']['option'] = $option;
        $result['type_id']['value'] = $option[0]['id'] ?? '';
        return $result;
    }

    # 删除文件
    public function upFileStatus()
    {
        $rule_id = Dever::input('id');
        $id = Dever::input('file_id');
        $status = Dever::input('status', 'is_numeric', '状态', 2);
        $user_id = Dever::load(Util::class)->getUser();
        return Dever::db('upload/file')->update(['id' => $id, 'user_id' => $user_id], ['status' => $status]);
    }

    # 彻底删除文件
    public function delFile()
    {
        $rule_id = Dever::input('id');
        $id = Dever::input('file_id');
        $user_id = Dever::load(Util::class)->getUser();
        $state = Dever::db('upload/file')->delete(['id' => $id, 'status' => 2, 'user_id' => $user_id]);
        if ($state) {
            # 同时删除文件
        }
        return 'ok';
    }

    # 添加文件
    public function addFile($id, $url, $file, $source, $name, $size)
    {
        $data = Dever::load(Save::class)->init($id)->addFile($url, $source, $name, $file, $size);
        return $data;
    }

    # 获取文件库文件列表
    public function getFileList()
    {
        $type = Dever::input('type', 'is_numeric', '类型', 0);
        $id = Dever::input('id', 'is_numeric', '上传规则错误', 1);
        $cate_id = Dever::input('cate_id', 'is_numeric', '上传分类', 1);
        $group_id = Dever::load(Util::class)->getGroup();
        $user_id = Dever::load(Util::class)->getUser();
        $file = Dever::input('file');

        $data = Dever::input();

        $set = [];
        $set['num'] = 18;
        $where['rule_id'] = $id;
        $where['status'] = 1;
        #$where['cate_id'] = $cate_id;
        if ($type == 1) {
            $where['group_id'] = $group_id;
        } elseif ($type == 2) {
            $where['user_id'] = $user_id;
        } elseif ($type == 4) {
            $where['status'] = 2;
        }
        
        $result['file'] = Dever::db('upload/file')->select($where, $set);
        if ($result['file']) {
            foreach ($result['file'] as &$v) {
                if ($v['source_name']) {
                    $v['name'] = $v['source_name'];
                }
                $v['url'] = Dever::load(\Upload\Lib\View::class)->getUrl($v);
                $v['class'] = '';
                $v['del'] = 2;
                if ($user_id == $v['user_id']) {
                    $v['del'] = 1;
                }
                if ($file) {
                    foreach ($file as $v1) {
                        if ($v1 && $v1['url'] == $v['url']) {
                            $v['class'] = 'show-image-active';
                        }
                    }
                }
            }
        }
        $result['total'] = Dever::page('total');
        return $result;
    }
}