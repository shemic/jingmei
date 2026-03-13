<?php namespace Dever;
use Dever;
class File
{
    public function id($id, $path)
    {
        $id = abs(intval($id));
        $sid = sprintf("%09d", $id);
        $dir1 = substr($sid, 0, 3);
        $dir2 = substr($sid, 3, 2);
        $dir3 = substr($sid, 5, 2);
        return $this->get($path . DIRECTORY_SEPARATOR . $dir1 . DIRECTORY_SEPARATOR . $dir2 . DIRECTORY_SEPARATOR . $dir3 . DIRECTORY_SEPARATOR . $id . '.jpg');
    }
    public function get($file, $path = '')
    {
        $file = $this->data() . DEVER_PROJECT . DIRECTORY_SEPARATOR . $file;
        $path = dirname($file);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
            @chmod($path, 0777);
            //@system('chmod -R 777 ' . $path);
        }
        return $file;
    }
    public function data()
    {
        if (isset(Dever::config('setting')['data'])) {
            return Dever::config('setting')['data'];
        }
        return DEVER_PROJECT_PATH . 'data' . DIRECTORY_SEPARATOR;
    }
}