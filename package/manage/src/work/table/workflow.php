<?php
return [
    'name' => '工作流表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'uid' => [
            'name' => '用户ID',
            'type' => 'bigint',
            'default' => 0,
        ],
        'cate_id' => [
            'name' => '分类',
            'type' => 'bigint',
            'value' => 'work/cate',   
        ],
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'info' => [
            'name'      => '描述',
            'type'      => 'varchar(500)',
        ],
        'out_type' => [
            'name'      => '产出类型',
            'type'      => 'varchar(20)',
            'default'   => 'text',
            'value'     => Dever::config('setting')['content'],
        ],

        'shenzhu_role' => [
            'name' => '助理角色',
            'type' => 'varchar(200)',
        ],

        'icon' => [
            'name'      => '图标',
            'type'      => 'varchar(32)',
        ],

        'is_text' => [
            'name'      => '开启输入框',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '开启',
                2 => '关闭',
            ],
        ],

        'tip' => [
            'name'      => '输入框提示',
            'type'      => 'varchar(200)',
        ],

        'is_upload' => [
            'name'      => '开启上传框',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '开启',
                2 => '关闭',
            ],
        ],

        'upload_type' => [
            'name'      => '上传类型',
            'type'      => 'varchar(300)',
            'default'   => 'image',
            'value' => [
                'image' => '图片',
                'audio' => '音频',
                'video' => '视频',
                'text' => '文本文件',
                'pdf' => 'pdf文件',
                'office' => 'office文件',
            ],
        ],
        
        'entry' => [
            'name'      => '入口节点',
            'type'      => 'varchar(64)',
            'default'   => 'main',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
        'status' => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '正常',
                2 => '封禁',
            ],
        ],
    ],
    'index' => [
        'code' => 'code.unique',
        'search' => 'cate_id,sort',
    ],
];