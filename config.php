<?php
return [
    'database' => [
        'name' => 'TssakharliaProj',
        'username' => 'assellalou',
        'password' => 'assellalou',
        'connection' => 'mysql:host=127.0.0.1',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
        ]
    ],
    'jwt_secret_key' => base64_encode("YAMohammedAssallalouIEtoMoySekretnyyKlyuch99Mar5"),
    'support' => [
        'support@devwave.com'
    ],
    'author' => "WyduYW1lJz0+J0FTU0VMTEFMT1UgTW9oYW1tZWQnLCdmaW5kTWUnPT5bJyBodHRwczovL2dpdGh1Yi5jb20vYXNzZWxsYWxvdScsICdodHRwczovL3d3dy5mYWNlYm9vay5jb20vYXNzZWxsYWxvdScsICdodHRwczovL3R3aXR0ZXIuY29tL21hc3NlbGxhbG91ICcsICdhc3NlbGxhbHVAZ21haWwuY29tJywgJysyMTI2Nzg0NzkwMTgnXV0="
];
