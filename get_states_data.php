<?php

function clean_data($txt)
{
    $txt = trim($txt);
    $txt = htmlspecialchars($txt);
    return $txt;
}


if ($_SERVER['REQUEST_METHOD'] === "GET") {
    if (
        isset($_GET['which']) && !empty($_GET['which']) &&
        isset($_GET['value']) && !empty($_GET['value'])
    ) {
        $which = clean_data($_GET['which']);
        $value = clean_data($_GET['value']);

        // create mysqli connection
        $server_name = "localhost";
        $username = "root";
        $password = "";
        $db_name = "states_data";
        $conn = new mysqli($server_name, $username, $password, $db_name);
        if (!$conn) {
            exit("Connection failed: " . $conn->connect_error);
        }
        header('Content-Type: application/json');

        if ($which == "state") {
            // return counties
            $sql = "select name,jurisdiction_id, prop_info_site from `counties` where state='$value'";
            $result = $conn->query($sql);
            $arr = [];
            if ($result?->num_rows > 0) {
                $arr = $result->fetch_all(MYSQLI_ASSOC);
            }
            echo json_encode($arr);
        } else if ($which == "county") {
            // return municipalities
            $sql = "select name, jurisdiction_id from `municipalities` where county_jurisdiction_id='$value'";
            $result = $conn->query($sql);
            $arr = [];
            if ($result?->num_rows > 0) {
                $arr = $result->fetch_all(MYSQLI_ASSOC);
            }
            echo json_encode($arr);
        }
        $conn->close();
    } else {
        http_response_code(403);
        exit('All fields are required!');
    }
}
