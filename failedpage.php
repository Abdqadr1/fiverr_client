<?php
require_once('./failedpage_back.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <title>Failed Row Page</title>
    <link rel="stylesheet" href="style.css">
    <script src="js/failedpage.js" defer></script>
</head>

<body class="body row mx-0 justify-content-center align-items-center">
    <div class="col-11 col-md-8 rounded py-5 px-4 border">
        <h4 class="text-start">Failed Rows</h4>
        <div class="border rounded px-3 py-2">
            <div class="text-end mb-2">Row Count: <span class="border rounded px-2"><?php echo $error_rows_count; ?></span></div>
            <div class='feedback-failed overflow-auto'>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th scope='col'></th>
                            <?php
                            $arr = $_SESSION['array'];
                            $headers = $_SESSION["headers"];
                            for ($h = 1; $h < count($headers); $h++) {
                                $key = $headers[$h];
                                echo "<th scope='col'><small>$key</small></th>";
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        <?php

                        foreach ($error_rows as $error) {
                            $row_num = $error['row_num'] ?? '';
                            $row_url = $error['url'] ?? '';
                            $row_index = $error['index'] ?? '';
                            $row_url = str_starts_with($row_url, 'https://') ? $row_url : "https://" . $row_url;

                            $output =  "<tr>";
                            for ($i = 0; $i < count($arr[$row_index]); $i++) {
                                $value = $arr[$row_index][$i];
                                $key = strtolower(trim($headers[$i]));
                                if ($i == 0) {
                                    $output =  "<td><a href='$row_url'><small>Row $row_num</small></a></td>";
                                    $output .=  "<td data-name='$key' class='d-none'>$value</td>";
                                } else {
                                    $output .= "<td data-name='$key'><span class='form-control' role='textbox' contenteditable>$value</span></td>";
                                }
                            }
                            $output .= "</tr>";
                            echo $output;
                        }

                        ?>


                    </tbody>
                </table>

            </div>

        </div>

        <div class='row mx-0 justify-content-center gx-2 fs-4 mt-4 fw-bold align-items-center'>
            <form class='text-underline col-5 text-start' method="post" action="skip.php">
                <input type="hidden" name="type" value="skip" />
                <button type="submit" class="btn btn-link">Skip</button>
            </form>
            <form id="try-again-form" class='text-underline col-5 text-end'>
                <input type="hidden" name="type" value="try again" />
                <button type="submit" class="btn btn-outline-secondary">Try Again</button>
            </form>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.7/dist/iconify-icon.min.js"></script>
    <script src=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script>
</body>

</html>