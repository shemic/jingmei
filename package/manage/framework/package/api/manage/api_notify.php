<?php
$platform_id = Dever::input('set')['platform_id'] ?? 0;
if (!$platform_id) {
    $platform_id = Dever::input('field')['platform_id'] ?? 0;
}
return [
    'update' => [
        'field'    => [
            'sign_id' => [
                'type' => 'select',
                'option'     => 'Dever::call("Api/Lib/Util.getPlatformSign", '.$platform_id.')',
                'clearable' => true,
                'default' => '',
            ],
            'sign_arg' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
            ],
            'success' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
            ],
            'error' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
            ],
        ],
    ],
];