<?php
return [
    'menu' => [
        'upload' => [
            'parent' => 'set',
            'name' => '文件上传',
            'icon' => 'gallery-upload-line',
            'sort' => '90',
        ],
        'rule' => [
            'parent'    => 'upload',
            'name'      => '上传规则',
            'icon'      => 'gallery-upload-line',
            'sort'      => '1',
        ],
        /* 废弃，用万接
        'save' => [
            'parent'    => 'upload',
            'name'      => '存储位置',
            'icon'      => 'save-line',
            'sort'      => '2',
        ],*/
        'file' => [
            'parent'    => 'upload',
            'name'      => '文件列表',
            'icon'      => 'file-2-line',
            'sort'      => '3',
        ],
    ],
];