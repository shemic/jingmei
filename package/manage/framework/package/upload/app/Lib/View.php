<?php namespace Upload\Lib;
use Dever;
class View
{
    private $config = [
        1 => 'Local',
        2 => 'Qiniu',
        3 => 'Oss',
    ];

    # 获取源文件信息
    public function getSource($file, $url = false)
    {
        $path = parse_url($file, PHP_URL_PATH);
        $filename = basename($path);
        $parts = explode('_', $filename);
        $pureName = $parts[0];
        $pureName = pathinfo($pureName, PATHINFO_FILENAME);
        $info = Dever::db('upload/file')->find(['name' => $pureName]);
        if ($url) {
            $file = $this->getUrl($info);
            return $file;
        }
        return $info;
    }

    public function getUrl($info, $project = 'api')
    {
        if (strstr($info['file'], 'http')) {
            return $info['file'];
        }
        $save = Dever::load(\Upload\Lib\Util::class)->getSaveInfo($info['save_id'], $project);
        //$save = Dever::db('upload/save')->find($info['save_id']);
        if (isset($save['setting']['host']) && $save['setting']['host']) {
            $host = $save['setting']['host'];
            if (strstr($host, '{host}')) {
                $host = str_replace('{host}', Dever::host(), $host);
            }
        } else {
            $host = Dever::host() . 'data/' . DEVER_PROJECT . '/upload/';
        }
        return $host . $info['file'];
    }

    public function local($file, $convert = false)
    {
        if (strstr($file, '?')) {
            $temp = explode('?', $file);
            $file = $temp[0];
        }
        $base = Dever::data();
        $host = Dever::host() . 'data/';
        if (strstr($file, $host)) {
            $local = str_replace($host, $base, $file);
        } else {
            $local = Dever::file('tmp/' . md5($file));
            file_put_contents($local, file_get_contents($file), LOCK_EX);
        }
        if ($convert) {
            if (strstr($base, '/www/') && !strstr($base, 'wwwroot')) {
                $local = str_replace('/www/', '/data/dm/container/web/', $local);
            }
        }
        return $local;
    }

    public function http($file)
    {
        $base = Dever::data();
        $host = Dever::host() . 'data/';
        if (strstr($file, $base)) {
            return str_replace($base, $host, $file);
        } else {
            $local = Dever::file('tmp/' . md5($file));
            file_put_contents($local, file_get_contents($file), LOCK_EX);
            return str_replace($base, $host, $local);
        }
    }

    # 从内容中解析文件
    public function file($content, $domain, $local = false)
    {
        $content = preg_replace_callback('/[0-9a-zA-Z\-\\/]+(\.jpeg|\.jpg|\.png|\.gif|\.mp3|\.mp4|\.aov|\.m4a)/i', function($matches) use($domain, $local)
        {
            $file = $matches[0];
            $file = ltrim($file, '/');
            if (!strstr($file, 'http')) {
                $file = $domain . $file;
            }
            if ($local) {
                $upload = Dever::load(Save::class)->act($local, $file);
                if ($upload && isset($upload['url'])) {
                    $file = $upload['url'];
                }
            }
            return $file;
        }, $content);
        return $content;
    }
}