<?php
session_start();
require_once dirname(__DIR__) . '/core/bootstrap.php';
Auth::logout();
redirect(BASE_URL . '/admin/login.php');
