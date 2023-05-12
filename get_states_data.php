<?php
require_once 'sql.php';


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (

        isset($_GET['which']) && !empty($_GET['which']) &&
        isset($_GET['value']) && !empty($_GET['value'])

    ) {

        $which = clean_data($_GET['which']);
        $value = clean_data($_GET['value']);
        $value_asInt = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);

        // create mysqli connection
        connect();

        header('Content-Type: application/json');

        if ($which == 'state') {

            $result_array = getCountiesInState($value_asInt);
            echo json_encode($result_array);
        } else if ($which == 'county') {

            $result_array = getMunicipalitiesInCounty($value_asInt);
            echo json_encode($result_array);
        }
    } else {

        http_response_code(403);
        exit('All fields are required!');
    }
}
