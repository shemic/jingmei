<?php namespace Upload\Lib\Tool;
use Dever;
Dever::apply('upload/sdk/oss');
use Upload\Lib\Save;
use Image\Lib\Tool;
class Oss
{
    private $token;
    private $auth;
    private $bucket;
    public function init($config)
    {
        $this->auth = new \Qiniu\Auth($config['appkey'], $config['appsecret']);
        $this->token = $this->auth->uploadToken($config['bucket'], null, 3600);
        $this->bucket = $config['bucket'];
        $this->client = new OssClient($accessKey, $secretKey, $endpoint, false, $token);
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
    public function upload($type, $source, $dest)
    {
        $ret = [];
        $options = [];
        if ($type == 1) {
            $client = new \Qiniu\Storage\UploadManager();
            list($ret, $err) = $client->putFile($this->token, $dest, $source, $options);
        } elseif ($type == 2) {
            $client = new \Qiniu\Storage\UploadManager();
            list($ret, $err) = $client->put($this->token, $dest, $source, $options);
        } elseif (strstr($source, 'http')) {
            $method = 'fetch';
            $client = new \Qiniu\Storage\BucketManager($this->auth);
            $items = $client->fetch($source, $this->bucket, $dest);
            if (isset($items[0])) {
                $ret = $items[0];
            }
        } else {
            Dever::error('上传类型无效');
        }
        return $ret;
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
            $data = Dever::load('upload')->save(6, $file, 'jpg');
            return $data['url'] . '?time=' . time();
        } else {
            return $file;
        }
    }
}