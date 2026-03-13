<?php namespace Manage\Lib;
use Dever;
# 配置一些常用的数据
class Data
{
    public function getShortcuts($type)
    {
        if (strstr($type, 'range')) {
            return [
                [
                    'text' => '今天',
                    'func' => 'return [new Date(new Date().setHours(0,0,0,0)), new Date()]',
                ],
                [
                    'text' => '昨天',
                    'func' => 'const now = new Date(); const start = new Date(now.setDate(now.getDate() - 1)); start.setHours(0,0,0,0); const end = new Date(start); end.setHours(23,59,59,999); return [start, end]',
                ],
                [
                    'text' => '本周',
                    'func' => 'const now = new Date(); const day = now.getDay() || 7; const start = new Date(now); start.setDate(now.getDate() - day + 1); start.setHours(0,0,0,0); return [start, new Date()]',
                ],
                [
                    'text' => '上周',
                    'func' => 'const now = new Date(); const day = now.getDay() || 7; const end = new Date(now); end.setDate(now.getDate() - day); end.setHours(23,59,59,999); const start = new Date(end); start.setDate(end.getDate() - 6); start.setHours(0,0,0,0); return [start, end]',
                ],
                [
                    'text' => '本月',
                    'func' => 'const now = new Date(); const start = new Date(now.getFullYear(), now.getMonth(), 1); return [start, new Date()]',
                ],
                [
                    'text' => '上月',
                    'func' => 'const now = new Date(); const start = new Date(now.getFullYear(), now.getMonth() - 1, 1); const end = new Date(now.getFullYear(), now.getMonth(), 0); end.setHours(23,59,59,999); return [start, end]',
                ],
                [
                    'text' => '最近7天',
                    'func' => 'const start = new Date(); start.setDate(start.getDate() - 6); start.setHours(0,0,0,0); return [start, new Date()]',
                ],
                [
                    'text' => '最近30天',
                    'func' => 'const start = new Date(); start.setDate(start.getDate() - 29); start.setHours(0,0,0,0); return [start, new Date()]',
                ],
                [
                    'text' => '最近90天',
                    'func' => 'const start = new Date(); start.setDate(start.getDate() - 89); start.setHours(0,0,0,0); return [start, new Date()]',
                ],
                [
                    'text' => '最近1年',
                    'func' => 'const start = new Date(); start.setFullYear(start.getFullYear() - 1); start.setDate(start.getDate() + 1); start.setHours(0,0,0,0); return [start, new Date()]',
                ],
                [
                    'text' => '本年',
                    'func' => 'const now = new Date(); const start = new Date(now.getFullYear(), 0, 1); return [start, new Date()]',
                ],
                [
                    'text' => '去年',
                    'func' => 'const now = new Date(); const start = new Date(now.getFullYear() - 1, 0, 1); const end = new Date(now.getFullYear() - 1, 11, 31, 23, 59, 59, 999); return [start, end]',
                ],
            ];
        } else {
            return [
                // --- 过去 ---
                [
                    'text' => '今天',
                    'func' => 'return new Date()',
                ],
                [
                    'text' => '昨天',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() - 1); return d',
                ],
                [
                    'text' => '前天',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() - 2); return d',
                ],
                [
                    'text' => '三天前',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() - 3); return d',
                ],
                [
                    'text' => '五天前',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() - 5); return d',
                ],
                [
                    'text' => '一周前',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() - 7); return d',
                ],
                [
                    'text' => '一个月前',
                    'func' => 'const d = new Date(); d.setMonth(d.getMonth() - 1); return d',
                ],
                [
                    'text' => '一年前',
                    'func' => 'const d = new Date(); d.setFullYear(d.getFullYear() - 1); return d',
                ],

                // --- 未来 ---
                [
                    'text' => '明天',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() + 1); return d',
                ],
                [
                    'text' => '后天',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() + 2); return d',
                ],
                [
                    'text' => '三天后',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() + 3); return d',
                ],
                [
                    'text' => '五天后',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() + 5); return d',
                ],
                [
                    'text' => '一周后',
                    'func' => 'const d = new Date(); d.setDate(d.getDate() + 7); return d',
                ],
                [
                    'text' => '一个月后',
                    'func' => 'const d = new Date(); d.setMonth(d.getMonth() + 1); return d',
                ],
                [
                    'text' => '一年后',
                    'func' => 'const d = new Date(); d.setFullYear(d.getFullYear() + 1); return d',
                ],
            ];
        }
    }
}