<?php namespace Image\Manage\Api;
use Dever;
use Manage\Lib\Auth;
use Upload\Lib\Util;
use Upload\Lib\Save;
class Manage
{
    public function set()
    {
        $data = array();
        $data['authorization'] = Dever::input('authorization');
        $data['id'] = Dever::input('id');
        $data['state'] = Dever::input('state');
        $data['name'] = Dever::input('name');
        $data['pic'] = Dever::input('pic');
        $data['type'] = Dever::input('type');
        $data['wh'] = Dever::input('wh');
        $data['group_id'] = Dever::load(Util::class)->getGroup();
        $data['user_id'] = Dever::load(Util::class)->getUser();
        $data['project'] = Dever::input('project');
        $data['ratio'] = 16 / 9;
        if ($data['wh']) {
            if (strstr($data['wh'], '*')) {
                $data['ratio'] = $this->ratio('*', $data['wh']);
            } elseif (strstr($data['wh'], 'x')) {
                $data['ratio'] = $this->ratio('x', $data['wh']);
            } elseif (strstr($data['wh'], 'X')) {
                $data['ratio'] = $this->ratio('X', $data['wh']);
            } else {
                $data['ratio'] = 1;
            }
        }
        $data['search_cate'] = 1;
        $data['param'] = '';

        if (strstr($data['pic'], '?')) {
            $temp = explode('?', $data['pic']);
            $data['pic'] = $temp[0];
        }
        if (strstr($data['pic'], '_cr_')) {
            $ext = '.' . pathinfo($data['pic'], PATHINFO_EXTENSION);
            $temp = explode('_cr_', $data['pic']);
            $param = $data['pic'];
            $data['pic'] = $temp[0];
            $data['param'] = str_replace($ext, '', $temp[1]);
        }
        # 查找原图
        $data['pic'] = Dever::load(\Upload\Lib\View::class)->getSource($data['pic'], true);

        $data['submit'] = Dever::url('image/manage.cropper');
        Dever::view('set', $data);
    }

    public function cropper()
    {
        $send['param'] = array();
        $input = Dever::input();
        foreach ($input as $k => $v) {
            if (strpos($k, 'param_') === 0) {
                $send['param'][$k] = $v;
            }
        }
        $cate = 3;
        $group_id = Dever::input('group_id');
        $user_id = Dever::input('user_id');
        $project = Dever::input('project');
        $id = Dever::input('id');
        $source = Dever::input('img');
        $dest = Dever::input('pic');
        $dest = explode('/upload/', $dest);
        $dest = end($dest);
        $dest .= '_cr_' . implode('_', $send['param']) . '.png';
        $result = Dever::load(Save::class)->init($id, $cate, $group_id, $user_id, $project)->act($source, false, false, $dest);
        return $result;
    }

    private function ratio($str, $wh)
    {
        $temp = explode($str, $wh);
        $ratio = $temp[0] / $temp[1];
        return $ratio;
    }
}