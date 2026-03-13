<?php
# 自定义页面，还缺分页、按钮等，这里也支持定义'diy' => 'manage/test.diy',参考view

$config['layout'] = [
    [
        'search' => 24,
    ],
    [
        'data1' => 6,//这里也支持不同屏幕的,用数组[xs,sm,md,lg,xl]
        'data2' => 6,
        'data3' => 6,
        'data4' => 6,
    ],
    [
        'data5' => 6,
        'data6' => 6,
        'data7' => 6,
        'data8' => 6,
    ],
    [
        'data9' => 24,
    ],
    [
        'data10' => 4,
        'data11' => 4,
        'data12' => 4,
        'data13' => 4,
        'data14' => 4,
        'data15' => 4,
    ],
];

# 搜索比较特殊，需要定义source，会自动生成搜索项
$config['source'] = 'manage/admin';
$config['search'] = [
    'cdate',
];
# 数据来源
$config['data'] = 'Manage/Lib/Test.getDiy';


$config['data1'] = [

    # 趋势
    'type' => 'trend',
    'name' => '趋势数据',
    'icon' => 'album-line',
    'number' => [
        # 展示的值
        'value' => 1000,
        # 从零开始
        'start' => 0,
        # 前缀
        'prefix' => '￥',
        # 后缀
        'suffix' => '',
        # 间隔符
        'separator' => '',
        # 时间间隔
        'duration' => 8000,
    ],
    'bottom' => [
        'left' => '自上周以来',
        'right' => '10%',
        'right_icon' => 'arrow-up-line',
        'right_type' => 'success',
    ],
];

$config['data2'] = [

    # 数据
    'type' => 'data',
    'name' => '普通数据',
    'icon' => 'album-line',
    'number' => [
        # 展示的值
        'value' => 1000,
        # 从零开始
        'start' => 0,
        # 前缀
        'prefix' => '￥',
        # 后缀
        'suffix' => '',
        # 间隔符
        'separator' => '',
        # 时间间隔
        'duration' => 8000,
    ],
    # 渐变背景颜色 浅色
    'bg1' => '#e4ecff',
    # 渐变背景颜色 深色
    'bg2' => '#4d7cfe',
];

$config['data3'] = [

    'type' => 'list',
    'name' => '列表',
    'content' => [
        ['name'=>'标题', 'content'=>'内容'],
        ['name'=>'标题', 'content'=>'内容'],
        ['name'=>'标题', 'content'=>'内容'],
    ],
];

$config['data4'] = [

    'type' => 'info',
    'name' => '信息',
    'info' => '描述',
    'content' => [
        ['name'=>'标题', 'content'=>'内容'],
        ['name'=>'标题', 'content'=>'内容'],
        ['name'=>'标题', 'content'=>'内容'],
    ],
];

$config['data5'] = [

    'type' => 'table',
    'name' => '表格',
    # 比view页面多出了header和footer
    # 头部信息
    'header' => [
        'left' => '左侧标题',
        'left_icon' => 'alert-line',
        'left_tips' => '提示',
        'right' => '右侧标题',
        # 右侧样式
        'right_type' => 'success',
    ],
    
    # 尾部信息，同头部信息
    'footer' => [
        'left' => '左侧',
        'right' => '右侧',
        # 右侧样式
        'right_type' => 'success',
    ],

    # 中间内容设置
    'border' => false,
    'height' => 'auto',

    'head' => array
    (
        [
            'key' => 'name',
            'name' => '姓名',
            'fixed' => 'fixed',
        ],
        [
            'key' => 'desc',
            'name' => '描述',
            'fixed' => 'fixed',
        ],
    ),
    'button' => array
    (
        array
        (
            'name' => '编辑',
            'type' => 'fastadd',
            'path' => 'source_manage/help',
        ),
    ),
];

$config['data6'] = [

    # 类型
    'type' => 'stat',
    'name' => '数据展示',
    # 比view页面多出了header和footer
    # 头部信息
    'header' => [
        'left' => '趋势',
    ],
    'content' => array
    (
        [
            # 一共24
            'span' => 6,
            'name' => '测试',
            'value' => 1000,
        ],
        [
            'span' => 6,
            'name' => '测试1',
            'value' => 1000,
        ],
        [
            'span' => 6,
            'name' => '测试2',
            'value' => 1000,
        ],
        [
            'span' => 6,
            'name' => '测试2',
            'value' => 1000,
        ],
    ),
];

$config['data7'] = [
    # 比view页面多出了header和footer
    # 头部信息
    'header' => [
        'left' => '时间线',
    ],

    'name' => '时间线',
    'type' => 'timeline',
    'content' => array
    (
        [
            'time' => '2020-10-11',
            'name' => '测试',
            'color' => '#0bbd87',
            'size' => 'large',
            'type' => 'primary',
            'hollow' => true,
        ],
        [
            'time' => '2020-10-11',
            'name' => '测试',
        ],
        [
            'time' => '2020-10-11',
            'name' => '测试',
        ],
        [
            'time' => '2020-10-11',
            'name' => '测试',
        ],
    ),
];
$config['data8'] = [

    # 类型
    'type' => 'chart',
    'name' => '图表',

    # 比view页面多出了header和footer
    # 头部信息
    'header' => [
        'left' => '趋势2',
    ],

    # 中间内容设置 这里和manage/test.view一致
    'height' => '300',

    # echarts配置
    'initOptions' => [
        'renderer' => 'svg',
    ],
    'option' => [
        'tooltip' => [
            'trigger' => 'item',
        ],
        'series' => [
            [
                'name' => '访问来源',
                'type' => 'pie',
                'radius' => ['50%', '70%'],
                'itemStyle' => [
                    'borderRadius' => 10,
                    'borderColor' => '#fff',
                    'borderWidth' => 2,
                ],
                'emphasis' => [
                    'label' => [
                        'show' => true,
                    ],
                ],
                'data' => [
                    ['value' => 1048, 'name' => '搜索引擎'],
                    ['value' => 735,  'name' => '直接访问'],
                    ['value' => 580,  'name' => '邮件营销'],
                    ['value' => 484,  'name' => '联盟广告'],
                    ['value' => 300,  'name' => '视频广告'],
                ],
            ]
        ]
    ]
];

$config['data9'] = [
    'type' => 'tip',
    'name' => '提示',
    'content' => '以下为显示内容',
];

$config['data10'] = [
    'type' => 'text',
    'name' => '文本',
    'content' => '文本内容',
    # 样式primary success warning danger info exception
    'style' => 'primary',
];

$config['data11'] = [
    'type' => 'tag',
    'name' => '标签',
    'content' => '标签内容',
    'style' => 'warning',
];

$config['data12'] = [
    'type' => 'link',
    'name' => '链接',
    'content' => '链接内容',
    'link' => '',
];

$config['data13'] = [
    'type' => 'button',
    'name' => '按钮',
    'icon' => '',
];

$config['data14'] = [
    'type' => 'image',
    'name' => '图片',
    'content' => 'https://fuss10.elemecdn.com/e/5d/4a731a90594a4af544c0c25941171jpeg.jpeg',
    # 'fill', 'contain', 'cover', 'none', 'scale-down'
    'fit' => 'fill',
];

$config['data15'] = [
    'name' => '进度条',
    'type' => 'progress',
    'content' => 50,
    'style' => 'exception',
    'width' => 20,
    'inside' => true,
    # line dashboard 仪表盘 circle 圆形
    'show' => 'line',
    # 开启条纹
    'striped' => true,
    # 开启动画
    'indeterminate' => true,
];

return $config;