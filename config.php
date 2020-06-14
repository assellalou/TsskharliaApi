<?php
return [
    'database' => [
        'name' => 'TssakharliaProj',
        'username' => 'assellalou',
        'password' => 'assellalou',
        'connection' => 'mysql:host=127.0.0.1',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    ],
    'jwt_secret_key' => base64_encode("YAMohammedAssallalouIEtoMoySekretnyyKlyuch99Mar5"),
    'support' => [
        'support@devwave.com'
    ]
];
