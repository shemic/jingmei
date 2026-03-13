<?php namespace Image\Lib\Tool;
use Dever;
class Mg extends Core
{
    private $imageType = false;
    private function imageType()
    {
        if (!$this->imageType) {
            $this->imageType = strtolower($this->im->getImageFormat());
        }
    }

    # 缩放
    public function thumb($set)
    {
        if (!$this->im) {
            return false;
        }
        $this->imageType();
        if (!is_array($set)) {
            $set = explode(',', $set);
        }
        $source_x   = $this->im->getImageWidth();
        $source_y   = $this->im->getImageHeight();
        $source_w = $source_x/$source_y;
        $source_h = $source_y/$source_x;
        foreach ($set as $k => $v) {
            $result[$k] = $this->getDest($v . '_thumb');
            if ($this->check($result[$k])) {
                list($dest_x, $dest_y, $offset) = $this->getXy($v, $source_x, $source_y, $source_w, $source_h);
                //$this->im = $this->get($this->source);
                $this->im->thumbnailImage($dest_x, $dest_y);
                
                if (isset($offset[2]) && $offset[2] == 3) {
                    /* 按照缩略图大小创建一个有颜色的图片 */  
                    $canvas = $this->get();
                    $color = new \ImagickPixel("white");
                    $canvas->newImage($offset[0], $offset[1], $color, 'png');
                    //$canvas->paintfloodfillimage('transparent',2000,NULL,0,0);
                    /* 计算高度 */
                    $x = ($offset[0] - $dest_x)/2;
                    $y = ($offset[1] - $dest_y)/2;
                    /* 合并图片 */
                    $canvas->compositeImage($this->im, \Imagick::COMPOSITE_OVER, $x, $y);
                    $canvas->setCompression(\Imagick::COMPRESSION_JPEG);
                    $canvas->setCompressionQuality(100);
                    $canvas->writeImage($result[$k]);
                    if (isset($offset[3]) && $offset[3]) {
                        $offset[3] = $offset[3] * 1024;
                        $size = abs(filesize($result[$k]));
                        if ($size > $offset[3]) {
                            $this->compress($canvas, $offset[3], 80, $result[$k]);
                        }
                    }
                    $canvas = false;
                } else {
                    //$this->im->setCompression(\Imagick::COMPRESSION_JPEG);
                    $this->im->setCompressionQuality(90);
                    if ($this->imageType == 'gif') {
                        $this->im->writeImages($result[$k], true);
                    } else {
                        $this->im->writeImage($result[$k]);
                    }
                }
            }
        }
        return $result;
    }

    public function crop($set, $position)
    {
        if (!$this->im) {
            return false;
        }
        $this->imageType();
        $result = array();
        if (!is_array($set)) {
            $set = explode(',', $set);
        }
        $source_x   = $this->im->getImageWidth();
        $source_y   = $this->im->getImageHeight();
        foreach ($set as $k => $v) {
            $result[$k] = $this->getDest($v . '_crop');
            if ($this->check($result[$k])) {
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
                if ($this->imageType == 'gif') {
                    $this->gif($offset[0], $offset[1], $x, $y);
                } else {  
                    $this->im->cropImage($offset[0], $offset[1], $x, $y);
                }
                
                if (isset($offset[2]) && $offset[2] == 3 && isset($offset[3]) && $offset[3] > 0) {
                    $this->im->writeImage($result[$k]);
                    $offset[3] = $offset[3] * 1024;
                    $size = abs(filesize($result[$k]));
                    if ($size > $offset[3]) {
                        $this->_compress($this->im, $offset[3], 80, $result[$k]);
                    }
                } else {
                    //$this->im->setCompression(\Imagick::COMPRESSION_JPEG); 
                    $this->im->setCompressionQuality(90);
                    if ($this->imageType == 'gif') {
                        $this->im->writeImages($result[$k], true);
                    } else {
                        $this->im->writeImage($result[$k]);
                    }
                }
            }
        }
        return $result;
    }

