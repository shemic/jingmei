<?php namespace Upload\Lib;
use Dever;
class Save
{
    private $config;
    private $cate_id;
    private $group_id;
    private $user_id;
    private $ext = [
        1 => 'jpg,png,gif,webp,jpeg',
        2 => 'mp3,m4a' ,
        3 => 'video,flv,mp4,webm,mov',
        4 => 'doc,xls,xlsx,docx',
        5 => 'pdf',
        6 => 'rar,zip',
        7 => 'cer,pfx,pem',
        8 => 'exe,msi',
    ];
    private $type = 1;
    public function init($id, $cate_id = 1, $group_id = false, $user_id = false, $project = 'api')
    {
        $this->config = Dever::db('upload/rule')->find($id);
        if (!$this->config) {
            Dever::error('上传规则错误');
        }
        $this->config['save'] = Dever::load(\Upload\Lib\Util::class)->getSaveInfo($this->config['save_id'], $project);
        //$this->config['save'] = Dever::db('upload/save')->find($this->config['save_id']);
        if (!$this->config['save']) {
            Dever::error('存储位置错误');
        }
        $this->cate_id = $cate_id ? $cate_id : 1;
        $this->group_id = $group_id;
        $this->user_id = $user_id;
        return $this;
    }

    public function get($id, $project = 'api')
    {
        $this->init($id, 1, false, false, $project);
        $result = [];
        $result['id'] = $id;
        $result['chunkSize'] = $this->config['chunk'];
        $result['path'] = $id;
        $result['size'] = $this->config['size'];
        $result['accept'] = [];
        $result['mine'] = [];
        $mine = $this->getExtByMine(false, true);
        $type = explode(',', $this->config['type']);
        foreach ($type as $v) {
            if (isset($this->ext[$v])) {
                $result['accept'][] = str_replace(',', ',.', $this->ext[$v]);
                //$result['accept'] .= '.' . str_replace(',', ',.', $this->ext[$v]);
                $temp = explode(',', $this->ext[$v]);
                foreach ($temp as $v2) {
                    if (isset($mine[$v2])) {
                        $result['mine'][] = $mine[$v2];
                    }
                }
            }
        }
        $result['accept'] = implode(',.', $result['accept']);
        $result['type'] = $this->config['save']['type'];
        $result['method'] = $this->config['save']['method'];
        if ($result['type'] > 1) {
            $result += Dever::load(Tool::class)->get($this->config['save'])->getInfo();
        }
        return $result;
    }

