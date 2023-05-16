<?php
session_start();

$state = $county = $municipality = $selected = "";

function generateReport($file, $error_rows)
{
    try {
        $log_foldername = __DIR__ . "/logs";
        if (!file_exists($log_foldername)) {
            mkdir($log_foldername, 0777, true);
        }
        $log_file_data = $log_foldername . '/log_' . $file . '_' . date('d-M-Y') . '.log';
        foreach ($error_rows as $row) {
            $log_data = "";
            $time = $row['time'] ?? '';
            $url = $row['url'] ?? '';
            $message = $row['message'] ?? '';
            $row_num = $row['row_num'] ?? '';
            $msg = "[$time] [row: $row_num, url: $url] [message: $message]";
            $log_data .= $msg . PHP_EOL;

            // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
            file_put_contents($log_file_data, $log_data, FILE_APPEND);
        }
    } catch (Throwable $t) {
        error_log("Error when generating report");
    }
}

function clean_data($txt)
{
    $txt = trim($txt);
    $txt = htmlspecialchars($txt);
    return $txt;
}

function testAllKey($arr, $post_keys)
{
    foreach ($arr as $key) {
        $k = strtolower(trim($key));
        $k = preg_replace('/\s+/', '_', $k);
        $k = preg_replace('/[^a-zA-Z0-9_ ]+/', '', $k);

        if (!in_array($k, $post_keys)) {
            http_response_code(403);
            exit("Incomplete data send: No $k");
        }
    }
    return true;
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
    $error_rows = $_SESSION['error_rows'];
    $success_rows = $_SESSION['success_rows'];
    $array = (array) $_SESSION['array'];
    $headers = (array) $_SESSION['headers'];
    $count_array = count($array);
    $db_name = $_SESSION['db_name'];
} else {
    header('location: datasource.php');
}


if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (isset($_POST['type']) && !empty($_POST['type'])) {
        $type = clean_data($_POST['type']);
        if ($type === "skip") {
            //generate error log
            generateReport($db_name, $error_rows);
            header('location: datasource.php');
        } else if ($type === "try again") {
            if (
                isset($_POST['type']) && !empty($_POST['type']) && testAllKey($headers, array_keys($_POST))
            ) {
                if ($success_rows === $count_array) {
                    //generate error log
                    generateReport($db_name, $error_rows);
                    header('location: datasource.php');
                }
                //filter array to contain only the error rows
                $filter = [];
                foreach ($_POST as $key => $value) {
                    if (!is_array($value)) continue;
                    $count = count($value);
                    for ($i = 0; $i < $count; $i++) {
                        $filter[$i] = $filter[$i] ?? [];
                        array_push($filter[$i], $value[$i]);
                    }
                }

                // http_response_code(403);
                // exit(print_r($filter));

                $_SESSION['array'] = $filter;
                $_SESSION['last_index'] = -1;
                $_SESSION['success_rows'] = 0;
                $_SESSION['error_rows'] = [];
                $_SESSION['try_again'] = true;
            } else {
                http_response_code(403);
                exit('Invalid data!');
            }
        }
    }
} else {
    header('location: datasource.php');
}
