<?php
require('./vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (
        isset($_FILES['file']) //&& !empty($_FILES['file'])
    ) {
        // create mysqli connection
        $server_name = "localhost";
        $username = "root";
        $password = "";
        $conn = new mysqli($server_name, $username, $password);
        if (!$conn) {
            exit("Connection failed: " . $conn->connect_error);
        }

        // drop database if already exists and create
        $db_name = "states_data";
        $sql = "DROP DATABASE IF EXISTS " . $db_name;
        $sql .= "; CREATE DATABASE " . $db_name;
        $sql .= ";";

        if ($conn->multi_query($sql)) {
            while ($conn->next_result()); // needed after running multi_query
            $conn->select_db($db_name);
        }
        // create counties and municipalities table
        $create_table_sql = "
            CREATE TABLE `counties` ( `name` VARCHAR (255) NOT NULL , `jurisdiction_id` VARCHAR(50) NOT NULL ,
             `prop_info_site` MEDIUMINT NOT NULL ,`state` VARCHAR(50), PRIMARY KEY (`jurisdiction_id`)); 
            ";
        $create_table_sql .= "
           CREATE TABLE `municipalities` ( `name` VARCHAR (255) NOT NULL , `jurisdiction_id` VARCHAR(50) NOT NULL , 
           `county_jurisdiction_id` INT NOT NULL , PRIMARY KEY (`jurisdiction_id`));";

        if ($conn->multi_query($create_table_sql)) {
            while ($conn->next_result()); // needed after running multi_query
            // parse the uploaded xlsx file
            $temp_name = $_FILES['file']["tmp_name"];
            $spreadsheet = IOFactory::load($temp_name);

            $sheets = $spreadsheet->getAllSheets();
            $sheetNames = $spreadsheet->getSheetNames();
            for ($i = 1; $i < count($sheets); $i++) {
                $sheet = $sheets[$i];
                $state_name = $sheetNames[$i];
                $last_inserted_county_jurisdiction_id = null;
                foreach ($sheet->getRowIterator(2, null) as $row) {

                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(FALSE);
                    $row_content = [];
                    foreach ($cellIterator as $cell) array_push($row_content, $cell->getValue());
                    $county = trim($row_content[0] ?? '');
                    $county = $conn->real_escape_string($county);
                    $municipality = trim($row_content[1] ?? '');
                    $municipality = $conn->real_escape_string($municipality);
                    $jurisdiction_id = trim($row_content[2] ?? '');
                    $jurisdiction_id = $conn->real_escape_string($jurisdiction_id);
                    $prop_info_site = trim($row_content[3] ?? '');
                    $prop_info_site = $conn->real_escape_string($prop_info_site);
                    if (!empty($county)) {
                        $county_sql = "INSERT INTO `counties` (`name`, `jurisdiction_id`, `prop_info_site`, `state`) 
                    VALUES ('$county', '$jurisdiction_id', '$prop_info_site', '$state_name');";
                        if ($conn->query($county_sql)) {
                            $last_inserted_county_jurisdiction_id = $jurisdiction_id;
                        } else {
                            echo "Error inserting county $county" . $conn->error . "<br/><br/>";
                        }
                    } else if (!empty($municipality)) {
                        $municipality_sql = "INSERT INTO `municipalities` (`name`, `jurisdiction_id`, `county_jurisdiction_id`) 
                    VALUES ('$municipality', '$jurisdiction_id', '$last_inserted_county_jurisdiction_id');";
                        if (!$conn->query($municipality_sql)) {
                            echo "Error inserting municipality $municipality" . $conn->error . "<br/><br/>";
                        }
                    }
                }
            }
        } else {
            exit("Error creating tables!");
        }


        $conn->close();
    } else {
        exit("No file uploaded");
    }
}

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    body {
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    form {
        height: auto;
        width: min(500px, 90%);
        padding: 2em;
        border-radius: 8px;
        border: 1px solid gray;
    }

    input {
        border: 1px solid gray;
        padding: 5px;
        border-radius: 5px;
        display: block;
        margin: 1em 0;
    }

    h2 {
        text-align: center;
    }
</style>

<body>
    <form action="" method="post" enctype="multipart/form-data">
        <h2>Upload states data xlsx file</h2>
        <input type="file" name="file" required />
        <input type="submit" value="submit">
    </form>
</body>

</html>