    public function act($source, $default_ext = '', $uid = false, $dest_name = '', $buffer = false)
    {
        if (!$this->config) {
            Dever::error('上传规则错误');
        }
        $name = '';
        $ext = '';
        $size = 0;
        $method = '';
        $source_name = '';
        $chunk = [];
        if (is_array($source) && isset($source['tmp_name'])) {
            # 文件上传
            $type = 1;
            if ($source['name'] == 'blob' && $source['type'] == 'application/octet-stream') {
                $source['name'] = Dever::input('name');
                $source['type'] = Dever::input('type');
            }
            $total = Dever::input('chunks');
            if ($total > 1) {
                $chunk['size'] = $this->config['chunk'];
                $chunk['uid'] = Dever::input('uid');
                $chunk['timestamp'] = Dever::input('timestamp');
                $chunk['cur'] = Dever::input('chunk');
                $chunk['total'] = $total;
                if (!$chunk['uid'] || !$chunk['timestamp'] || !$chunk['cur'] || !$chunk['total']) {
                    Dever::error('分片配置信息无效');
                }
                $name = $source_name = $source['name'] . $chunk['timestamp'] . $chunk['uid'];
            } else {
                $source_name = $source['name'];
                //$name = $source_name . uniqid(date('YmdHis'), true) . mt_rand(10000, 99999);
                $name = $source_name . $source['type'] . $source['size'];
            }
            
            $ext = $this->getExtByMine($source['type']);
            $size = $source['size'];
            $method = 'getimagesize';
            $source = $source['tmp_name'];
        } elseif (is_string($source)) {
            if ($buffer == true) {
                $type = 2;
                $name = $source;
                $ext = $default_ext;
                $size = 0;
            } elseif (strstr($source, ';base64,')) {
                # base64编码
                $temp = explode(';base64,', $source);
                $ext = $this->getExtByMine(ltrim($temp[0], 'data:'));
                $source = str_replace(' ', '+', $temp[1]);
                $source = str_replace('=', '', $temp[1]);
                $source = base64_decode($source);
                $size = strlen($source);
                $size = round(($size - ($size/8)*2)/1024, 2);
                $method = 'getimagesizefromstring';
                $type = 2;
            } else {
                if (strstr($source, 'http'))  {
                    # http远程文件
                    $name = $source;
                    $type = 3;
                    $ext = pathinfo($source, PATHINFO_EXTENSION);
                    if ($this->config['save']['type'] == 1) {
                        $content = Dever::curl($source)->log(false)->result();
                        $source = Dever::file('tmp/' . sha1($name));
                        file_put_contents($source, $content);
                    }
                } elseif (strstr($source, 'zip:///')) {
                    $type = 1;
                    $name = $source;
                    $ext = pathinfo($source, PATHINFO_EXTENSION);
                    $size = 0;
                }
                if (is_file($source)) {
                    # 本地文件
                    $type = 1;
                    $name = $source;
                    $finfo = finfo_open(FILEINFO_MIME);
                    $code = finfo_file($finfo, $source);
                    finfo_close($finfo);
                    $temp = explode(';', $code);
                    $ext = $this->getExtByMine($temp[0]);
                    $size = filesize($source);
                    $method = 'getimagesize';
                }
            }
        } else {
            $source = false;
        }
        if (!$source) {
            Dever::error('源文件不存在');
        }
        if (!$ext) {
            $ext = $this->getExtByByte($source);
        }
        if (!$ext && $default_ext) {
            $ext = $default_ext;
        }
        $state = $this->check($ext, $size, $method, $source);
        if (is_string($state)) {
            if (isset($content)) {
                @unlink($source);
            }
            Dever::error($state);
        }
        if ($dest_name && strstr($dest_name, '/')) {
            $dest = $dest_name;
        } else {
            if ($dest_name) {
                $name = $dest_name;
            }
            $dest = $this->config['id'] . '/' . $this->getDest($name, $ext, $uid);
            $system = Dever::call("Manage/Lib/Util.system", [false, true, "Upload/Lib/Manage.getFileField"]);
            if ($system && isset($system['database'])) {
                $dest = $system['database'] . '/' . $dest;
            }
        }
        # type 1是文件复制 2是base64 3是远程文件复制
        $url = Dever::load(Tool::class)->get($this->config['save'])->upload($type, $source, $dest, $chunk, $this);
        $data = $this->up($source_name, $name, $dest, $this->config['size'], $this->config['width'] ?? 0, $this->config['height'] ?? 0);
        $data['url'] = $url . '?t=' . time();
        $data['type'] = $this->type;
        if (isset($content)) {
            @unlink($source);
        }
        return $data;
    }

    public function addFile($url, $source_name, $name, $dest, $size)
    {
        if ($this->config['save']['type'] > 1) {
            $url = Dever::load(Tool::class)->get($this->config['save'])->getUrl(3, $url, $this);
            if (isset($this->config['size']) && $this->config['size']) {
                $size = $this->config['size'];
            }
        }
        
        $data = $this->up($source_name, $name, $dest, $size, $this->config['width'] ?? 0, $this->config['height'] ?? 0);
        $data['url'] = $url;
        return $data;
    }

    private function up($source_name, $name, $dest, $size, $width = 0, $height = 0)
    {
        $file['rule_id'] = $this->config['id'];
        $file['name'] = $name;
        $data = $file;
        $data['source_name'] = $source_name;
        $data['file'] = $dest;
        $data['save_id'] = $this->config['save_id'];
        $data['size'] = $size;
        if ($width) {
            $data['width'] = $width;
        }
        if ($height) {
            $data['height'] = $height;
        }
        if ($this->cate_id) {
            $data['cate_id'] = $this->cate_id;
        }
        if ($this->group_id) {
            $data['group_id'] = $this->group_id;
        }
        if ($this->user_id) {
            $data['user_id'] = $this->user_id;
        }
        $data['id'] = Dever::db('upload/file')->up($file, $data);
        return $data;
    }

