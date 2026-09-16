<?php

require dirname(__DIR__) . '/includes/bootstrap.php';

header('Location: ' . app_url(Auth::check() ? 'dashboard.php' : 'login.php'));
exit;
