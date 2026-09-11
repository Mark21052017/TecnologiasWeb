<?php

require dirname(__DIR__) . '/app/bootstrap.php';

header('Location: ' . app_url(Auth::check() ? 'dashboard.php' : 'login.php'));
exit;