    public function after()
    {
        $data = Dever::db('upload/rule_after')->select(['rule_id' => $this->config['id']]);
        if ($data) {
            foreach ($data as $k => $v) {
                $table = '';
                if ($v['type'] == 1) {
                    $table = 'thumb';
                } elseif ($v['type'] == 2) {
                    $table = 'crop';
                } elseif ($v['type'] == 3) {
                    $table = 'water_pic';
                } elseif ($v['type'] == 4) {
                    $table = 'water_txt';
                }
                if ($table && $v['type_id']) {
                    $data[$k]['table'] = $table;
                    $data[$k]['param'] = Dever::db($table, 'image')->find($v['type_id']);
                }
            }
        }
        return $data;
    }

    public function check($ext, $size, $info, $source = '')
    {
        $result = $this->checkExt($ext);
        if (is_string($result)) {
            return $result;
        }
        if ($size) {
            $result = $this->checkSize($size);
            if (is_string($result)) {
                return $result;
            }
            if ($this->type == 1 && $info) {
                if (is_string($info)) {
                    $info = $info($source);
                }
                if ($info) {
                    $result = $this->checkLimit($info);
                    if (is_string($result)) {
                        return $result;
                    }
                }
            }
        }
        return true;
    }

    protected function checkExt($ext)
    {
        $this->type = 0;
        if (!$ext) {
            return '文件格式错误';
        }
        $state = false;
        $type = explode(',', $this->config['type']);
        foreach ($type as $v) {
            if (isset($this->ext[$v]) && strstr($this->ext[$v], $ext)) {
                $this->type = $v;
                $state = true;
                break;
            }
        }
        if (!$state) {
            return '文件格式不符合要求';
        }
        $this->config['ext'] = $ext;
        return true;
    }

    protected function checkSize($size)
    {
        $set = $this->config['size'];
        if (!$set) {
            $set = 2;
        }
        $limit = $set * 1048576;
        if ($size > $limit) {
            return '文件不能超过'.$set.'MB';
        }
        $this->config['size'] = $size;
    }

    protected function checkLimit($info)
    {
        if (isset($this->config['limit']) && $this->config['limit'] == 2 && $info[0] < $info[1]) {
            return '文件高度不能超过文件宽度';
        } elseif (isset($this->config['limit']) &&  $this->config['limit'] == 3 && $info[0] > $info[1]) {
            return '文件宽度不能超过文件高度';
        } elseif ($this->config['min_width'] > 0 && $this->config['min_width'] > $info[0]) {
            return '文件宽度不能小于' . $this->config['min_width'] . 'px';
        } elseif ($this->config['min_height'] > 0 && $this->config['min_height'] > $info[1]) {
            return '文件高度不能小于' . $this->config['min_height'] . 'px';
        }
        $this->config['width'] = $info[0];
        $this->config['height'] = $info[1];
    }

