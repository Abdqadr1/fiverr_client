<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <title>Status Bar Page</title>
    <link rel="stylesheet" href="style.css">
    <script src="js/statusbar.js" defer></script>
    <style></style>
</head>

<body class="body row mx-0 justify-content-center align-items-center">
    <div class="col-12 col-sm-10 col-md-7 rounded py-5 px-4 border">
        <h5 class='mb-3'>Retrieving Property info...</h5>
        <div class='d-flex justify-content-end mb-3'>
            <button id="cancel-btn" class='btn btn-danger'>Cancel</button>
        </div>

        <div class="progress rounded-pill" role="progressbar" aria-label="Basic example" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" style="height: 40px;">
            <div id="progress-bar" class="progress-bar progress-bar-striped" style="width: 0%;"></div>
        </div>
        <div id="progress-text" class='py-3 text-center'>
            <!-- Finished 16 of 52 | 39% Complete -->
        </div>

        <div class='row mx-0 justify-content-center gx-2 fs-4 mt-4 fw-bold align-items-center'>
            <div id="success-rows" class='text-underline col-5 text-start'>0 rows ✔</div>
            <div id="error-rows" class='text-underline col-5 text-end'>0 rows 🗙</div>
        </div>

        <div id="feedback" class='feedback p-3 overflow-auto border rounded mt-5'>
            <!-- <div class='d-flex'>
                <div class='w-25'>Row 2</div>
                <div class='w-75'><em>Error:</em> faceamount was not type integer</div>
            </div> -->
        </div>

        <div class='d-flex justify-content-end my-3'>
            <button id="continue-btn" disabled="true" class="btn btn-outline-secondary mt-3">Continue &gt;</button>
        </div>

        <iframe id="progress-frame"></iframe>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.7/dist/iconify-icon.min.js"></script>
    <script src=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script>
</body>

</html>