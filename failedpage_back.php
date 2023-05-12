<?php
session_start();

if (
    isset($_SESSION["juris_id"]) &&
    isset($_SESSION['db_name']) &&
    isset($_SESSION['array']) &&
    isset($_SESSION['headers']) &&
    isset($_SESSION['extra_header']) &&
    isset($_SESSION["error_rows"]) &&
    isset($_SESSION["success_rows"]) &&
    isset($_SESSION['prop_info_site'])
) {
    $error_rows = $_SESSION['error_rows'];
    if (empty($error_rows)) {
        header('location: skip.php');
    }
    $error_rows_count = count($error_rows);
} else {
    exit('Incomplete data');
}
