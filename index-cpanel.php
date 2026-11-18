<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

/**
 * Simple Working Index
 * Copy this EXACTLY to public_html/index.php
 */
error_reporting(0);
ini_set('display_errors', 0);

$projectPath = dirname(__DIR__).'/demo.westparkapartments.com.ng';

require $projectPath.'/vendor/autoload.php';

$app = require $projectPath.'/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    Request::capture()
);

$response->send();

$kernel->terminate(
    Request::capture(),
    $response
);