    protected function getDest(&$name, $ext, $uid)
    {
        if ($uid) {
            $id = abs(intval($uid));
            $id = sprintf("%09d", $id);
            $dest = substr($id, 0, 3) . DIRECTORY_SEPARATOR . substr($id, 3, 2) . DIRECTORY_SEPARATOR . substr($id, 5, 2) . DIRECTORY_SEPARATOR . $uid . '.' . $ext;
            $name = $uid;
        } else {
            if (!strpos($name, '_cr_')) {
                $name = md5($name);
            }
            $path = array_slice(str_split($name, 2), 0, 3);
            $dest = implode(DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR . $name . '.' . $ext;
        }
        return $dest;
    }

    protected function getExtByMine($mine, $flip = false, $result = false)
    {
        if (!$mine) {
            return '';
        }
        $config = [
            'application/envoy' => 'evy',
            'application/fractals' => 'fif',
            'application/futuresplash' => 'spl',
            'application/hta' => 'hta',
            'application/internet-property-stream' => 'acx',
            'application/mac-binhex40' => 'hqx',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'video/x-m4v' => 'mp4',
            'video/mp4' => 'mp4',
            'application/octet-stream' => 'exe',
            'application/oda' => 'oda',
            'application/olescript' => 'axs',
            'application/pdf' => 'pdf',
            'application/pics-rules' => 'prf',
            'application/pkcs10' => 'p10',
            'application/pkix-crl' => 'crl',
            'application/postscript' => 'ai',
            'application/postscript' => 'eps',
            'application/postscript' => 'ps',
            'application/rtf' => 'rtf',
            'application/set-payment-initiation' => 'setpay',
            'application/set-registration-initiation' => 'setreg',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.ms-outlook' => 'msg',
            'application/vnd.ms-pkicertstore' => 'sst',
            'application/vnd.ms-pkiseccat' => 'cat',
            'application/vnd.ms-pkistl' => 'stl',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.ms-project' => 'mpp',
            'application/vnd.ms-works' => 'wps',
            'application/winhlp' => 'hlp',
            'application/x-bcpio' => 'bcpio',
            'application/x-cdf' => 'cdf',
            'application/x-compress' => 'z',
            'application/x-compressed' => 'tgz',
            'application/x-cpio' => 'cpio',
            'application/x-csh' => 'csh',
            'application/x-director' => 'dir',
            'application/x-dvi' => 'dvi',
            'application/x-gtar' => 'gtar',
            'application/x-gzip' => 'gz',
            'application/x-hdf' => 'hdf',
            'application/x-internet-signup' => 'isp',
            'application/x-iphone' => 'iii',
            'application/x-javascript' => 'js',
            'application/x-latex' => 'latex',
            'application/x-msaccess' => 'mdb',
            'application/x-mscardfile' => 'crd',
            'application/x-msclip' => 'clp',
            'application/x-msdownload' => 'dll',
            'application/x-msmediaview' => 'mvb',
            'application/x-msmetafile' => 'wmf',
            'application/x-msmoney' => 'mny',
            'application/x-mspublisher' => 'pub',
            'application/x-msschedule' => 'scd',
            'application/x-msterminal' => 'trm',
            'application/x-mswrite' => 'wri',
            'application/x-netcdf' => 'cdf',
            'application/x-netcdf' => 'nc',
            'application/x-perfmon' => 'pma',
            'application/x-pkcs12' => 'p12',
            'application/x-pkcs12' => 'pfx',
            'application/x-pkcs7-certificates' => 'p7b',
            'application/x-pkcs7-certreqresp' => 'p7r',
            'application/x-pkcs7-mime' => 'p7c',
            'application/x-pkcs7-signature' => 'p7s',
            'application/x-sh' => 'sh',
            'application/x-shar' => 'shar',
            'application/x-shockwave-flash' => 'swf',
            'application/x-stuffit' => 'sit',
            'application/x-sv4cpio' => 'sv4cpio',
            'application/x-sv4crc' => 'sv4crc',
            'application/x-tar' => 'tar',
            'application/x-tcl' => 'tcl',
            'application/x-tex' => 'tex',
            'application/x-texinfo' => 'texi',
            'application/x-texinfo' => 'texinfo',
            'application/x-troff' => 'roff',
            'application/x-troff' => 't',
            'application/x-troff' => 'tr',
            'application/x-troff-man' => 'man',
            'application/x-troff-me' => 'me',
            'application/x-troff-ms' => 'ms',
            'application/x-ustar' => 'ustar',
            'application/x-wais-source' => 'src',
            'application/x-x509-ca-cert' => 'cer',
            'application/ynd.ms-pkipko' => 'pko',
            'application/zip' => 'zip',
            'audio/basic' => 'au',
            'audio/basic' => 'snd',
            'audio/mid' => 'mid',
            'audio/mid' => 'rmi',
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/x-aiff' => 'aif',
            'audio/x-aiff' => 'aifc',
            'audio/x-aiff' => 'aiff',
            'audio/x-mpegurl' => 'm3u',
            'audio/x-pn-realaudio' => 'ram',
            'audio/x-wav' => 'wav',
            'image/png' => 'png',
            'image/bmp' => 'bmp',
            'image/cis-cod' => 'cod',
            'image/gif' => 'gif',
            'image/ief' => 'ief',
            'image/jpeg' => 'jpg',
            'image/pipeg' => 'jfif',
            'image/svg+xml' => 'svg',
            'image/tiff' => 'tif',
            'image/tiff' => 'tiff',
            'image/x-cmu-raster' => 'ras',
            'image/x-cmx' => 'cmx',
            'image/x-icon' => 'ico',
            'image/x-portable-anymap' => 'pnm',
            'image/x-portable-bitmap' => 'pbm',
            'image/x-portable-graymap' => 'pgm',
            'image/x-portable-pixmap' => 'ppm',
            'image/x-rgb' => 'rgb',
            'image/x-xbitmap' => 'xbm',
            'image/x-xpixmap' => 'xpm',
            'image/x-xwindowdump' => 'xwd',
            'message/rfc822' => 'mht',
            'message/rfc822' => 'mhtml',
            'message/rfc822' => 'nws',
            'text/css' => 'css',
            'text/h323' => '323',
            'text/html' => 'html',
            'text/iuls' => 'uls',
            'text/plain' => 'txt',
            'text/richtext' => 'rtx',
            'text/scriptlet' => 'sct',
            'text/tab-separated-values' => 'tsv',
            'text/webviewhtml' => 'htt',
            'text/x-component' => 'htc',
            'text/x-setext' => 'etx',
            'text/x-vcard' => 'vcf',
            'video/mpeg' => 'mpeg',
            'video/quicktime' => 'mov',
            'video/x-ms-asf' => 'asx',
            'video/x-msvideo' => 'avi',
            'video/x-sgi-movie' => 'movie',
            'x-world/x-vrml' => 'flr',
            'application/x-rar' => 'rar',
            'application/vnd.android.package-archive' => 'apk',
            'audio/webm' => 'webm',
            'video/webm' => 'webm',
            'audio/x-m4a' => 'm4a',
            'image/webp' => 'webp',
        ];

        if ($flip) {
            $config = array_flip($config);
        }
        $mine = trim($mine);
        if (isset($config[$mine])) {
            return $config[$mine];
        } else {
            return false;
        }
    }

    protected function getExtByByte($file)
    {
        $file = fopen($file, "rb");
        $bin = fread($file, 2);
        fclose($file);
        $strInfo = @unpack("c2chars",$bin);
        $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
        $fileType = '';
        switch ($typeCode) {
            case 7790:
                $fileType = 'exe';
                break;
            case 7784:
                $fileType = 'midi';
                break;
            case 8297:
                $fileType = 'rar';
                break;
            case 255216:
                $fileType = 'jpg';
                break;
            case 7173:
                $fileType = 'gif';
                break;
            case 13780:
                $fileType = 'png';
                break;
            case 6677:
                $fileType = 'bmp';
                break;
            case 6787:
                $fileType = 'swf';
                break;
            case 6063;
                $fileType = 'php|xml';
                break;
            case 6033:
                $fileType = 'html|htm|shtml';
                break;
            case 8075:
                $fileType = 'zip';
                break;
            case 6782:
            case 1310:
                $fileType = 'txt';
                break;
            case 4742:
                $fileType = 'js';
                break;
            case 8273:
                $fileType = 'wav';
                break;
            case 7368:
                $fileType = 'mp3';
                break;
            case 3780:
                $fileType = 'pdf';
                break;
            case 4545:
                $fileType = 'pem';
                break;
            case 7597:
                $fileType = 'fbx';
                break;
            default:
                $fileType = 'unknown'.$typeCode;
            break;
        }
        if ($strInfo['chars1'] == '-1' && $strInfo['chars2'] == '-40') {
            return 'jpg';
        }
        if ($strInfo['chars1'] == '-119' && $strInfo['chars2'] == '80') {
            return 'png';
        }
        if ($strInfo['chars1'] == '-48' && $strInfo['chars2'] == '-49') {
            return 'msi';
        }
        return $fileType;
    }
}