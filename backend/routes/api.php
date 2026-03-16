<?php

use Illuminate\Support\Facades\Route;

$domainPath = app_path('Domain');

foreach (glob($domainPath . '/*/Routes/api.php') as $routeFile) {
    require $routeFile;
}
