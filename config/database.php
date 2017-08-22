<?php
// return [
    
//     'default' => 'event',
//     'connections' => [
//         'business' => [
//             'driver'    => 'mysql',
//             'host'      => 'localhost',
//             'database'  => 'ren',
//             'username'  => 'root',
//             'password'  => '123456',
//             'charset'   => 'utf8',
//             'collation' => 'utf8_unicode_ci',
//             'prefix'    => '',
//             'strict'    => false,
//         ],
//         'recommendation' => [
//             'driver'    => 'mysql',
//             'host'      => 'localhost',
//             'database'  => 'pio',
//             'username'  => 'root',
//             'password'  => '123456',
//             'charset'   => 'utf8',
//             'collation' => 'utf8_unicode_ci',
//             'prefix'    => '',
//             'strict'    => false,
//         ],
//     ],
// ];

return [
    
    'default' => 'event',
    'connections' => [
        'business' => [
            'driver'    => 'mysql',
            'host'      => '103.23.22.215',
            'port'      => '43306',
            'database'  => 'ren',
            'username'  => 'priority',
            'password'  => 'm18Baj6^',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],
        'recommendation' => [
            'driver'    => 'mysql',
            'host'      => '103.23.22.215',
            'port'      => '43306',
            'database'  => 'pio',
            'username'  => 'priority',
            'password'  => 'm18Baj6^',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],
    ],
];