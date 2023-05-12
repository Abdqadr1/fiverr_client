<?php
require_once('sql.php');

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
    global $conn, $db_name;
    echo $message;
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();

    if (connection_aborted()) {
        error_log("User aborted process!");
        $conn->query("DROP DATABASE IF EXISTS " . $db_name);
        $conn->close();
        exit;
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
        }
    } else {
        $row = ['url' => $url, "message" => $data, 'row_num' => $certNo, "index" => $index, "time" => $time];
        array_push($error_rows, $row);
        $_SESSION['error_rows'] = $error_rows;

        $script = "<script>";
        $script .= "parent.setProgress($success_rows, `" . json_encode($error_rows) . "` , $array_count)";
        $script .= "</script>";
        sendData($script);
    }
}

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

    $juris_id = $_SESSION['juris_id'];
    $array = $_SESSION['array'];
    $header = $_SESSION['headers'];
    $extra_header = $_SESSION['extra_header'];
    $db_name = $_SESSION['db_name'];
    $prop_info_site = $_SESSION['prop_info_site'];

    $file = getParserFile($prop_info_site);
    if (!file_exists($file)) exit("$file does not exist!");


    //mysqli connection without the database
    connect(false);

    if (!(isset($_SESSION['try_again']) && $_SESSION['try_again'] === true)) {
        // drop database if it exists and recreate it
        $drop_db_sql = "DROP DATABASE IF EXISTS " . $db_name;
        $conn->query($drop_db_sql);
        $create_db_sql = "CREATE DATABASE " . $db_name;
        if (!$conn->query($create_db_sql)) {
            exit("Unable to create database $db_name");
        }

        // connect to the database
        $conn->select_db($db_name);

        $create_table_sql = "CREATE TABLE IF NOT EXISTS `tax_certificate` (
  `certNo` int(5) UNSIGNED ZEROFILL NOT NULL,
  `parcelNo` varchar(25) NOT NULL,
  `alternateID` varchar(25) DEFAULT NULL,
  `chargeType` set('Property Taxes','Water','Sewer','PILOT','Utility','Sp Assmnt','Misc','Boarding Up','Demolition','QFARM','Bill Board','Cell Tower') DEFAULT NULL,
  `faceAmnt` float(8,2) UNSIGNED NOT NULL,
  `status` tinyint(1) NOT NULL,
  `assessedValue` int(8) UNSIGNED DEFAULT NULL,
  `appraisedValue` int(8) UNSIGNED DEFAULT NULL,
  `propClass` enum('Residential','Land','Commercial','Industrial','Other') DEFAULT NULL,
  `propType` varchar(50) DEFAULT NULL,
  `propLocation` varchar(50) DEFAULT NULL,
  `city` varchar(30) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `buildingDescrip` varchar(50) DEFAULT NULL,
  `numBeds` int(2) UNSIGNED DEFAULT NULL,
  `numBaths` int(2) UNSIGNED DEFAULT NULL,
  `lastRecordedOwner` varchar(50) DEFAULT NULL,
  `lastRecordedOwnerType` enum('Individual(s)','Estate','LLC/LP/Inc','Corp Entity','Unknown') NOT NULL,
  `lastRecordedDateOfSale` date DEFAULT NULL,
  `absenteeOwner` tinyint(1) DEFAULT NULL,
  `livesInState` tinyint(1) DEFAULT NULL,
  `saleHistory` text,
  `priorDelinqHistory` text,
  `propertyTaxes` text,
  `taxJurisdictionID` int(8) UNSIGNED ZEROFILL DEFAULT NULL,
  PRIMARY KEY (`certNo`),
  UNIQUE KEY `parcelNo` (`parcelNo`),
  KEY `taxJurisdictionID` (`taxJurisdictionID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;";

        if (!$conn->query($create_table_sql)) {
            exit("Unable to create tax_certificate table");
        }
    } else {
        // connect to already created database and continue with the edited rows
        $conn->select_db($db_name);
    }
    $array_count = count($array);
    $header_count = count($header);

    $err_message = "";

    include_once($file);

    // last index processed
    $last_index = isset($_SESSION['last_index']) ? (int) $_SESSION['last_index'] + 1 : 0;
    for ($i = $last_index ?? 0; $i < $array_count; $i++) {
        parsedRow($conn, $i, $array[$i], $header, $extra_header, "saveDataToDB_sendProgress");
    }

    $conn?->close();
} else {
    $script = "<script>";
    $script .= "parent.serverError('Incomplete data in the server!')";
    $script .= "</script>";
    exit($script);
}
