<?php
return [
    'update' => [
        'field'    => [
            'platform_id' => [
                'type' => 'hidden',
                'default' => 'Dever::call("Api/Manage/Lib/Platform.getId")',
            ],
            'key' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
            ],
            'value' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
            ],
            'type' => [
                'type' => 'cascader',
                'option'    => 'Dever::call("Api/Lib/Util.fieldType", Dever::call("Api/Manage/Lib/Platform.getId"))',
                'clearable' => true,
            ],
        ],
        'drag' => 'sort',
    ],
];