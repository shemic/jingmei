<?php namespace Dever;
use Dever;
class Project
{
    protected $content = [];
    public function init()
    {
        $file = Dever::get(File::class)->get('app.php');
        if (!$this->content) {
            if (is_file($file)) {
                require $file;
                $this->content = $project;
            }
            if (isset(Dever::config('setting')['app'])) {
                $this->content = array_merge($this->content, Dever::config('setting')['app']);
            }
        }
        return $file;
    }
    public function register()
    {
        $file = $this->init();
        if (empty($this->content[DEVER_APP_NAME])) {
            $host = Dever::get(Dever\Route::class)->server['app_host'];
            $name = strtolower(DEVER_APP_NAME);
            if (strpos($host, '/src/' . $name)) {
                $host = explode('/src/' . $name, $host)[0] . '/';
            } elseif (strpos($host, '/package/' . $name)) {
                $host = explode('/package/' . $name, $host)[0] . '/';
            }
            $this->write($host, 'package');
            //$this->write($host, 'src');
            //$this->write($host, 'package');$this->write($host, 'src');$this->write($host, 'app');
            $this->content[DEVER_APP_NAME]['url'] = Dever::get(Dever\Route::class)->server['app_host'];
            if (empty($this->content[DEVER_APP_NAME]['path'])) {
                $this->content[DEVER_APP_NAME]['path'] = DEVER_APP_PATH;
            }
            if (isset($this->content['Manage'])) {
                $manage = $this->content['Manage'];
                unset($this->content['Manage']);
                $this->content = array_merge(['Manage' => $manage], $this->content);
            }
            $this->content($file);
            if (isset($this->content['Manage'])) {
                Dever::load(\Manage\Lib\Menu::class)->init();
            }
        }
        if (empty($this->content[DEVER_APP_NAME]['lang'])) {
            $this->content[DEVER_APP_NAME]['lang'] = DEVER_APP_LANG;
            $this->content($file);
        }

    }
    public function write($host, $name)
    {
        $dir = DEVER_PROJECT_PATH . $name . '/';
        if (is_dir($dir)) {
            $data = scandir($dir);
            foreach ($data as $v) {
                $n = ucfirst($v);
                if (empty($this->content[$n]) && is_dir($dir . '/' . $v) && $v !== '.' && $v !== '..') {
                    $p = $v;
                    $k = $name . '/' . $v . '/';
                    if ($v == 'manage') {
                        $p = 'manage/api';
                    }
                    if (is_file($dir . $p . '/index.php')) {
                        $this->content[$n] = [];
                        if (strstr($name, 'package')) {
                            $this->content[$n]['path'] = DEVER_PATH . $k;
                            if ($v == 'manage') {
                                $k .= 'api/';
                            }
                            $this->content[$n]['setup'] = DEVER_PROJECT_PATH . $k;
                        } else {
                            $this->content[$n]['path'] = DEVER_PROJECT_PATH . $k;
                        }
                        $this->content[$n]['url'] = $host . $k;
                    } else {
                        $this->write($host, $name . '/' . $v);
                    }
                }
            }
        }
    }
    public function content($file)
    {
        file_put_contents($file, '<?php $project = ' . var_export($this->content, true) . ';');
    }
    public function read()
    {
        return $this->content;
    }
    public function load($app, $error = true)
    {
        if (ctype_lower($app)) {
            if (strpos($app, '_')) {
                $app = str_replace(' ', '', ucwords(str_replace('_', ' ', $app)));
            } else {
                $app = ucfirst($app);
            }
        }
        if (isset($this->content[$app])) {
            return $this->content[$app];
        }
        if ($error) {
            Dever::error('app not exists:' . $app);
        }
        return false;
    }
}