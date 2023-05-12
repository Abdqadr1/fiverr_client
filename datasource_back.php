<?php
require_once 'sql.php';
session_start();
session_unset();

//connect to database
connect();

$file_size_limit = 5000;  // 5 kiloBytes

function checkCSVFile(array $file): array
{
    $csvAsArray = array_map('str_getcsv', $file);
    $numRows = count($csvAsArray);

    // Ensure file contains at least 2 rows
    if ($numRows < 2) {
        throw new InvalidArgumentException('Sorry, your file has less than 2 rows');
    }

    $header = array_shift($csvAsArray); //remove the first row (header row) from the array and return it
    $numColumns = count($header);

    // Ensure file contains at least 6 columns
    if ($numColumns < 6) {
        throw new InvalidArgumentException('Sorry, your file has less than 6 columns');
    } else {

        $extra_header = [];
        $new_header = [
            'adv_num',
            'parcel_id',
            'alternate_id',
            'charge_type',
            'face_amount',
            'status'
        ];

        for ($h = 6; $h < $numColumns; $h++) {
            //  <<==== Acceptable Column name code goes here  ====>>
            $column_name = $header[$h];
            switch (strtolower(trim($column_name))) {
                case 'address':
                    $new_header[] = 'prop_location';
                    $extra_header['prop_location'] = $h;
                    break;
                case "owner name":
                    $new_header[] = 'last_recorded_owner';
                    $extra_header['last_recorded_owner'] = $h;
                    break;
                case "owner address":
                    $new_header[] = 'last_recorded_owner_address';
                    $extra_header['last_recorded_owner_address'] = $h;
                    break;
                case "owner state":
                    $new_header[] = 'last_recorded_owner_state';
                    $extra_header['last_recorded_owner_state'] = $h;
                    break;
                case "zip":
                    $new_header[] = 'zip';
                    $extra_header['zip'] = $h;
                    break;
                case "city":
                    $new_header[] = 'city';
                    $extra_header['city'] = $h;
                    break;
                default:
                    //  <<==== Default case - throw an Error! ====>>
                    throw new InvalidArgumentException('Column "' . $column_name . '" is not an acceptable column name');
            }
        }
    }

    $_SESSION['extra_header'] = $extra_header;
    array_unshift($csvAsArray, $new_header);
    return $csvAsArray;
}



$state_juris_id = $county_juris_id = $municip_juris_id = $db_name = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (

        isset($_POST['state'])    &&  !empty($_POST['state'])    &&
        isset($_POST['county'])   &&  !empty($_POST['county'])   &&
        isset($_FILES['csv'])     &&  !empty($_FILES['csv'])     &&
        isset($_POST['selected']) &&  !empty($_POST['selected'])

    ) {

        $state_juris_id     = clean_data($_POST['state']);
        $county_juris_id    = clean_data($_POST['county']);
        $db_name            = clean_data($_POST['selected']);

        if (isset($_POST['municipality']))
            $municip_juris_id = clean_data($_POST['municipality']);

        $csv       = $_FILES['csv'];
        $file_name = $csv['name'];
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $temp_name = $csv['tmp_name'];

        if ($extension !== 'csv') {
            $error_message = "File format is not .csv";
            return;
        } else if ($csv['size'] > $file_size_limit) {
            $error_message = "Sorry, your file size is larger than " . $file_size_limit . " bytes";
            return;
        }

        try {
            $csvArray = checkCSVFile(file($temp_name));
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            return;
        }

        $query = "SELECT prop_info_site, is_auction_jurisdiction from `counties` WHERE jurisdiction_id=?";
        $stmt  = mysqli_prepare($conn, $query);

        mysqli_stmt_bind_param($stmt, 's', $county_juris_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $propInfoSite, $isAuctionJurisdiction);
        mysqli_stmt_fetch($stmt);

        // Coerce query result to boolean
        // If the county is an Auction Jurisdiction, save its jurisdiction_id in the SESSION var
        // Otherwise save the municipality's jurisdiction_id in the SESSION var

        if (!!$isAuctionJurisdiction) {
            $_SESSION['juris_id'] = $county_juris_id;
        } else {
            if (empty($municip_juris_id)) {
                $error_message = "Please select an Auction Jurisdiction";
                return;
            }
            $_SESSION['juris_id'] = $municip_juris_id;
        }

        $headers = array_shift($csvArray);
        $_SESSION['prop_info_site'] = $propInfoSite;
        $_SESSION['db_name'] = $conn->escape_string($db_name);
        $_SESSION["headers"] = $headers;
        $_SESSION['array'] = $csvArray;
        // initialize success and error rows
        $_SESSION['error_rows'] = [];
        $_SESSION['success_rows'] = 0;
        header("location: statusbar.php");
    } else {
        $error_message = "All fields are required";
        return;
    }
}
