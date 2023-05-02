<?php
session_start();

if (
    isset($_SESSION['state']) && !empty($_SESSION['state']) &&
    isset($_SESSION['county']) && !empty($_SESSION['county']) &&
    isset($_SESSION['municipality']) && !empty($_SESSION['municipality']) &&
    isset($_SESSION['selected']) && !empty($_SESSION['selected']) &&
    isset($_SESSION['array']) && !empty($_SESSION['array']) &&
    isset($_SESSION['error_rows']) && isset($_SESSION['success_rows'])
) {
    $error_rows = $_SESSION['error_rows'];
    if (empty($error_rows)) {
        header('location: skip.php');
    }
    $error_rows_count = count($error_rows);
} else {
    exit('Incomplete data');
}
