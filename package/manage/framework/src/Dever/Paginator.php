<?php namespace Dever;
use Dever;
class Paginator
{
    protected $status = false;
    protected $num;
    protected $rows;
    protected $current;
    protected $html = '';
    protected $link;
    public function init($num, $page, $rows)
    {
        $this->num = $num;
        $this->rows = $rows;
        $this->current = Dever::input('pg', '', '', $page);
        $offset = $this->num * ($this->current-1);
        if ($offset >= 10000) {
            return [$offset, $this->num];
        }
        return $offset . ',' . $this->num;
    }
    public function status($status)
    {
        $this->status = !$status;
    }
    public function get($template = '', $maxpage = 10, $link = '')
    {
        if ($this->rows) {
            if ($template == 'total') {
                return ($this->rows)();
            }
            $page['num'] = $this->num;
            $page['status'] = $this->status;
            $page['current'] = $this->current;
            if ($template) {
                $page['rows'] = $this->rows = ($this->rows)();
                $page['next'] = $page['total'] = ceil($this->rows / $this->num);
                if ($this->current < $page['total']) {
                    $page['next'] = $this->current+1;
                } else {
                    $this->current = $page['total'];
                }
                $page['prev'] = $this->current-1;
                if ($page['prev'] < 1) {
                    $page['prev'] = 1;
                }
                $this->handle($page, $maxpage);
                $page['html'] = $this->html($page, $template, $maxpage, $link);
            }
            return $page;
        }
        return false;
    }
    protected function handle(&$page, $maxpage)
    {
        if ($page['total'] <= $maxpage) {
            $page['start'] = 1;
            $page['end'] = $page['total'];
        } else {
            $tpage = intval($maxpage / 2);
            if ($page['current'] < $tpage) {
                $page['start'] = 1;
            } elseif ($page['current'] <= ($page['total'] - $maxpage)) {
                $page['start'] = $page['current'] - $tpage;
            } elseif ($page['current'] > $page['total'] - $maxpage && $page['current'] <= $page['total'] - $tpage) {
                $page['start'] = $page['current'] - $tpage;
            } elseif ($page['current'] > $page['total'] - $tpage) {
                $page['start'] = $page['total'] - $maxpage + 1;
            }
            $page['end'] = $page['start'] + $maxpage - 1;
            if ($page['start'] < 1) {
                $page['end'] = $page['current'] + 1 - $page['start'];
                $page['start'] = 1;
                if (($page['end'] - $page['start']) < $maxpage) {
                    $page['end'] = $maxpage;
                }
            } elseif ($page['end'] > $page['total']) {
                $page['start'] = $page['total'] - $maxpage + 1;
                $page['end'] = $page['total'];
            }
        }
    }
    public function link($page)
    {
        if (is_string($this->link)) {
            return Dever::url($this->link, ['pg' => $page]);
        }
        return ($this->link)($page);
    }
    protected function html($page, $template, $maxpage, $link)
    {
        $file = DEVER_PROJECT_PATH . 'page/' . $template . '.php';
        if (is_file($file)) {
            $html = include $file;
            $this->link = $link;
            if (!$this->link) {
                $this->link = Dever::url(false);
                if (strpos($this->link, 'pg=') !== false) {
                    $this->link = preg_replace('/[?|&]pg=(\d+)/i', '', $this->link);
                }
            }
            if (is_array($html)) {
                $this->template($html, $page);
            } else {
                $this->html = $html;
            }
        }
        return $this->html;
    }
    protected function template($html, $page)
    {
        $this->html = '';
        if ($page['current'] > 1 && isset($html['start'])) {
            $this->html .= $this->create($html['child'], $html['start'][1], 1, $html['start'][0], $html['page'][2]);
        }
        if (isset($html['prev'])) {
            $this->html .= $this->create($html['child'], $html['prev'][1], $page['prev'], $html['prev'][0], $html['page'][2]);
        }
        $i = $page['start'];
        for ($i; $i <= $page['end']; $i++) {
            $this->html .= $this->create($html['child'], $this->getClass($i, $html['page'], $page), $i, $i, $html['page'][2]);
        }
        if (isset($html['next'])) {
            $this->html .= $this->create($html['child'], $html['next'][1], $page['next'], $html['next'][0], $html['page'][2]);
        }
        if (isset($html['end']) && $page['current'] < $page['total']) {
            $this->html .= $this->create($html['child'], $html['end'][1], $page['total'], $html['end'][0], $html['page'][2]);
        }
        if (isset($html['jump'])) {
            $click = 'onclick="var link=\'' . $this->link('dever_page') . '\';location.href=link.replace(\'dever_page\', document.getElementById(\'dever_page\').value)"';
            $this->html .= str_replace($click, $html['jump'], '{click}');
        }
        $this->html = $this->tag($html['parent'], $this->html);
    }
    protected function create($child, $class, $num, $name, $type = '')
    {
        if ($type == 'parent') {
            $child[1] = 'class="' . $class . '"';
            $class = '';
        }
        return $this->tag($child, $this->getContent($child, $class, $num, $name));
    }
    protected function tag($tag, $content)
    {
        if (!$tag) {
            return $content;
        }
        $attr = '';
        if (is_array($tag)) {
            $temp = $tag;unset($tag);
            $tag = $temp[0];
            $attr = $temp[1];
        }
        return '<' . $tag . ' ' . $attr . '>' . $content . '</' . $tag . '>';
    }
    protected function getContent($child, $class, $num, $name)
    {
        if ($child && $child[0] == 'a') {
            $child[1] = $this->attr($class, $this->link($num));
            $content = $name;
        } else {
            $content = $this->tag(array('a', $this->attr($class, $this->link($num))), $name);
        }
        return $content;
    }
    protected function attr($class, $href)
    {
        return ' class="' . $class . '" href="' . $href . '" ';
    }
    protected function getClass($index, $html, $page)
    {
        $class = $html[0];
        if ($index == $page['current']) {
            if (isset($html[3]) && $html[3] == true) {
                $class = $html[1];
            } else {
                if ($class) {
                    $class .= ' ';
                }
                $class .= $html[1];
            }
        }
        return $class;
    }
}