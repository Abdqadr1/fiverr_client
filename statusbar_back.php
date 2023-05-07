<?php
session_start();
$error_rows = isset($_SESSION['error_rows']) ? $_SESSION['error_rows'] : [];
$success_rows = isset($_SESSION['success_rows']) ? $_SESSION['success_rows'] : 0;

ini_set('max_execution_time', 0);
ignore_user_abort(true);
ob_start();

function getParserFile($id)
{
    $arr = [
        "tax1_co_monmouth_nj_us.php", "tax_datahub.php", "tax_co_ocean_nj_us.php",
        "assessment_cot_tn_gov.php", "padctn_org.php", "property_spatialest.php", "assessormelvinburgess.php",
        "qpublic_schneidercorp.php", "gwinnettassessor_manatron.php",
        "imate.php", "maps_chautauqua.php", "gis_dutchessny.php", "prosgar.php", "lrv_nassaucountyny.php"
    ];
    return $arr[$id - 1];
}

function sendData($message)
{
    echo $message;
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();

    if (connection_aborted()) {
        error_log("User aborted process!");
        $GLOBALS['conn']?->close();
        exit;
    }
}

function generateReport($file, $row)
{
    try {
        $log_foldername = __DIR__ . "/logs";
        if (!file_exists($log_foldername)) {
            // create directory/folder uploads.
            mkdir($log_foldername, 0777, true);
        }
        $log_file_data = $log_foldername . '/log_' . $file . '_' . date('d-M-Y') . '.log';
        $log_data = "";
        $time = $row['time'] ?? '';
        $url = $row['url'] ?? '';
        $message = $row['message'] ?? '';
        $row_num = $row['row_num'] ?? '';
        $msg = "[$time] [row: $row_num, url: $url] [message: $message]";
        $log_data .= $msg . PHP_EOL;
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_data, FILE_APPEND);
    } catch (Throwable $t) {
        error_log("Error when generating report");
    }
}

function saveDataToDB_sendProgress(mysqli $conn, $data, $certNo,  $index, $url, $is_successful = true)
{
    global $success_rows, $error_rows, $array_count;
    $_SESSION['last_index'] = $index;
    $time = date("Y-m-d h:i:sa");

    if ($is_successful) {
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
            $row = ['url' => $url, "message" => $msg, 'row_num' => $certNo, "index" => $index, "time" => $time];
            array_push($error_rows, $row);
            $_SESSION['error_rows'] = $error_rows;

            $script = "<script>";
            $script .= "parent.setProgress($success_rows, `" . json_encode($error_rows) . "` , $array_count)";
            $script .= "</script>";
            sendData($script);
            generateReport($GLOBALS['file'], $row);
        }
    } else {
        $row = ['url' => $url, "message" => $data, 'row_num' => $certNo, "index" => $index, "time" => $time];
        array_push($error_rows, $row);
        $_SESSION['error_rows'] = $error_rows;

        $script = "<script>";
        $script .= "parent.setProgress($success_rows, `" . json_encode($error_rows) . "` , $array_count)";
        $script .= "</script>";
        sendData($script);
        generateReport($GLOBALS['file'], $row);
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

    $file = getParserFile($site['prop_info_site']);
    if (!file_exists($file)) exit("$file does not exist!");

    $server_name = "localhost";
    $username = "root";
    $password = "";

    // Create connection
    $conn = new mysqli($server_name, $username, $password);
    if (!$conn) {
        exit("Connection failed: " . $conn->connect_error);
    }
    $db_name = explode('.', $file, 2)[0];
    $db_name = str_replace('.', '_', $db_name);

    if (!(isset($_SESSION['try_again']) && $_SESSION['try_again'] === true)) {
        $sql = "DROP DATABASE IF EXISTS " . $db_name;
        $sql .= "; CREATE DATABASE " . $db_name;
        $sql .= ";";

        if ($conn->multi_query($sql)) {
            while ($conn->next_result()); // needed after running multi_query
            $conn->select_db($db_name);
        }

        $drop_create_table_sql = "CREATE TABLE tax_certificate (
        certNo INT(5), parcelNo VARCHAR(20), alternateID TEXT,
        chargeType TEXT, faceAmnt FLOAT(10,2), status BOOLEAN,
        assessedValue INT(8), appraisedValue INT(8), propClass TEXT,
        propType TEXT, propLocation TEXT, city TEXT, zip TEXT,
        buildingDescrip TEXT, numBeds INT(2), numBaths INT(2),
        lastRecordedOwner TEXT, lastRecordedOwnerType TEXT,
        lastRecordedDateOfSale DATE, absenteeOwner BOOLEAN,
        livesInState BOOLEAN, saleHistory TEXT, priorDelinqHistory TEXT,
        propertyTaxes TEXT, taxJurisdictionID INT(8) , PRIMARY KEY (`parcelNo`));";

        if (!$conn->query($drop_create_table_sql)) {
            exit("Unable to create tax_certificate table: " . $conn->error);
        }
    } else {
        $conn->select_db($db_name);
    }


    while ($conn->next_result()); // needed after running multi_query
    // last index processed
    $last_index = isset($_SESSION['last_index']) ? (int) $_SESSION['last_index'] + 1 : 0;

    $header = (array) $_SESSION['headers'];
    $header_count = count($header);
    include_once($file);
    $conn?->close();
} else {
    $script = "<script>";
    $script .= "parent.serverError('Incomplete data in the server!')";
    $script .= "</script>";
    exit($script);
}