    public function pic($water, $position = 5, $offset = 0, $width = 0, $height = 0, $radius = 0)
    {
        if (!$this->im) {
            return false;
        }
        $this->imageType();
        $result = $this->getDest('mark');
        if ($this->check($result)) {
            if ($radius) {
                $water = $this->get_radius($water, $radius);
            } else {
                $water  = $this->get($water);
            }
            $draw = new \ImagickDraw();
            $source_x   = $this->im->getImageWidth();
            $source_y   = $this->im->getImageHeight();
            $water_x = $water->getImageWidth();
            $water_y = $water->getImageHeight();
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
                //$water->thumbnailImage($dest_x, $dest_y);
                list($x, $y) = $this->position($water_x, $water_y, $width, $height, 5, 0);
                $water->cropImage($width, $height, $x, $y);
                $xy = $this->position($source_x, $source_y, $dest_x, $dest_y, $position, $offset);
                $water_x = $dest_x;
                $water_y = $dest_y;
            } else {
                $xy = $this->position($source_x, $source_y, $water_x, $water_y, $position, $offset);
            }
            if ($xy[0] == -1) {
                # 水印平铺
                $image = new \Imagick();
                $image->newImage($source_x, $source_y, new \ImagickPixel('none'));
                $image->setImageFormat('jpg');
                $water = $image->textureImage($water);
                $width = $source_x;
                $height = $source_y;
                $xy[0] = $xy[1] = 0;
            }
            if ($xy[2] == false) {
                $this->destroy($water);
                return;
            }
            $draw->composite($water->getImageCompose(), $xy[0], $xy[1], $width, $height, $water);
            if ($this->imageType == 'gif') {
                $this->gif(0, 0, 0, 0, $draw);
            } else {
                $this->im->drawImage($draw);
            }
            $this->im->writeImage($result);
        }
        return $result;
    }

    # 文字水印
    public function txt($name, $position = 5, $offset = 0, $size = 10, $color = '', $angle = 0, $width = 0, $font = 'SIMSUN.TTC')
    {
        if (!$this->im) {
            return false;
        }
        $this->imageType();
        $result = $this->getDest('txt');
        if ($this->check($result)) {
            $autowrap = 0;
            if ($width > 0) {
                $name = $this->autowrap($autowrap, $size, $angle, $font, $name, $width);
            }
            $draw = new \ImagickDraw();
            $draw->setFont($font);
            $position = imagettfbbox($size, $angle, $font, $name);
            if ($position) {
                $source_x   = $this->im->getImageWidth();
                $source_y   = $this->im->getImageHeight();
                $water_x = $position[2] - $position[0];
                $water_y = $position[1] - $position[7];
                $xy = $this->position($source_x, $source_y, $water_x, $water_y, $position, $offset);
            }
            
            if ($size) {
                $draw->setFontSize($size);
            }
            if ($color) {
                $draw->setFillColor($color);
            }
            /*
            if ($bgcolor) {
                $draw->setTextUnderColor($bgcolor);
            }*/
            $left = $xy[0] ?? 0;
            $top = $xy[1] ?? 0;
            $draw->setGravity(\Imagick::GRAVITY_NORTHWEST); 
            if ($this->imageType == 'gif') {  
                foreach ($this->im as $frame) {
                    $frame->annotateImage($draw, $left, $top, $angle, $name);
                }
            } else {
                $this->im->annotateImage($draw, $left, $top + $autowrap, $angle, $name);
            }
            $this->_image->writeImage($result);
        }
        return $result;
    }

    protected function gif($w, $h, $x, $y, $d = false, $num = false)
    {
        $canvas = $this->get();
        $canvas->setFormat("gif");
        $this->im->coalesceImages();
        $num = $num ? $num : $this->im->getNumberImages();
        for ($i = 0; $i < $num; $i++) {
            $this->im->setImageIndex($i);
            $img = $this->get();
            $img->readImageBlob($this->im);
            if ($d != false) {
                $img->drawImage($d);
            } else {
                $img->cropImage($w, $h, $x, $y);
            }
            $canvas->addImage($img);
            $canvas->setImageDelay($img->getImageDelay());
            if($d == false) $canvas->setImagePage($w, $h, 0, 0);
            $img->destroy();
            unset($img);
        }
        $this->im->destroy();
        $this->im = $canvas;
    }

    protected function compress($canvas, $max, $num, $file)
    {
        $num = $num - 10;
        $temp = $file . '_temp_'.$num.'.jpg';
        $canvas->setCompressionQuality($num);
        $canvas->stripImage();
        $canvas->writeImage($temp);
        $size = abs(filesize($temp));
        if ($size > $max && $num > 0) {
            @unlink($temp);
            return $this->compress($canvas, $max, $num, $file);
        } else {
            $canvas->destroy();
            @copy($temp, $file);
            @unlink($temp);
        }
    }

    protected function create($w, $h, $t = 1)
    {
        $im = new \Imagick();
        if ($t == 1) {
            $transparent = new \ImagickPixel('#ffffff');
        } elseif ($t == 2) {
            $transparent = new \ImagickPixel('#transparent');
        }
        $im->newImage($w, $h, $transparent, 'jpg');
        return $im;
    }
    protected function get($im)
    {
        if ($im && strstr($im, 'http')) {
            $content = file_get_contents($im);
            $im = new \Imagick();
            $im->readImageBlob($content);
        } elseif ($im && is_file($im)) {
            $im = new \Imagick($im);
        } else {
            $im = new \Imagick();
        }
        return $im;
    }
    protected function get_radius($img = '', $radius = -1)
    {
        $image = $this->get($img);
        $image->setImageFormat('png');
        if ($radius == -1) {
            $x = $image->getImageWidth() / 2;
            $y = $image->getImageHeight() / 2;
        } else {
            $x = $image->getImageWidth() - $radius;
            $y = $image->getImageHeight() - $radius;
        }
        $image->roundCorners($x, $y);
        return $image;
    }
    protected function _destroy()
    {
        $this->im->destroy();
        $this->im = false;
    }
}