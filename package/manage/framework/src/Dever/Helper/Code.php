<?php namespace Dever\Helper;
class Code
{
    public static function create($width = '120', $height = '40')
    {
        header("Content-type: image/png");
        $image = @imagecreate($width, $height);
        $back = imagecolorallocate($image, 255, 255, 255);
        $border = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $back);
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $border);
        for ($i = 0; $i <= 200; $i++) {
            imagesetpixel($image, rand(2, $width), rand(2, $height), imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255)));
        }
        $cal = array
            (
            ['+', '+'],
            ['-', '-'],
        );
        $index = array_rand($cal);
        $m1 = rand(1, 100);
        $m2 = rand(1, 100);
        $string = $m1 . $cal[$index][1] . $m2 . '';
        $code = '$code = ' . $m1 . $cal[$index][0] . $m2 . ';';
        eval($code);
        $length = strlen($string);
        for ($i = 0; $i < $length; $i++) {
            $bg_color = imagecolorallocate($image, rand(0, 255), rand(0, 128), rand(0, 255));
            $x = floor($width / $length) * $i;
            $y = rand(0, $height - 15);
            imagechar($image, rand(5, 5), $x + 5, $y, $string[$i], $bg_color);
        }
        imagepng($image);
        imagedestroy($image);
        return $code;
    }
}