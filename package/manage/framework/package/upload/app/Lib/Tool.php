<?php namespace Upload\Lib;
use Dever;
class Tool
{
    private $config = [
        1 => 'Local',
        2 => 'Qiniu',
        3 => 'Oss',
    ];

    public function get($config)
    {
        $save = $this->config[$config['type']] ?? false;
        if (!$save) {
            Dever::error('存储位置错误');
        }
        $tool = 'Upload\\Lib\\Tool\\' . $save;
        $tool = Dever::load($tool);
        $tool->init($config);
        return $tool;
    }
}