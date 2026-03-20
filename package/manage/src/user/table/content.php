<?php
return [
    'name' => '项目内容表',
    'order' => 'sort desc,id desc',
    'struct' => [
        'name' => [
            'name'      => '名称',
            'type'      => 'varchar(32)',
        ],
        'code' => [
            'name'      => '标识',
            'type'      => 'varchar(64)',
        ],
        'uid' => [
            'name' => '用户ID',
            'type' => 'bigint',
        ],
        'project_id' => [
            'name'      => '项目ID',
            'type'      => 'bigint',
            'value'     => 'user/project',
        ],
        'app_id' => [
            'name'      => '应用ID',
            'type'      => 'bigint',
            'value'     => 'service/app',
        ],
        'content_version_id' => [
            'name'      => '内容版本ID',
            'type'      => 'bigint',
            'value'     => 'user/content_version',
        ],
        'type' => [
            'name'      => '类型',
            'type'      => 'varchar(20)',
            'default'   => 'text',
            'value'     => Dever::config('setting')['content'],
        ],
        'search' => [
            'name'      => '筛选项',
            'type'      => 'jsonb',
        ],
        'sort' => [
            'name'      => '排序',
            'type'      => 'int(11)',
            'default'   => '1',
        ],
        'deleted' => [
            'name'      => '删除状态',
            'type'      => 'tinyint(1)',
            'default'   => 1,
            'value'     => [
                1 => '正常',
                2 => '已删除',
            ],
        ],
        'udate' => [
            'name'      => '更新内容时间',
            'type'      => 'bigint',
        ],
    ],
    'index' => [
        'code' => 'code.unique',
        'project_id' => 'project_id,app_id,deleted,sort',
        'search' => 'search',
    ],
];