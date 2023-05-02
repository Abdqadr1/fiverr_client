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
    <style></style>
</head>

<body class="body row mx-0 justify-content-center align-items-center">
    <div class="col-12 col-sm-10 col-md-7 rounded py-5 px-4 border">
        <h4 class="text-start">Failed Rows</h4>
        <div class="border rounded px-3 py-2">
            <div class="text-end mb-2">Row Count: <span class="border rounded px-2"><?php echo $error_rows_count; ?></span></div>
            <div class='feedback-failed overflow-auto'>
                <?php

                foreach ($error_rows as $error) {
                    $msg = $error['message'] ?? '';
                    $row_num = $error['row_num'] ?? '';
                    $row_url = $error['url'] ?? '';
                    $row_url = str_starts_with($row_url, 'https://') ? $row_url : "https://" . $row_url;
                    echo "
                    <div class='d-flex mb-1'>
                <div class='w-25'><a href='" . $row_url . "'>Row $row_num</a></div>
                <div class='w-75'><strong>Error:</strong> <small>$msg</small></div>
            </div>
                    ";
                }

                ?>
            </div>

        </div>

        <div class='row mx-0 justify-content-center gx-2 fs-4 mt-4 fw-bold align-items-center'>
            <form class='text-underline col-5 text-start' method="post" action="skip.php">
                <input type="hidden" name="type" value="skip" />
                <button type="submit" class="btn btn-link">Skip</button>
            </form>
            <form class='text-underline col-5 text-end' method="post" action="skip.php">
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