<?php namespace Image\Lib;
use Dever;
class Tool
{
    public function get($source = '')
    {
        if (class_exists('\Imagick')) {
            $type = 'mg';
        } else {
            $type = 'gd';
        }
        $tool = 'Image\\Lib\\Tool\\' . ucfirst($type);
        $tool = Dever::load($tool);
        return $tool;
    }
}