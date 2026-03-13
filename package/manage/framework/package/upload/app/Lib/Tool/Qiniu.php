<?php namespace Upload\Lib\Tool;
use Dever;
Dever::apply('upload/sdk/qiniu');
use Upload\Lib\Save;
use Image\Lib\Tool;
class Qiniu
{
    private $token;
    private $auth;
    private $bucket;
    private $host;
    public function init($config)
    {
        $this->auth = new \Qiniu\Auth($config['setting']['appkey'], $config['setting']['appsecret']);
        $this->token = $this->auth->uploadToken($config['setting']['bucket'], null, 3600);
        $this->bucket = $config['setting']['bucket'];
        $this->host = $config['setting']['host'];
    }

    # 获取基本信息
    public function getInfo()
    {
        return [
            'token' => $this->token,
            'bucket' => $this->bucket,
            'host' => $this->host,
        ];
    }

    # 查看文件
    public function view($file)
    {
        return $this->auth->privateDownloadUrl($file);
    }

    # 上传文件
    public function upload($type, $source, $dest, $chunk, $upload)
    {
        $result = [];
        $options = [];
        if ($type == 1) {
            $client = new \Qiniu\Storage\UploadManager();
            if ($chunk) {
                $log = Dever::file('upload/log');
                list($result, $err) = $client->putFile($this->token, $dest, $source, $options, 'application/octet-stream', false, $log, 'v2', $chunk['size'] * 1024 * 1024);
            } else {
                list($result, $err) = $client->putFile($this->token, $dest, $source, $options);
            }
        } elseif ($type == 2) {
            $client = new \Qiniu\Storage\UploadManager();
            list($result, $err) = $client->put($this->token, $dest, $source, $options);
        } elseif ($type == 3) {
            $client = new \Qiniu\Storage\BucketManager($this->auth);
            $items = $client->fetch($source, $this->bucket, $dest);
            if (isset($items[0])) {
                $result = $items[0];
            }
        } else {
            Dever::error('上传类型无效');
        }
        if (isset($result['hash']) && isset($result['key'])) {
            $file = $result['key'];
            $url = $this->host . $file;
            return $this->getUrl($type, $url, $upload);
        }
        Dever::error('上传失败');
    }

    public function getUrl($type, $url, $upload)
    {
        if ($type == 3) {
            $image = Dever::curl($url . '?imageInfo')->result();
            if ($image && !strstr($image, 'unsupported format')) {
                $image = Dever::json_decode($image);
                if (isset($image['width'])) {
                    $state = $upload->check($image['format'], $image['size'], [$image['width'], $image['height']]);
                    if (is_string($state)) {
                        $this->delete($file);
                        Dever::error($state);
                    }
                } else {
                    $this->delete($file);
                    Dever::error('上传失败');
                }
            }
        }
        $after = $upload->after();
        if ($after) {
            foreach ($after as $k => $v) {
                if (isset($v['table'])) {
                    $method = 'handle_' . $v['table'];
                    $url = $this->$method($url, $v['param']);
                }
            }
        }
        return $url;
    }

    public function handle($id, $file, $type = 'thumb')
    {
        $param = Dever::db('image/' . $type)->find($id);
        return $this->$method($file, $param);
    }

    private function handle_thumb($file, $param)
    {
        //?imageView2/2/w/360/h/270/format/png/q/75|imageslim
        $prefix = '';
        if (!strstr($file, 'imageMogr2')) {
            $prefix = '?imageMogr2';
        }
        if (strstr($file, '|imageslim')) {
            $file = str_replace('|imageslim', '', $file);
        }
        if ($param['height'] <= 0) {
            $param['height'] = '';
        }
        $dest = $file . $prefix . '/thumbnail/'.$param['width'].'x'.$param['height'] . '>';
        if (isset($param['compress']) && $param['compress'] > 0) {
            $dest .= '/quality/' . $param['compress'];
        }
        $dest .= '|imageslim';
        return $dest;
    }

    private function handle_crop($file, $param)
    {
        //?imageView2/2/w/360/h/270/format/png/q/75|imageslim
        $temp = parse_url($file);
        $info = ltrim($temp['path'], '/');
        $info = Dever::db('file', 'upload')->find(['file' => $info]);
        if (strstr($file, '|imageslim')) {
            $file = str_replace('|imageslim', '', $file);
        }
        $x = $y = 0;
        if ($info) {
            if (strstr($file, 'thumbnail/')) {
                $temp = explode('thumbnail/', $file);
                if (isset($temp[1])) {
                    $temp = explode('x>', $temp[1]);
                    $width = $info['width'];
                    $info['width'] = $temp[0];
                    $radio = $info['width']/$width;
                    if (isset($temp[1]) && $temp[1]) {
                        $info['height'] = $temp[1];
                    } else {
                        $info['height'] = round($radio*$info['height'], 2);
                    }
                }
            }
            list($x, $y) = Dever::load(Tool::class)->get()->position($info['width'], $info['height'], $param['width'], $param['height'], $param['position']);
        }
        $prefix = '';
        if (!strstr($file, 'imageMogr2')) {
            $prefix = '?imageMogr2';
        }
        $dest = $file . $prefix . '/crop/!'.$param['width'].'x'.$param['height'].'a'.$x.'a' . $y;
        $dest .= '|imageslim';
        return $dest;
    }

    # 删除文件
    public function delete($file, $bucket = false)
    {
        $bucket = $bucket ? $bucket : $this->bucket;
        $client = new \Qiniu\Storage\BucketManager($this->auth);
        $result = $client->delete($bucket, $file);
        return $result;
    }

    # 下载文件
    public function download($file, $bucket = false)
    {
        $bucket = $bucket ? $bucket : $this->bucket;
        $client = new \Qiniu\Storage\UploadManager();
        $content = $client->getObject($bucket, $file, []);
        return $content;
    }

    # 视频截图 vframe/jpg/offset/7/w/480/h/360
    public function cover($key, $file, $num = 1, $local = 2)
    {
        $file .= '?vframe/jpg/offset/' . $num;
        if ($local == 1) {
            $data = Dever::load(Save::class)->act(6, $file, 'jpg');
            return $data['url'] . '?time=' . time();
        } else {
            return $file;
        }
    }
}