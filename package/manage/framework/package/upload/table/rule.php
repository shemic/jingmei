<?php
return [
    'name' => '上传规则',
    # 数据结构
    'struct' => [
        'name'      => [
            'name'      => '规则名称',
            'type'      => 'varchar(24)',
        ],

        'save_id'     => [
            'name'      => '存储位置',
            'type'      => 'int(11)',
            'default'   => '-1',
            //'value'     => 'upload/save',
            'value'     => 'Dever::call("Upload/Lib/Util.getSaveList")',
        ],

        'type'     => [
            'name'      => '类型',
            'type'      => 'varchar(30)',
            'value' => [
                1 => '图片',
                2 => '音频',
                3 => '视频',
                4 => 'office文件',
                5 => 'pdf文件',
                6 => '压缩包',
                7 => '证书',
                8 => '可执行文件',
            ],
            'control' => true,
        ],

        'chunk'      => [
            'name'      => '分片大小',
            'type'      => 'varchar(11)',
            'default'   => '5',
        ],

        'size'      => [
            'name'      => '限制大小',
            'type'      => 'varchar(11)',
            'default'   => '2',
        ],

        'limit'     => [
            'name'      => '宽高限制',
            'type'      => 'tinyint(1)',
            'value' => [
                1 => '不限制',
                2 => '高度小于宽度-横图',
                3 => '高度大于宽度-竖图',
            ],
            'default'   => '1',
        ],

        'min_width'     => [
            'name'      => '最小宽度',
            'type'      => 'varchar(11)',
            'default'   => '0',
        ],

        'min_height'     => [
            'name'      => '最小高度',
            'type'      => 'varchar(11)',
            'default'   => '0',
        ],
    ],
    'default' => [
        'field' => 'id,name,type,cdate',
        'value' => [
            '1,"图片",1,' . DEVER_TIME,
            '2,"音频",2,' . DEVER_TIME,
            '3,"视频",3,' . DEVER_TIME,
            '4,"office文件",4,' . DEVER_TIME,
            '5,"pdf文件",5,' . DEVER_TIME,
            '6,"裁剪图片",1,' . DEVER_TIME,
            '7,"用户上传图片",1,' . DEVER_TIME,
            '8,"文件","1,2,3,4,5",' . DEVER_TIME,
        ],
    ],
];
