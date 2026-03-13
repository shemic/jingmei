<?php
return [
    'name' => '文件列表',
    'partition' => 'Dever::call("Manage/Lib/Util.system", [false, true, "Upload/Lib/Manage.getFileField"])',
    # 数据结构
    'struct' => [
        'file'      => [
            'name'      => '文件路径',
            'type'      => 'varchar(300)',
        ],

        'name'      => [
            'name'      => '文件名',
            'type'      => 'varchar(50)',
        ],

        'source_name'      => [
            'name'      => '原文件名',
            'type'      => 'varchar(200)',
        ],

        'cate_id'     => [
            'name'      => '文件分类',
            'type'      => 'int(11)',
            'value'     => 'upload/cate',
            'default'   => '1',
        ],

        'save_id'     => [
            'name'      => '存储位置',
            'type'      => 'int(11)',
            'value'     => 'upload/save',
        ],

        'rule_id'     => [
            'name'      => '上传规则',
            'type'      => 'int(11)',
            'value'     => 'upload/rule',
        ],

        'group_id'     => [
            'name'      => '文件分组',
            'type'      => 'int(11)',
            'value'     => 'upload/group',
        ],

        'user_id'     => [
            'name'      => '上传用户id',
            'type'      => 'int(11)',
        ],

        'size'      => [
            'name'      => '大小',
            'type'      => 'varchar(11)',
        ],

        'width'     => [
            'name'      => '宽度',
            'type'      => 'varchar(11)',
            'default'   => '0',
        ],

        'height'     => [
            'name'      => '高度',
            'type'      => 'varchar(11)',
            'default'   => '0',
        ],

        'status'     => [
            'name'      => '状态',
            'type'      => 'tinyint(1)',
            'default'   => '1',
            'value'     => [
                1 => '存在',
                2 => '删除',
            ],
        ],
    ],
];
