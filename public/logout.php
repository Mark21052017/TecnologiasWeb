<?php

require dirname(__DIR__) . '/app/bootstrap.php';

Auth::logout();
header('Location: ' . app_url('login.php'));
exit;
