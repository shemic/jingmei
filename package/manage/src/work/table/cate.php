<?php
return [
    'name' => '分类表',
    'order' => 'sort asc,id asc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'type' => [
            'name'      => '类型',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '工作流',
                2 => '智能体',
                3 => '工具',
                4 => '产物类型',
            ],
        ],
        'icon' => [
            'name'      => '图标',
            'type'      => 'varchar(32)',
        ],
        'info' => [
            'name'      => '描述',
            'type'      => 'varchar(200)',
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

    'default' => [
        'field' => 'name,type,icon,info,sort,cdate',
        'value' => [
            '"工作流默认分类",1,"","",1,' . DEVER_TIME,
            '"智能体默认分类",2,"","",1,' . DEVER_TIME,
            '"工具默认分类",3,"","",1,' . DEVER_TIME,
            '"编辑",4,"paintbrush","对本内容做二次处理",1,' . DEVER_TIME,
            '"衍生",4,"layers-plus","基于本内容生成新的内容",1,' . DEVER_TIME,
        ],
        'num' => 1,
    ],
];