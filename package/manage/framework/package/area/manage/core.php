<?php
return [
    'menu' => [
        'area' => [
            'parent' => 'set',
            'name' => '行政区域',
            'icon' => 'price-tag-2-line',
            'sort' => '80',
        ],

        'area_level' => [
            'parent'    => 'area',
            'name'      => '城市等级',
            'icon'      => 'capsule-line',
            'sort'      => '1',
        ],

        'province' => [
            'parent'    => 'area',
            'name'      => '省份',
            'icon'      => 'copper-coin-line',
            'sort'      => '2',
        ],

        'city' => [
            'parent'    => 'area',
            'name'      => '城市',
            'icon'      => 'coupon-5-line',
            'sort'      => '3',
        ],

        'county' => [
            'parent'    => 'area',
            'name'      => '区县',
            'icon'      => 'coupon-3-line',
            'sort'      => '4',
        ],

        'town' => [
            'parent'    => 'area',
            'name'      => '街镇',
            'icon'      => 'compasses-line',
            'sort'      => '5',
        ],

        /*
        'village' => [
            'parent'    => 'area',
            'name'      => '社区',
            'icon'      => 'contacts-line',
            'sort'      => '6',
        ],*/
    ],
];