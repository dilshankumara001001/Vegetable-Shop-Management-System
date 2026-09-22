<?php
$DB_HOST='localhost'; $DB_USER='root'; $DB_PASS=''; $DB_NAME='vegie_pro';
$conn = @new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
if ($conn->connect_error) die('DB error: '.$conn->connect_error);
$conn->set_charset('utf8mb4');
date_default_timezone_set('Asia/Colombo');