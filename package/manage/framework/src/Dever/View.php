<?php namespace Dever;
use Dever;
class View
{
    public function show($file, $data, $app = '', $path = 'assets')
    {
        if (!$app) {
            $app = DEVER_APP_NAME;
        }
        $project = Dever::project($app);
        $template = $this->template();
        $path = $path . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR;
        if (strstr($project['path'], 'package')) {
            $package_path = str_replace(DEVER_PATH, '', $project['path']);
            $package_host = DEVER_PROTO . '://' . $_SERVER['HTTP_HOST'] . '/dever2/' . $package_path . $path;
        }
        $host = $project['url'] . $path;
        $compile = Dever::get(File::class)->get('compile' . DIRECTORY_SEPARATOR . $app . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR . $file . '.php');
        Dever::get(Debug::class)->add($compile, 'template');
        if (Dever::get(Debug::class)->shell) {
            return $data;
        }
        $template = $project['path'] . $path . 'template' . DIRECTORY_SEPARATOR . $file . '.html';
        if (!is_file($template)) {
            Dever::get(Output::class)->error('file not exists');
        }
        $create = true;
        if (is_file($compile) && filemtime($compile) >= filemtime($template)) {
            $create = false;
        }
        if ($create) {
            $this->create($template, $compile);
        }
        include($compile);die;
    }
    private function template()
    {
        $template = 'default';
        if (isset(Dever::config('setting')['template']['name']) && Dever::config('setting')['template']['name']) {
            $template = Dever::config('setting')['template']['name'];
            if (strpos($template, ',')) {
                $temp = explode(',', $template);
                if (\Dever\Helper\Env::mobile()) {
                    $template = $temp[1];
                } else {
                    $template = $temp[0];
                }
            }
        }
        return $template;
    }
    private function create($template, $compile)
    {
        $content = file_get_contents($template);
        if (isset(Dever::config('setting')['template']['replace'])) {
            foreach (Dever::config('setting')['template']['replace'] as $k => $v) {
                $content = str_replace($k, $v, $content);
            }
        }
        $content = str_replace('{endforeach}', '<?php endforeach;?>', $content);
        $content = str_replace('{endif}', '<?php endif;?>', $content);
        $content = preg_replace('/{foreach(.*?)}/', '<?php foreach($1):?>', $content);
        $content = preg_replace('/{if(.*?)}/', '<?php if($1):?>', $content);
        $content = preg_replace('/{\$([a-zA-Z0-9_\'\"\[\]\s]+)}/', '<?php echo \$$1; ?>', $content);
        $content = preg_replace('/{\$(.*?)([\=|\-|\-\-|\+|\+\+|\*|\/])(.*?)}/', '<?php \$$1$2$3; ?>', $content);
        $content = preg_replace('/{Dever::(.*?)}/', '<?php echo Dever::$1; ?>', $content);
        file_put_contents($compile, '<!--power by dever '.date('Y-m-d H:i:s').'-->'.$content);
    }
}