<?php
require_once 'includes/auth.php';

fazerLogout();
header('Location: login.php');
exit;
?>