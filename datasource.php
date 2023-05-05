<?php
require_once('./datasource_back.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <title>Data Source Page</title>
    <link rel="stylesheet" href="style.css">
    <script src="js/datasource.js" defer></script>
    <style></style>
</head>

<body class="body row mx-0 justify-content-center align-items-center">
    <form id="datasource-form" class="col-12 col-sm-10 col-md-7 rounded py-5 px-4 border" method="post" enctype="multipart/form-data">
        <?php
        echo (empty($error_message)
            ? ''
            : "<div class=\"alert alert-danger text-center mb-3\" role=\"alert\">
            $error_message
        </div>");
        ?>

        <div class="row mb-3">
            <label for="states" class="col-sm-2 col-form-label">State</label>
            <div class="col-sm-10">
                <select value="<?php echo $state ?>" name="state" class="form-select" id="state" required>
                    <option value="" hidden>Select state</option>
                    <option value="NEW JERSEY">NEW JERSEY</option>
                    <option value="NEW YORK">NEW YORK</option>
                    <option value="TENNESSEE">TENNESSEE</option>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <label for="country" class="col-sm-2 col-form-label">County</label>
            <div class="col-sm-10">
                <select value="<?php echo $county ?>" name="county" class="form-select" id="county" required>
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <label for="municipality" class="col-sm-2 col-form-label">Municipality</label>
            <div class="col-sm-10">
                <select value="<?php echo $municipality ?>" name="municipality" class="form-select" id="municipality" required>
                </select>
            </div>
        </div>
        <hr />
        <div id="file-div" class="input-group mb-3 rounded">
            <span class="form-control w-75 text-center">Upload Raw File</span>
            <input type="file" id="csv" class='d-none' accept=".csv" name='csv' />
            <label for='csv' class="btn btn-secondary w-25" tabindex="-1">
                <iconify-icon class='fs-4' icon="material-symbols:folder-open-rounded"></iconify-icon>
            </label>
        </div>
        <div class="row mb-3">
            <label for="selected" class="col-sm-2 col-form-label">Selected:</label>
            <div class="col-sm-10">
                <input value="<?php echo $selected ?>" name="selected" class="form-control" id="selected" required />
            </div>
        </div>
        <button class="btn btn-outline-success mt-3 px-3" type="submit">Go</button>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.7/dist/iconify-icon.min.js"></script>
    <script src=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script>
</body>

</html>