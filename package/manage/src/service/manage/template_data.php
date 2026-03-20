<?php
$template_id = Dever::load(\Manage\Lib\Util::class)->request('template_id');
return [
    'list' => [
        'where' => ['template_id'],
        'field'      => [
            'prompt',
            'template_cate_id',
            'type',
            //'file',
            'sort',
            'status',
            'cdate',
        ],
        'button' => [
            '新增' => ['fastadd'],
            '分类' => ['route', [
                'path' => 'service_manage/template_cate',
                'param' => [
                    'set' => ['template_id' => $template_id, 'menu' => 'service_manage/template', 'parent' => 'service_manage/template_data'],
                ],
            ]],
        ],
        'data_button' => [
            '编辑' => ['fastedit'],
        ],
        'search'    => [
            'prompt',
            'template_cate_id',
            'status',
        ],
    ],
    'update' => [
        'field'    => [
            'template_id' => 'hidden',
            'prompt' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 4],
            ],
            'template_cate_id' => [
                'rules' => true,
                'option'    => 'Dever::call("Service/Manage/Lib/Template.getCate")',
            ],
            'type' => [
                'rules' => true,
                'type' => 'radio',
            ],
            
            'file' => [
                'type' => 'upload',
                'upload' => '1',
                'project' => 'place',
                'multiple' => false,
                'style' => 'list',
            ],
        ],
    ],
];