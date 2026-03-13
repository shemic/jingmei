<?php
$config = [
    'update' => [
        'field'    => [
            //'location',
            'workflow' => [
                'rules' => true,
                'type' => 'cascader',
                'option' => 'Dever::call("Work/Manage/Lib/Workflow.getList")',
            ],
        ],
        'drag' => 'sort',
    ],
];

return $config;