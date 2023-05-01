<?php
session_start();
$error_rows = isset($_SESSION['error_rows']) ? $_SESSION['error_rows'] : [];
$success_rows = isset($_SESSION['success_rows']) ? $_SESSION['success_rows'] : 0;

ini_set('max_execution_time', 0);
ignore_user_abort(true);
ob_start();

function sendData($message)
{
    echo $message;
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();

    if (connection_aborted()) {
        exit;
    }
}

function saveDataToDB_sendProgress(mysqli $conn, $data, $index, $url, $is_successful = true)
{
    global $success_rows, $error_rows, $array_count;

    if ($is_successful) {
        $_SESSION['last_index'] = $index;
        //insert into table
        unset($data['auctionID']);
        $keys = array_keys($data);
        $values = array_values($data);
        $values = array_map(function ($val) use ($conn) {
            return "'" . $conn->escape_string($val) . "'";
        }, $values);

        $keys_str = implode(',', $keys);
        $values_str = implode(',', $values);
        $insert_sql = "INSERT INTO tax_certificate (" . $keys_str . ") VALUES (" . $values_str . ")";

        if ($conn->query($insert_sql)) {
            $success_rows += 1;
            $_SESSION['success_rows'] = $success_rows;
            $script = "<script>";
            $script .= "parent.setProgress($success_rows, `" . json_encode($error_rows) . "` , $array_count)";
            $script .= "</script>";
            sendData($script);
        } else {
            $msg = $conn?->error ?? "Unknown error occur when inserting data into database";
            array_push($error_rows, ['url' => $url, "message" => $msg, 'row_num' => $index]);
            $_SESSION['error_rows'] = $error_rows;

            $script = "<script>";
            $script .= "parent.setProgress($success_rows, `" . json_encode($error_rows) . "` , $array_count)";
            $script .= "</script>";
            sendData($script);
        }
    } else {
        array_push($error_rows, ['url' => $url, "message" => $data, 'row_num' => $index]);
        $_SESSION['error_rows'] = $error_rows;

        $script = "<script>";
        $script .= "parent.setProgress($success_rows, `" . json_encode($error_rows) . "` , $array_count)";
        $script .= "</script>";
        sendData($script);
    }
}

if (
    isset($_SESSION["state"]) &&
    isset($_SESSION["county"]) &&
    isset($_SESSION['municipality']) &&
    isset($_SESSION['array']) &&
    isset($_SESSION['site']) &&
    isset($_SESSION['selected'])
) {

    $state_full = $_SESSION['state'];
    $county = $_SESSION['county'];
    $municipality = $_SESSION['municipality'];
    $selected = $_SESSION['selected'];
    $array = (array) $_SESSION['array'];
    $site = $_SESSION['site'];
    $array_count = count($array);

    $file = $site['file'];
    if (!file_exists($file)) exit("$file does not exist!");

    $server_name = "localhost";
    $username = "root";
    $p = "";

    // Create connection
    $conn = new mysqli($server_name, $username, $p);
    if (!$conn) {
        exit("Connection failed: " . $conn->connect_error);
    }
    $db_name = explode('.', $file, 2)[0];
    $db_name = str_replace('.', '_', $db_name);

    $sql = "DROP DATABASE IF EXISTS " . $db_name;
    $sql .= "; CREATE DATABASE " . $db_name;
    $sql .= ";";

    if ($conn->multi_query($sql)) {
        while ($conn->next_result()); // needed after running multi_query
        $conn->select_db($db_name);
    }

    $drop_create_table_sql = "DROP TABLE IF EXISTS tax_certificate;";
    $drop_create_table_sql .= "CREATE TABLE tax_certificate (
certNo INT(5), parcelNo TEXT, alternateID TEXT,
chargeType TEXT, faceAmnt FLOAT(10,2), status BOOLEAN,
assessedValue INT(8), appraisedValue INT(8), propClass TEXT,
propType TEXT, propLocation TEXT, city TEXT, zip TEXT,
buildingDescrip TEXT, numBeds INT(2), numBaths INT(2),
lastRecordedOwner TEXT, lastRecordedOwnerType TEXT,
lastRecordedDateOfSale DATE, absenteeOwner BOOLEAN,
livesInState BOOLEAN, saleHistory TEXT, priorDelinqHistory TEXT,
propertyTaxes TEXT, taxJurisdictionID INT(8));";

    if (!$conn->multi_query($drop_create_table_sql)) {
        exit("Unable to create tax_certificate table: " . $conn->error);
    }
    while ($conn->next_result()); // needed after running multi_query
    include_once($file);
} else {
    exit("Invalid data!");
}
session_destroy();
