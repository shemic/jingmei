<?php namespace Image\Lib\Tool;
use Dever;
class Gd extends Core
{
    # 缩放
    public function thumb($set)
    {
        if (!$this->im) {
            return false;
        }
        $result = array();
        if (!is_array($set)) {
            $set = explode(',', $set);
        }
        $source_x = imagesx($this->im);
        $source_y = imagesy($this->im);
        $source_w = $source_x/$source_y;
        $source_h = $source_y/$source_x;
        foreach ($set as $k => $v) {
            $result[$k] = $this->getDest($v . '_thumb');
            if ($this->check($result[$k])) {
                list($dest_x, $dest_y, $offset) = $this->getXy($v, $source_x, $source_y, $source_w, $source_h);
                $im = $this->copy($this->im, $dest_x, $dest_y, $source_x, $source_y, 0, 0, false, 1);
                imagejpeg($im, $result[$k]);
                $this->destroy($im);
            }
        }
        return $result;
    }

    # 裁剪
    public function crop($set, $position)
    {
        if (!$this->im) {
            return false;
        }
        $result = array();
        if (!is_array($set)) {
            $set = explode(',', $set);
        }
        $source_x = imagesx($this->im);
        $source_y = imagesy($this->im);
        foreach ($set as $k => $v) {
            $result[$k] = $this->getDest($v . '_crop');
            if ($this->check($result[$k])) {
                $x = 0;
                $y = 0;
                $offset = explode('_', $v);
                if (isset($offset[2]) && $offset[2]) {
                    $offset[0] += $offset[2];
                    $offset[1] += $offset[2];
                }
                if ($position) {
                    if (!is_array($position)) {
                        list($x, $y) = $this->position($source_x, $source_y, $offset[0], $offset[1], $position);
                    } else {
                        # 加入根据百分比计算裁图
                        if ($position[0] <= 0) {
                            $position[0] = $source_x/2 - $offset[0]/2;
                        } elseif (strstr($position[0], '%')) {
                            $position[0] = $source_x * intval(str_replace('%', '', $position[0]))/100;
                        }
                        if ($position[1] <= 0) {
                            $position[1] = $source_y/2 - $offset[1]/2;
                        } elseif (strstr($position[1], '%')) {
                            $position[1] = $source_y * intval(str_replace('%', '', $position[1]))/100;
                        }
                        $x = $position[0];
                        $y = $position[1];
                    }
                } else {
                    $x = $source_x/2 - $offset[0]/2;
                    $y = $source_y/2 - $offset[1]/2;
                }
                if ($x < 0) {
                    $x = 0;
                }
                if ($y < 0) {
                    $y = 0;
                }
                $im = $this->copy($this->im, $offset[0], $offset[1], $offset[0], $offset[1], $x, $y);
                imagejpeg($im, $result[$k]);
                $this->destroy($im);
            }
        }
        return $result;
    }

    # 图片水印
    public function pic($water, $position = 5, $offset = 0, $width = 0, $height = 0, $radius = 0)
    {
        if (!$this->im) {
            return false;
        }
        $result = $this->getDest('mark');
        if ($this->check($result)) {
            if ($radius) {
                $water = $this->get_radius($water, $radius);
            } else {
                $water  = $this->get($water);
            }
            $source_x = imagesx($this->im);
            $source_y = imagesy($this->im);
            $water_x = imagesx($water);
            $water_y = imagesy($water);
            if ($width || $height) {
                $water_w = $water_x/$water_y;
                $water_h = $water_y/$water_x;
                if ($water_x > $width) {
                    $dest_x = $width;
                    $dest_y = $width*$water_h;
                } elseif ($height > 0 && $water_y > $height) {
                    $dest_x = $height*$water_w;
                    $dest_y = $height;
                } else {
                    $dest_x = $water_x;
                    $dest_y = $water_y;
                }
                $water = $this->copy($water, $dest_x, $dest_y, $water_x, $water_y, 0, 0, false, 2);
                $xy = $this->position($source_x, $source_y, $dest_x, $dest_y, $position, $offset);
                $water_x = $dest_x;
                $water_y = $dest_y;
            } else {
                $xy = $this->position($source_x, $source_y, $water_x, $water_y, $position, $offset);
            }
            if ($xy[0] == -1) {
                # 水印平铺 gd的先不做了
                $xy[0] = $xy[1] = 0;
            }
            if ($xy[2] == false) {
                $this->destroy($water);
                return;
            }
            $im = $this->copy($water, $water_x, $water_y, 0, 0, $xy[0], $xy[1], $this->im);
            imagejpeg($im, $result);
            $this->destroy($water);
        }
        return $result;
    }

