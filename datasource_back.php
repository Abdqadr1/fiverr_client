<?php
session_start();
session_unset();
require_once("./states_data.php");

function clean_data($txt)
{
    $txt = trim($txt);
    $txt = htmlspecialchars($txt);
    return $txt;
}

function checkCSVFile($file)
{
    $csvAsArray = array_map('str_getcsv', $file);
    array_shift($csvAsArray);

    if (count($csvAsArray) < 2) {
        echo ("<script>alert('Sorry, your file has less than 2 rows')</script>");
        return false;
    }

    $filtered_array = array_filter($csvAsArray, function ($arr) {
        return count($arr) < 6;
    });

    if (!empty($filtered_array)) {
        echo ("<script>alert('Sorry, your file has some rows with less than 6 columns')</script>");
        return false;
    }

    return $csvAsArray;
}

function findSite($arr, $county)
{
    foreach ($arr as $site) {
        if (in_array(strtolower($county), array_map('strtolower', $site["counties"]))) {
            return $site;
        }
    }
    return false;
}

$state = $county = $municipality = $selected = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (
        isset($_POST['state']) && !empty(isset($_POST['state'])) &&
        isset($_POST['county']) && !empty(isset($_POST['county'])) &&
        isset($_POST['municipality']) && !empty(isset($_POST['municipality'])) &&
        isset($_FILES['csv']) && !empty(isset($_FILES['csv'])) &&
        isset($_POST['selected']) && !empty(isset($_POST['selected']))
    ) {
        $state = clean_data($_POST['state']);
        $county = clean_data($_POST['county']);
        $municipality = clean_data($_POST['municipality']);
        $selected = clean_data($_POST['selected']);
        $csv = $_FILES['csv'];
        $file_name = $csv["name"];
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $temp_name = $csv["tmp_name"];
        $site = "";

        if (!isset($states[$state])) {
            $error_message = "State is not recognized!";
        } else if (!$site = findSite($states[$state], $county)) {
            $error_message = "County does not exist in state";
        } else if ($extension !== "csv") {
            echo ("<script>alert('File is not csv format')</script>");
        } else if ($csv["size"] > 5000) {
            echo ("<script>alert('Sorry, your file larger than 5KB')</script>");
        } else if ($csvArray = checkCSVFile(file($temp_name))) {
            $_SESSION["state"] = $state;
            $_SESSION["county"] = $county;
            $_SESSION["municipality"] = $municipality;
            $_SESSION["selected"] = $selected;
            $_SESSION["array"] = $csvArray;
            $_SESSION["site"] = $site;
            header("location: statusbar.php");
        }
    } else {
        $error_message = "All fields are required.";
    }
}
