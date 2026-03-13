<?php
return [
    'name' => '工作流输入项表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'workflow_id'       => [
            'type'      => 'bigint',
            'name'      => '工作流ID',
            'value'     => 'work/workflow',
        ],
        'workflow_nodes_id'       => [
            'type'      => 'bigint',
            'name'      => '工作流节点',
            'value'     => 'work/workflow_nodes',
        ],
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],

        'icon' => [
            'name'      => '图标',
            'type'      => 'varchar(32)',
        ],

        'type' => [
            'name'      => '类型',
            'type'      => 'varchar(20)',
            'default'   => 'radio',
            'value'     => [
                //'text' => '文本输入',
                //'file' => '文件上传',
                'radio' => '单项选择',
                'select' => '下拉选择',
                'list' => '列表选择',
                'cate' => '分类选择',
            ],
        ],

        'source' => [
            'name'      => '数据来源',
            'type'      => 'varchar(500)',
        ],

        'tip_title' => [
            'name'      => '提示标题',
            'type'      => 'varchar(32)',
        ],

        'tip_pic' => [
            'name'      => '提示图片',
            'type'      => 'varchar(150)',
        ],

        'tip_desc' => [
            'name'      => '提示描述',
            'type'      => 'varchar(300)',
        ],

        'search' => [
            'name'      => '搜索项',
            'type'      => 'tinyint(1)',
            'default'   => 2,
            'value'     => [
                1 => '是',
                2 => '否',
            ],
        ],

        'must' => [
            'name'      => '必选项',
            'type'      => 'tinyint(1)',
            'default'   => 2,
            'value'     => [
                1 => '是',
                2 => '否',
            ],
        ],

        'default' => [
            'name'      => '默认值',
            'type'      => 'varchar(100)',
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
                2 => '删除',
            ],
        ],
    ],
    'index' => [
        'workflow_id' => 'workflow_id',
        'search' => 'search,type',
    ],
];