    # 文字水印
    public function txt($name, $position = 5, $offset = 0, $size = 10, $color = '', $angle = 0, $width = 0, $font = 'SIMSUN.TTC')
    {
        if (!$this->im) {
            return false;
        }
        $result = $this->getDest('txt');
        if ($this->check($result)) {
            $autowrap = 0;
            if ($width > 0) {
                $name = $this->autowrap($autowrap, $size, $angle, $font, $name, $width);
            }
            $position = imagettfbbox($size, $angle, $font, $name);
            if ($position) {
                $source_x = imagesx($this->im);
                $source_y = imagesy($this->im);
                $water_x = $position[2] - $position[0];
                $water_y = $position[1] - $position[7];
                $xy = $this->position($source_x, $source_y, $water_x, $water_y, $position, $offset);
            }
            if ($color && (strlen($color)==7)) {
                $left = $xy[0] ?? 0;
                $top = $xy[1] ?? 0;
                $R = hexdec(substr($color,1,2)); 
                $G = hexdec(substr($color,3,2)); 
                $B = hexdec(substr($color,5)); 
                putenv('GDFONTPATH=' . realpath('.'));
                imagettftext($this->im, $size, $angle, $left, $top + $autowrap, imagecolorallocate($this->im, $R, $G, $B), $font, $name);
            }
            imagejpeg($this->im, $result);
        }
        return $result;
    }

    protected function copy($im, $w, $h, $x, $y, $l, $t, $dim = false, $ti = 1)
    {
        if ($dim == false) {
            $dim = $this->create($w, $h, $ti);
            imagecopyresized($dim, $im, 0, 0, $l, $t, $w, $h, $x, $y);
        } else {
            imagecopy($dim, $im, $l, $t, 0, 0, $w, $h);
            //imagecopyresampled($dim, $im, $l,$t, 0, 0, $w, $h, $x, $y);
        }
        return $dim;
    }

    protected function create($w, $h, $t = 1)
    {
        $im = imagecreatetruecolor($w,$h);
        if ($t == 1) {
            # 空白背景
            $wite = ImageColorAllocate($im, 255,255,255);
            imagefilledrectangle($im, 0, 0, $w, $h, $wite);
            imagefilledrectangle($im, $w, $h, 0,0, $wite);
            ImageColorTransparent($im, $wite);
        } elseif ($t == 2) {
            # 透明背景
            imagealphablending($im, false);
            imagesavealpha($im,true);
            $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
            imagefilledrectangle($im, 0, 0, $w, $h, $transparent);
        }
        return $im;
    }
    protected function get($im)
    {
        $im = file_get_contents($im);
        $im = imagecreatefromstring($im);
        return $im;
    }
    # 圆角图片
    private function get_radius($imgpath = '', $radius = 0)
    {
        $ext     = pathinfo($imgpath);
        $src_img = null;
        switch ($ext['extension']) {
        case 'jpg':
            $src_img = imagecreatefromjpeg($imgpath);
            break;
        case 'png':
            $src_img = imagecreatefrompng($imgpath);
            break;
        }
        $wh = getimagesize($imgpath);
        $w  = $wh[0];
        $h  = $wh[1];
        $radius = $radius <= 0 ? (min($w, $h) / 2) : $radius;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r = $radius; //圆 角半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                    //不在四角的范围内,直接画
                    imagesetpixel($img, $x, $y, $rgbColor);
                } else {
                    //在四角的范围内选择画
                    //上左
                    $y_x = $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //上右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下左
                    $y_x = $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }
        return $img;
    }
    protected function _destroy()
    {
        imagedestroy($this->im);
        $this->im = false;
    }
}