<?php
/**
 * Copy this file to config/config.php and fill in your own values.
 * config/config.php is ignored by git so passwords never get committed.
 *
 * Database is MySQL / MariaDB. Create the database first (phpMyAdmin, HeidiSQL,
 * or `CREATE DATABASE green_office CHARACTER SET utf8mb4;` via the mysql CLI).
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'green_office',
        'user' => 'root',
        'pass' => '',
    ],

    // URL path of the public/ folder.
    // null      = detect automatically (works for XAMPP/Laragon: http://localhost/green-office/public/)
    // ''        = public/ is the web server's document root (recommended when you control the vhost)
    // '/public' = whole project sits inside the web root as one folder, e.g. shared hosting where you
    //             can only FTP into htdocs/ and can't repoint the document root (see root .htaccess)
    'base_url' => null,

    // Show PHP errors on screen. Set to false on the live server.
    'debug' => true,

    'timezone' => 'Asia/Bangkok',

    // Largest upload allowed for documents and images (MB).
    // PHP's own upload_max_filesize / post_max_size must also allow this.
    'upload_max_mb' => 10,
];
