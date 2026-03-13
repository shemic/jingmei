<?php namespace Upload\Lib\Tool;
use Dever;
use Image\Lib\Tool;
class Local
{
    private $config;
    public function init($config)
    {
        $this->config = $config;

    }
    public function upload($type, $source, $dest, $chunk, $upload)
    {
        $file = Dever::file('upload/' . $dest);
        if ($chunk) {
            $chunk['file'] = $file;
            $file = Dever::file('upload/' . $dest . '_chunk/' . $chunk['cur'] . '.blob');
        }
        if ($type == 1) {
            if (!copy($source, $file)) {
                Dever::error('上传失败');
            }
        } elseif ($type == 2) {
            file_put_contents($file, $source);
        } else {
            Dever::error('上传类型无效');
        }
        if (!is_file($file)) {
            Dever::error('上传失败');
        }
        if ($chunk) {
            $dir = dirname($file);
            if (is_dir($dir)) {
                $files = scandir($dir);
                $num = count($files) - 2;
                if ($num == $chunk['total']) {
                    $out = fopen($chunk['file'], 'ab');
                    natsort($files);
                    foreach ($files as $v) {
                        if (pathinfo($v, PATHINFO_EXTENSION) === 'blob') {
                            $temp = $dir . '/' . $v;
                            $in = fopen($temp, 'rb');
                            stream_copy_to_stream($in, $out); // 边读边写
                            fclose($in);
                            @unlink($temp); // 删除分片
                        }
                    }
                    fclose($out);
                    @rmdir($dir);
                    $file = $chunk['file'];
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }

        $after = $upload->after();
        if ($after) {
            $tool = Dever::load(Tool::class)->get()->cover(true);
            foreach ($after as $k => $v) {
                if (isset($v['table'])) {
                    $method = 'handle_' . $v['table'];
                    $file = $this->$method($tool, $file, $v['param']);
                }
            }
        }
        return $this->url($file);
    }

    private function url($file)
    {
        $dest = str_replace(Dever::data() . DEVER_PROJECT . '/', '', $file);
        if (isset($this->config['host']) && $this->config['host']) {
            return $this->config['host'] . $dest;
        }
        return Dever::host() . 'data/' . DEVER_PROJECT . '/' . $dest;
    }

    public function handle($id, $file, $type = 'thumb')
    {
        $param = Dever::db('image/' . $type)->find($id);
        $tool = Dever::load(Tool::class)->get()->cover(true);
        $file = $this->$method($tool, $file, $param);
        return $this->url($file);
    }

    private function handle_thumb($tool, $file, $param)
    {
        $tool->source($file);
        $result = $tool->thumb($param['width'] . '_' . $param['height']);
        if ($result) {
            return $result[0];
        }
        return $file;
    }

    private function handle_crop($tool, $file, $param)
    {
        $tool->source($file);
        $result = $tool->crop($param['width'] . '_' . $param['height'], $param['position']);
        if ($result) {
            return $result[0];
        }
        return $file;
    }
}