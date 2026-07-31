<?php
require_once '../../includes/csrf.php'; require_once 'auth.php'; logout(); header('Location: login.php'); exit;
