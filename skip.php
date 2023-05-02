<?php
session_start();

$state = $county = $municipality = $selected = "";
function clean_data($txt)
{
    $txt = trim($txt);
    $txt = htmlspecialchars($txt);
    return $txt;
}


if (
    isset($_SESSION['state']) && !empty($_SESSION['state']) &&
    isset($_SESSION['county']) && !empty($_SESSION['county']) &&
    isset($_SESSION['municipality']) && !empty($_SESSION['municipality']) &&
    isset($_SESSION['selected']) && !empty($_SESSION['selected']) &&
    isset($_SESSION['array']) && !empty($_SESSION['array']) &&
    isset($_SESSION['site']) && !empty($_SESSION['site']) &&
    isset($_SESSION['error_rows']) && isset($_SESSION['success_rows'])
) {
    $error_rows = $_SESSION['error_rows'];
    $success_rows = $_SESSION['success_rows'];
    $array = (array) $_SESSION['array'];
    $count_array = count($array);
    $site = $_SESSION['site'];
    $file = $site['file'];
} else {
    header('location: datasource.php');
}


if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (isset($_POST['type']) && !empty($_POST['type'])) {
        $type = clean_data($_POST['type']);
        if ($type === "skip") {
            header('location: datasource.php');
        } else if ($type === "try again") {
            if ($success_rows === $count_array) {
                header('location: datasource.php');
            }
            //filter array
            $filter = [];
            foreach ($error_rows as $err) {
                $r_num = (int) $err['index'];
                if ($array[$r_num]) array_push($filter, $array[$r_num]);
            }

            $_SESSION['array'] = $filter;
            $_SESSION['last_index'] = -1;
            $_SESSION['success_rows'] = 0;
            $_SESSION['error_rows'] = [];
            $_SESSION['try_again'] = true;
            header('location: statusbar.php');
        }
    }
} else {
    header('location: datasource.php');
}