<?php
require_once 'includes/auth.php';

fazerLogout();
header('Location: index.php');
exit;
?>