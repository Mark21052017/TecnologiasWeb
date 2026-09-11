<?php

require dirname(__DIR__) . '/app/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
require dirname(__DIR__) . '/app/Views/dashboard.php';
