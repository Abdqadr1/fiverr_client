<?php
session_start();
session_unset();

function clean_data($txt)
{
    $txt = trim($txt);
    $txt = htmlspecialchars($txt);
    return $txt;
}

function checkCSVFile($file)
{
    $csvAsArray = array_map('str_getcsv', $file);

    if (count($csvAsArray) < 3) {
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

function findSite($state, $county, $municipality)
{
    // create mysqli connection
    $server_name = "localhost";
    $username = "root";
    $password = "";
    $db_name = "states_data";
    $conn = new mysqli($server_name, $username, $password, $db_name);
    if (!$conn) {
        exit("Connection failed: " . $conn->connect_error);
    }

    $sql = "
    SELECT c.name as county_name, m.name as municipality_name, c.prop_info_site, c.jurisdiction_id as county_juri, m.jurisdiction_id as municipality_juri 
    FROM `counties` as c INNER JOIN municipalities as m 
    where c.state='$state' AND c.jurisdiction_id='$county' AND m.county_jurisdiction_id=c.jurisdiction_id AND m.jurisdiction_id='$municipality';";
    $result = $conn->query($sql);
    if ($result?->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return false;
}

$state = $county = $municipality = $selected = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (
        isset($_POST['state']) && !empty($_POST['state']) &&
        isset($_POST['county']) && !empty($_POST['county']) &&
        isset($_POST['municipality']) && !empty($_POST['municipality']) &&
        isset($_FILES['csv']) && !empty($_FILES['csv']) &&
        isset($_POST['selected']) && !empty($_POST['selected'])
    ) {
        $state = clean_data($_POST['state']);
        $state = ucwords($state);
        $county = clean_data($_POST['county']);
        $municipality = clean_data($_POST['municipality']);
        $selected = clean_data($_POST['selected']);
        $csv = $_FILES['csv'];
        $file_name = $csv["name"];
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $temp_name = $csv["tmp_name"];
        $site = "";

        if (!$site = findSite($state, $county, $municipality)) {
            $error_message = "State, County, and Municipality combination does not exist in database";
        } else if ($extension !== "csv") {
            echo ("<script>alert('File is not csv format')</script>");
        } else if ($csv["size"] > 5000) {
            echo ("<script>alert('Sorry, your file larger than 5KB')</script>");
        } else if ($csvArray = checkCSVFile(file($temp_name))) {
            $headers = array_shift($csvArray);
            $_SESSION["state"] = $state;
            $_SESSION["county"] = $county;
            $_SESSION["municipality"] = $municipality;
            $_SESSION["selected"] = $selected;
            $_SESSION["array"] = $csvArray;
            $_SESSION["headers"] = $headers;
            $_SESSION["site"] = $site;
            // initialize success and error rows
            $_SESSION['error_rows'] = [];
            $_SESSION['success_rows'] = 0;
            header("location: statusbar.php");
        }
    } else {
        $error_message = "All fields are required.";
    }
}
