<?php
// logout.php - binubura ang session para mag-logout ang user
session_start();
$_SESSION = [];
session_destroy();
header("Location: index.php");
exit;
