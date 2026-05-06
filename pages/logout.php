<?php
require_once __DIR__ . '/../includes/functions.php';
startSession();
session_destroy();
header('Location: ' . APP_URL . '/pages/user_login.php?msg=Logged+out+successfully');
exit;
