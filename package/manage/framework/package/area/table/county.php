<?php
return [
	'name' => '区县',
	'order' => 'sort asc,id asc',
	'struct' => [
		'name'		=> [
			'type' 		=> 'varchar(150)',
			'name' 		=> '区县名称',
		],

		'pinyin'		=> [
			'type' 		=> 'varchar(300)',
			'name' 		=> '拼音',
		],

		'pinyin_first'		=> [
			'type' 		=> 'varchar(30)',
			'name' 		=> '拼音首字母',
		],

		'area'       => [
            'type'      => 'varchar(500)',
            'name'      => '所在城市',
        ],

		'province_id'		=> [
			'type' 		=> 'int(11)',
			'name' 		=> '省份',
		],

		'city_id'		=> [
			'type' 		=> 'int(11)',
			'name' 		=> '城市',
		],

		'type'        => [
            'type'      => 'tinyint(1)',
            'name'      => '区县类型',
            'default'	=> 1,
            'value'		=> [
				1 => '城区',
				2 => '郊区',
				3 => '县城',
				4 => '经济技术开发区',
				5 => '县级市',
			],
        ],

        'level'        => [
            'type'      => 'tinyint(1)',
            'name'      => '区县级别',
            'default'	=> 1,
            'value'		=> [
				1 => '核心区',
				2 => '普通区',
				3 => '边缘区',
			],
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
];
