<?php
return [
    'menu' => [
        'image' => [
            'parent' => 'set',
            'name' => '图片处理',
            'icon' => 'image-line',
            'sort' => '120',
        ],
        'thumb' => [
            'parent'    => 'image',
            'name'      => '缩略图',
            'icon'      => 'picture-in-picture-line',
            'sort'      => '1',
        ],
        'crop' => [
            'parent'    => 'image',
            'name'      => '裁剪图',
            'icon'      => 'crop-line',
            'sort'      => '2',
        ],
        'water_pic' => [
            'parent'    => 'image',
            'name'      => '水印图',
            'icon'      => 'water-flash-line',
            'sort'      => '3',
        ],
        'water_txt' => [
            'parent'    => 'image',
            'name'      => '水印文字',
            'icon'      => 'text-direction-l',
            'sort'      => '4',
        ],
    ],
];