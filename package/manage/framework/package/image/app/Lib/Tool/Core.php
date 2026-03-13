<?php namespace Image\Lib\Tool;
use Dever;
class Core
{
    protected $im;
    protected $source;
    protected $cover = false;
    public function __construct($source)
    {
        if ($source) {
            $this->source($source);
        }
    }
    public function source($source)
    {
        if (is_array($source)) {
            $this->im = $this->create($source[0], $source[1], 1);
            $this->source = $source[2] ?? false;
        } else {
            $this->im = $this->get($source);
            $this->source = $source;
        }
        return $this;
    }
    public function cover($cover)
    {
        $this->cover = $cover;
        return $this;
    }
    protected function check($file)
    {
        if ($this->cover || !file_exists($file)) {
            return true;
        }
        return false;
    }
    protected function getDest($name)
    {
        if (!$this->source) {
            $name = md5($name);
            $path = array_slice(str_split($name, 2), 0, 3);
            $dest = implode(DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR . $name . '.jpg';
            return Dever::file('image/' . $dest);
        } else {
            return $this->source . '_' . $name . '.jpg';
        }
    }
    public function getXy($set, $source_x, $source_y, $source_w, $source_h)
    {
        $offset = explode('_', $set);
        if (isset($offset[2]) && $offset[2] == 1) {
            //完全等比例
            if ($source_x > $offset[0]) {
                $dest_x = $offset[0];
                $dest_y = $offset[0]*$source_h;
            } elseif ($offset[1] > 0 && $source_y > $offset[1]) {
                $dest_x = $offset[1]*$source_w;
                $dest_y = $offset[1];
            } else {
                $dest_x = $source_x;
                $dest_y = $source_y;
            }
        } elseif (isset($offset[2]) && $offset[2] == 2) {
            //按照一定比例
            if ($offset[0] == 0 && $offset[1] > 0) {
                $dest_x = $offset[1]*$source_w;
                $dest_y = $offset[1];
            } elseif ($offset[1] > 0 && $source_x > $source_y && $source_y > $offset[1]) {
                $dest_x = $offset[1]*$source_w;
                $dest_y = $offset[1];
            } elseif ($source_y > $source_x && $source_x > $offset[0]) {
                $dest_x = $offset[0];
                $dest_y = $offset[0]*$source_h;
            } elseif ($source_y == $source_x && $offset[0] == $offset[1]) {
                $dest_x = $offset[0];
                $dest_y = $offset[1];
            } elseif ($source_x > $source_y && $source_y < $offset[1]) {
                $dest_x = $offset[1]*$source_w;
                $dest_y = $offset[1];
            } elseif($source_y > $source_x && $source_x < $offset[0]) {
                $dest_x = $offset[0];
                $dest_y = $offset[0]*$source_h;
            } elseif($source_x > $offset[0]) {
                $dest_x = $offset[0];
                $dest_y = $offset[0]*$source_h;
            } else {
                $dest_x = $source_x;
                $dest_y = $source_y;
            }
        } elseif (isset($offset[2]) && $offset[2] == 3) {
            //按照比例缩放，如有多余则留白（或黑...如果实在留不了白的话）
            $b = $offset[0]/$offset[1];
            $l = $source_x/$source_y;
            
            if ($b > $l) {
                $dest_x = $offset[1]*$source_w;
                $dest_y = $offset[1];
            } else {
                $dest_x = $offset[0];
                $dest_y = $offset[0]*$source_h;
            }
        } elseif (isset($offset[2]) && $offset[2] == 4) {
            //按照一定比例
            if ($offset[0] == 0 && $offset[1] > 0) {
                $dest_x = $offset[1]*$source_w;
                $dest_y = $offset[1];
            } elseif($offset[1] > 0 && $source_x > $source_y && $source_y >= $offset[1]) {
                $dest_x = $offset[1]*$source_w;
                $dest_y = $offset[1];
            } elseif ($source_y > $source_x && $source_x >= $offset[0]) {
                $dest_x = $offset[0];
                $dest_y = $offset[0]*$source_h;
            } elseif ($source_y == $source_x && $offset[0] < $offset[1]) {
                $dest_x = $offset[1]*$source_w;
                $dest_y = $offset[1];
            } elseif ($source_y == $source_x && $offset[0] > $offset[1]) {
                $dest_x = $offset[0];
                $dest_y = $offset[0]*$source_h;
            } elseif ($source_y == $source_x && $offset[0] == $offset[1]) {
                $dest_x = $offset[0];
                $dest_y = $offset[1];
            } elseif ($source_x > $source_y && $source_y < $offset[1]) {
                $dest_x = $offset[1]*$source_w;
                $dest_y = $offset[1];
            } elseif ($source_y > $source_x && $source_x < $offset[0]) {
                $dest_x = $offset[0];
                $dest_y = $offset[0]*$source_h;
            } else {
                $dest_x = $source_x;
                $dest_y = $source_y;
            }
        } else {
            //直接放大和缩小
            $dest_x = $offset[0];
            $dest_y = $offset[1];
        }
        return array($dest_x, $dest_y, $offset);
    }
    public function position($source_x, $source_y, $dest_x, $dest_y, $position, $offset = 0)
    {
        $left = 0;
        $top = 0;
        $state = 1;
        if ($position && is_array($position)) {
            $left = $position[0];
            $top = $position[1];
        } elseif ($position) {
            switch ($position) {
                case 1:
                    //左上
                    break;
                case 2:
                    //左下
                    $top = $source_y - $dest_y;
                    break;
                case 3:
                    //右上
                    $left = $source_x - $dest_x;
                    break;
                case 4:
                    //右下
                    $left = $source_x - $dest_x;
                    $top = $source_y - $dest_y;
                    break;
                case 5:
                    //中间
                    $left = $source_x/2 - $dest_x/2;
                    $top = $source_y/2 - $dest_y/2;
                    break;
                case 6:
                    //上中
                    $left = $source_x/2 - $dest_x/2;
                    break;
                case 7:
                    //下中
                    $left = $source_x/2 - $dest_x/2;
                    $top = $source_y - $dest_y;
                    break;
                case 8:
                    //左中
                    $top = $source_y/2 - $dest_y/2;
                    break;
                case 9:
                    //右中
                    $left = $source_x - $dest_x;
                    $top = $source_y/2 - $dest_y/2;
                    break;
                case 10:
                    //平铺
                    $left = -1;
                    $top = -1;
                    break;
                default :
                    $state = false;
                    break;
            }
        }
        if ($offset && is_array($offset)) {
            $left = $left + $offset[0];
            $top = $top + $offset[1];
        } else {
            $left = $left + $offset;
            $top = $top + $offset;
        }
        return array($left, $top, $state);
    }

    public function autowrap(&$autowrap, $fontsize, $angle, $fontface, $string, $width)
    {
        // 这几个变量分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
        $content = "";
        // 将字符串拆分成一个个单字 保存到数组 letter 中
        for ($i=0;$i<mb_strlen($string);$i++) {
            $letter[] = mb_substr($string, $i, 1);
        }
        foreach ($letter as $l) {
            $teststr = $content." ".$l;
            $testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
            // 判断拼接后的字符串是否超过预设的宽度
            if (($testbox[2] > $width) && ($content !== "")) {
                $content .= "\n";
                $autowrap += $fontsize;
            }
            $content .= $l;
        }
        return $content;
    }
}