<?php

require dirname(__DIR__) . '/includes/bootstrap.php';

Auth::logout();
header('Location: ' . app_url('login.php'));
exit;
