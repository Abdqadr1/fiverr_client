<?php
include_once('./failedpage_back.php');
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
    <div className="col-12 col-sm-10 col-md-7 rounded py-5 px-4 border">
        <div className='feedback-failed px-3 py-2 overflow-auto border rounded'>
            <div className="text-end mb-1">Row Count: 2</div>
            <div className='d-flex mb-1'>
                <div className='w-25'><a href="">Row 2</a></div>
                <div className='w-75'><em>Error:</em> faceamount was not type integer</div>
            </div>
            <div className='d-flex mb-1'>
                <div className='w-25'><a href="">Row 5</a></div>
                <div className='w-75'><em>Warning:</em> faceamount was not type integer</div>
            </div>
        </div>

        <div className='row mx-0 justify-content-center gx-2 fs-4 mt-4 fw-bold align-items-center'>
            <div className='text-underline col-5 text-start'><button className="btn btn-link">Skip</button></div>
            <div className='text-underline col-5 text-end'><button className="btn btn-outline-secondary">Try Again?</button></div>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.7/dist/iconify-icon.min.js"></script>
    <script src=" https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script>
</body>

</html>