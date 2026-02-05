<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = new Dotenv();
    $dotenv->usePutenv(true);
    $dotenv->load(__DIR__ . '/../.env');
}
