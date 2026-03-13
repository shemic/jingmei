<?php
return [
    'name' => '文件引用位置',
    'partition' => 'Dever::call("Manage/Lib/Util.system", [false, true, "Upload/Lib/Manage.getFileField"])',
    # 数据结构
    'struct' => [
        'file_id'     => [
            'name'      => '文件',
            'type'      => 'int(11)',
        ],

        'table'     => [
            'name'      => '表名',
            'type'      => 'varchar(100)',
        ],

        'table_id'     => [
            'name'      => '表id',
            'type'      => 'int(11)',
        ],
    ],
];
