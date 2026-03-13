<?php
return [
    'name' => '集团组织',
    'partition' => 'Dever::load("Manage/Util")->system()',
    'order' => 'sort asc',
    'struct' => [
        'name' => [
            'name'      => '组织名称',
            'type'      => 'varchar(32)',
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
                1 => '启用',
                2 => '关闭',
            ],
        ],
    ],
    'default' => [
        'field' => 'name,cdate',
        'value' => [
            '"默认组织",' . DEVER_TIME,
        ],
    ],
];