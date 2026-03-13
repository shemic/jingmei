<?php
return [
    'name' => '文件分类',
    # 数据结构
    'struct' => [
        'name'      => [
            'name'      => '名称',
            'type'      => 'varchar(24)',
        ],
    ],
    'default' => [
        'field' => 'id,name,cdate',
        'value' => [
            '1,"后台上传",' . DEVER_TIME,
            '2,"用户上传",' . DEVER_TIME,
            '3,"裁剪",' . DEVER_TIME,
            '4,"采集",' . DEVER_TIME,
        ],
        'num' => 1,
    ],
];
