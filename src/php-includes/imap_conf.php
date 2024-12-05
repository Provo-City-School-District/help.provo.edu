<?php
return [
    'accounts' => [
        'dev' => [
            'host' => 'imap.gmail.com',
            'port' => 993,
            'protocol' => 'imap',
            'encryption' => 'ssl',
            'validate_cert' => true,
            'username' => getenv('GMAIL_USER'),
            'password' => getenv('GMAIL_PASSWORD'),
            'authentication' => null
        ]
    ]
];