<!DOCTYPE html>
<html>

<?php include_once "head.html"; ?>

<body>
    <?php include_once "navbar.html"; ?>

    <div class="container-fluid contain">
        <div class="row justify-content-around mx-0 pt-2 img-bg">
            <div class="col-7 border mx-0 p-0">
                <div class="">
                    <div class="row justify-content-around py-2 mx-0 border bg-light mb-4">
                        <div class="col-2">
                            <img src="assets/user.png" alt="" class="rounded-pill" height="70px" width="100%">
                            <span class="text-center d-inline-block w-100">John B</span>
                        </div>
                        <div class="col-10">
                            Hi and welcome to Nutley. <br /> The nicest city in NJ and the home to...
                            <br /><a href="" class="btn btn-outline-primary ms-1 mt-2 rounded-pill">See more...</a>
                        </div>
                    </div>
                    <div class="tabs mt-2">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="" id="1">Overview</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="" id="2">Home Values</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="" id="3">Biggest Players</a>
                            </li>
                        </ul>
                        <div class="tab-content p-3">
                            <div id="slider" class="carousel slide" aria-id="overview" data-bs-interval="false" data-interval="false">
                                <div class="carousel-inner">
                                    <div class="carousel-item active" id="1">
                                        <!-- first tab -->
                                        <div class="mb-2 text-center fs-4">State of <b>NJ</b> Interest capped at
                                            <span class="londrina">18%</span>
                                        </div>
                                        <div class="row justify-content-around">
                                            <div class="col-5 pt-3 m-0">
                                                <img src="assets/img_map.png" class="img-map" alt="city map">
                                            </div>
                                            <div class="col-7 m-0">
                                                <ul class="d-flex justify-content-center mt-3 text-center pb-2 fs-5 border-bottom">
                                                    <li class="half mx-1 mx-lg-3 px-lg-2"><b class="d-block">2</b> years
                                                        to redeem
                                                    </li>
                                                    <li class="half mx-1 mx-lg-3 px-lg-2">Sale takes place at least once
                                                        a year
                                                    </li>
                                                </ul>
                                                <div class="mt-lg-4">
                                                    <h5 class="text-center m-0">Essex County</h5>
                                                    <ul class="d-flex justify-content-around mt-1 text-center">
                                                        <li class="pt-4 text-left">Ranks:</li>
                                                        <li class="px-lg-2">
                                                            <span class="londrina d-block">#1</span>
                                                            <span>in home values</span>
                                                        </li>
                                                        <li class="px-lg-2">
                                                            <span class="londrina d-block">#9</span>
                                                            <span>in investor activity</span>
                                                        </li>
                                                        <li class="px-lg-2">
                                                            <span class="londrina d-block">532</span>
                                                            <span>liens sold last year</span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="carousel-item" id="2">
                                        <img src="assets/img_values.png" class="d-block w-100" height="500px" alt="">
                                    </div>
                                    <div class="carousel-item" id="3">
                                        <div class="third-slide my-3 mx-1">
                                            <ul class="nav nav-tabs justify-content-end pe-5" id="third-slide-tab-list">
                                                <li class="nav-item">
                                                    <a class="nav-link text-dark" aria-current="page" href="#">2020</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link text-dark" href="#">2019</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link active text-dark" href="">2018</a>
                                                </li>
                                            </ul>
                                            <div id="content">
                                                <div class="row justify-content-start gx-2 mt-2 mx-1">
                                                    <div class="col-4">
                                                        <span class="fs-6 d-inline-block">Top</span>
                                                        <select class="form-select d-inline w-50" aria-label="Default select example">
                                                            <option value="1" selected>3</option>
                                                            <option value="2">5</option>
                                                            <option value="3">10</option>
                                                        </select>
                                                    </div>
                                                    <div class="col fs-6">
                                                        <span class="d-inline-block mt-2">Biggest Players <a>in
                                                                2018</a></span>
                                                    </div>
                                                </div>
                                                <!-- table -->
                                                <table class="table table-borderless mt-3 text-center">
                                                    <thead>
                                                        <tr>
                                                            <th></th>
                                                            <th scope="col">Total Liens Bought</th>
                                                            <th scope="col">Total Amount Paid</th>
                                                            <th scope="col">Total Paid in Premium</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <th scope="row">US Bank</th>
                                                            <th>13</th>
                                                            <td>$15,689.45</td>
                                                            <td>$45,675</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">TSW</th>
                                                            <th>7</th>
                                                            <td>$17,835.18</td>
                                                            <td>$26,510</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Honey Hedge LLC</th>
                                                            <th>7</th>
                                                            <td>$11,143.05</td>
                                                            <td>$7,700</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <!-- compare 2 cars table -->
                                                <table class="table table-bordered mt-5 border table-striped table-hover" id="compareTable">
                                                    <tbody class="text-center">
                                                        <tr>
                                                            <td class="head">Typical Snatched Up Lien
                                                                <i class="bi bi-box-arrow-up-right bg-secondary py-1 px-2 rounded text-white" title="show report" data-bs-toggle="modal" data-bs-target="#avgReportModal"></i>
                                                            </td>
                                                            <td></td>
                                                            <td class="head">Typical No Bid Lien
                                                                <i class="bi bi-box-arrow-up-right bg-secondary py-1 px-2 rounded text-white" title="show report" data-bs-toggle="modal" data-bs-target="#avgReportModal"></i>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>$4,365</td>
                                                            <th>Avg Sale Amnt.</th>
                                                            <td>$2,312</td>
                                                        </tr>
                                                        <tr>
                                                            <td>$5,505</td>
                                                            <th>Avg Premium Paid</th>
                                                            <td>--</td>
                                                        </tr>
                                                        <tr>
                                                            <td>3</td>
                                                            <th>Est. No. of Bids</th>
                                                            <td>--</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="cityGraphic">
                                                                <div id="cityChart"></div>
                                                            </td>
                                                            <th>Property Type</th>
                                                            <td class="cityGraphic">
                                                                <div id="cityChart"></div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>$286,765</td>
                                                            <th>Home Value</th>
                                                            <td>$113,654</td>
                                                        </tr>
                                                        <tr>
                                                            <td>8 years</td>
                                                            <th>Length of Ownership</th>
                                                            <td>3.5 years</td>
                                                        </tr>
                                                        <tr>
                                                            <td>1</td>
                                                            <th>Active Mortgs</th>
                                                            <td>1</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <!-- that link takes you to a page called 'Search Results' where the tax liens are displayed -->
                                                <!-- <a href="" class="btn btn-primary mx-auto mt-3 d-block w-50">See Full
                                                    Report</a> -->
                                                <a href="search_results.php" class="d-block mx-auto w-50 mt-3 text-center">Show All Tax
                                                    Liens Sold in
                                                    <b>2018</b></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- show tax liens here -->
                        <div class="border m-0">
                            <div class="row justify-content-around mt-2 text-center fs-5">
                                <div class="col">
                                    Tax Liens to be sold <br><a href="">Register Now</a>
                                </div>
                                <div class="col">
                                    Countdown to <b>2/18/22</b> <br> 3 days left at 5pm EST
                                </div>
                            </div>
                            <!-- search results list -->
                            <div class="container my-3" id="resultsDiv">
                                <div class="row g-2">
                                    <!-- for active liens -->
                                    <div class="col-6 active-lien" id="lien">
                                        <div class="p-2">
                                            <div class="border">
                                                <div class="border search-item-header py-1">
                                                    <div class="d-flex justify-content-around" id="tax-head">
                                                        <span class="ms-2 me-1" style="font-size: 12px;">
                                                            <input class="form-check-input ms-1 check" type="checkbox" value="" id="flexCheckDefault"><br>Add
                                                        </span>
                                                        <span class="fs-6 ms-lg-2 mt-2">Tax Sale Certificate
                                                            <span class="ms-2 text-center" id="lienNo">#21-009</span></span>
                                                    </div>
                                                    <div class="row justify-content-between mt-2 mx-0 text-center">
                                                        <span class="col-6">Total Price<br><b>$4617.09</b></span>
                                                        <span class="col-3">Block<br><b>352</b></span>
                                                        <span class="col-2">Lot<br><b>11</b></span>
                                                    </div>
                                                </div>
                                                <div class="row justify-content-between mt-2 mx-1">
                                                    <div class="col-5 p-0">
                                                        <img src="assets/img_map.png" height="80px" width="100%">
                                                    </div>
                                                    <div class="col-7 ps-1 mx-0 search-query-text">
                                                        <address class="mb-2">1499 Hackenbee St Nutley, NJ 07015
                                                        </address>
                                                        <div class="text-uppercase">STATUS: <span>Active</span></div>
                                                    </div>
                                                    <div class="col-6 text-center">Source:<br><b>MLS</b></div>
                                                    <div class="col-6">Est. Value: <b>$191,562</b></div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <!-- for old liens -->
                                    <div class="col-6 old-lien" id="lien">
                                        <div class="p-2">
                                            <div class="border">
                                                <div class="border search-item-header py-1">
                                                    <div class="d-flex  justify-content-around" id="tax-head">
                                                        <span class="ms-2 me-1" style="font-size: 12px;">
                                                            <input class="form-check-input ms-1 check" type="checkbox" value="" id="flexCheckDefault"><br>Add
                                                        </span>
                                                        <span class="fs-6 ms-1 ms-lg-3 mt-1">Tax Sale Certificate<br>
                                                            <small>Sold on <a href="">Dec 24, 2018</a></small></span>
                                                        <span class="mt-2 ms-1 ms-lg-3">Cert. No
                                                            <br><b id="lienNo">18-067</b></span>
                                                    </div>
                                                    <div class="row justify-content-around mt-2 mx-0 text-center">
                                                        <span class="col-4"><small>Total
                                                                Price</small><br><b>$4617.09</b></span>
                                                        <span class="col-4"><small>Int. Rate</small><br><b>352</b></span>
                                                        <span class="col-2"><small>Block</small><br><b>1133</b></span>
                                                        <span class="col-2"><small>Lot</small><br><b>5</b></span>
                                                    </div>
                                                </div>
                                                <div class="row justify-content-between mt-2 mx-1">
                                                    <div class="col-5 p-0">
                                                        <img src="assets/img_map.png" height="80px" width="100%">
                                                    </div>
                                                    <div class="col-7 ps-1 mx-0 search-query-text">
                                                        <address class="mb-2">1499 Hackenbee St Nutley, NJ 07015
                                                        </address>
                                                        <div class="text-uppercase">STATUS: <span>OLD</span></div>
                                                    </div>
                                                    <div class="col-6 text-center">Source:<br><b>MLS</b></div>
                                                    <div class="col-6">Est. Value: <b>$191,562</b></div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <!-- for old liens -->
                                    <div class="col-6 old-lien" id="lien">
                                        <div class="p-2">
                                            <div class="border">
                                                <div class="border search-item-header py-1">
                                                    <div class="d-flex  justify-content-around" id="tax-head">
                                                        <span class="ms-2 me-1" style="font-size: 12px;">
                                                            <input class="form-check-input ms-1 check" type="checkbox" value="" id="flexCheckDefault"><br>Add
                                                        </span>
                                                        <span class="fs-6 ms-1 ms-lg-3 mt-1">Tax Sale Certificate<br>
                                                            <small>Sold on <a href="">Dec 24, 2018</a></small></span>
                                                        <span class="mt-2 ms-1 ms-lg-3">Cert. No
                                                            <br><b id="lienNo">18-067</b></span>
                                                    </div>
                                                    <div class="row justify-content-around mt-2 mx-0 text-center">
                                                        <span class="col-4"><small>Total
                                                                Price</small><br><b>$4617.09</b></span>
                                                        <span class="col-4"><small>Int. Rate</small><br><b>352</b></span>
                                                        <span class="col-2"><small>Block</small><br><b>1133</b></span>
                                                        <span class="col-2"><small>Lot</small><br><b>5</b></span>
                                                    </div>
                                                </div>
                                                <div class="row justify-content-between mt-2 mx-1">
                                                    <div class="col-5 p-0">
                                                        <img src="assets/img_map.png" height="80px" width="100%">
                                                    </div>
                                                    <div class="col-7 ps-1 mx-0 search-query-text">
                                                        <address class="mb-2">1499 Hackenbee St Nutley, NJ 07015
                                                        </address>
                                                        <div class="text-uppercase">STATUS: <span>OLD</span></div>
                                                    </div>
                                                    <div class="col-6 text-center">Source:<br><b>MLS</b></div>
                                                    <div class="col-6">Est. Value: <b>$191,562</b></div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <!-- for old liens -->
                                    <div class="col-6 old-lien" id="lien">
                                        <div class="p-2">
                                            <div class="border">
                                                <div class="border search-item-header py-1">
                                                    <div class="d-flex  justify-content-around" id="tax-head">
                                                        <span class="ms-2 me-1" style="font-size: 12px;">
                                                            <input class="form-check-input ms-1 check" type="checkbox" value="" id="flexCheckDefault"><br>Add
                                                        </span>
                                                        <span class="fs-6 ms-1 ms-lg-3 mt-1">Tax Sale Certificate<br>
                                                            <small>Sold on <a href="">Dec 24, 2018</a></small></span>
                                                        <span class="mt-2 ms-1 ms-lg-3">Cert. No
                                                            <br><b id="lienNo">18-067</b></span>
                                                    </div>
                                                    <div class="row justify-content-around mt-2 mx-0 text-center">
                                                        <span class="col-4"><small>Total
                                                                Price</small><br><b>$4617.09</b></span>
                                                        <span class="col-4"><small>Int. Rate</small><br><b>352</b></span>
                                                        <span class="col-2"><small>Block</small><br><b>1133</b></span>
                                                        <span class="col-2"><small>Lot</small><br><b>5</b></span>
                                                    </div>
                                                </div>
                                                <div class="row justify-content-between mt-2 mx-1">
                                                    <div class="col-5 p-0">
                                                        <img src="assets/img_map.png" height="80px" width="100%">
                                                    </div>
                                                    <div class="col-7 ps-1 mx-0 search-query-text">
                                                        <address class="mb-2">1499 Hackenbee St Nutley, NJ 07015
                                                        </address>
                                                        <div class="text-uppercase">STATUS: <span>OLD</span></div>
                                                    </div>
                                                    <div class="col-6 text-center">Source:<br><b>MLS</b></div>
                                                    <div class="col-6">Est. Value: <b>$191,562</b></div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <!-- for old liens -->
                                    <div class="col-6 old-lien" id="lien">
                                        <div class="p-2">
                                            <div class="border">
                                                <div class="border search-item-header py-1">
                                                    <div class="d-flex  justify-content-around" id="tax-head">
                                                        <span class="ms-2 me-1" style="font-size: 12px;">
                                                            <input class="form-check-input ms-1 check" type="checkbox" value="" id="flexCheckDefault"><br>Add
                                                        </span>
                                                        <span class="fs-6 ms-1 ms-lg-3 mt-1">Tax Sale Certificate<br>
                                                            <small>Sold on <a href="">Dec 24, 2018</a></small></span>
                                                        <span class="mt-2 ms-1 ms-lg-3">Cert. No
                                                            <br><b id="lienNo">18-067</b></span>
                                                    </div>
                                                    <div class="row justify-content-around mt-2 mx-0 text-center">
                                                        <span class="col-4"><small>Total
                                                                Price</small><br><b>$4617.09</b></span>
                                                        <span class="col-4"><small>Int. Rate</small><br><b>352</b></span>
                                                        <span class="col-2"><small>Block</small><br><b>1133</b></span>
                                                        <span class="col-2"><small>Lot</small><br><b>5</b></span>
                                                    </div>
                                                </div>
                                                <div class="row justify-content-between mt-2 mx-1">
                                                    <div class="col-5 p-0">
                                                        <img src="assets/img_map.png" height="80px" width="100%">
                                                    </div>
                                                    <div class="col-7 ps-1 mx-0 search-query-text">
                                                        <address class="mb-2">1499 Hackenbee St Nutley, NJ 07015
                                                        </address>
                                                        <div class="text-uppercase">STATUS: <span>OLD</span></div>
                                                    </div>
                                                    <div class="col-6 text-center">Source:<br><b>MLS</b></div>
                                                    <div class="col-6">Est. Value: <b>$191,562</b></div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <!-- for old liens -->
                                    <div class="col-6 old-lien" id="lien">
                                        <div class="p-2">
                                            <div class="border">
                                                <div class="border search-item-header py-1">
                                                    <div class="d-flex  justify-content-around" id="tax-head">
                                                        <span class="ms-2 me-1" style="font-size: 12px;">
                                                            <input class="form-check-input ms-1 check" type="checkbox" value="" id="flexCheckDefault"><br>Add
                                                        </span>
                                                        <span class="fs-6 ms-1 ms-lg-3 mt-1">Tax Sale Certificate<br>
                                                            <small>Sold on <a href="">Dec 24, 2018</a></small></span>
                                                        <span class="mt-2 ms-1 ms-lg-3">Cert. No
                                                            <br><b id="lienNo">18-067</b></span>
                                                    </div>
                                                    <div class="row justify-content-around mt-2 mx-0 text-center">
                                                        <span class="col-4"><small>Total
                                                                Price</small><br><b>$4617.09</b></span>
                                                        <span class="col-4"><small>Int. Rate</small><br><b>352</b></span>
                                                        <span class="col-2"><small>Block</small><br><b>1133</b></span>
                                                        <span class="col-2"><small>Lot</small><br><b>5</b></span>
                                                    </div>
                                                </div>
                                                <div class="row justify-content-between mt-2 mx-1">
                                                    <div class="col-5 p-0">
                                                        <img src="assets/img_map.png" height="80px" width="100%">
                                                    </div>
                                                    <div class="col-7 ps-1 mx-0 search-query-text">
                                                        <address class="mb-2">1499 Hackenbee St Nutley, NJ 07015
                                                        </address>
                                                        <div class="text-uppercase">STATUS: <span>OLD</span></div>
                                                    </div>
                                                    <div class="col-6 text-center">Source:<br><b>MLS</b></div>
                                                    <div class="col-6">Est. Value: <b>$191,562</b></div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php include_once "bottom_navbar.html"; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once "mylist.html"; ?>
        </div>

        <?php include_once "modals.html" ?>
    </div>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="city.js" type="module"></script>
</body>

</html>