<?php
// member/logout.php
session_start();
session_unset();
session_destroy();
header("Location: ../index"); // Babalik sa unified login page
exit();
?>