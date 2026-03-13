<?php
return [
    'update' => [
        'field'    => [
            'name' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
            ],
            'key' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
            ],
            'option' => [
                'type' => 'textarea',
                'autosize' => ['minRows' => 2],
            ],
            'type',
            'match' => [
                'width' => '40',
                'type' => 'switch',
                'show'  => '{match}',
                'active_value' => 1,
                'inactive_value' => 2,
            ],
        ],
        'drag' => 'sort',
    ],
];