<!DOCTYPE html>
<html>
<?php include_once "head.html"; ?>

<body>
    <?php include_once "navbar.html"; ?>
    <div class="container-fluid contain">
        <div class="row justify-content-around mx-0 pt-2 img-bg">
            <div class="col-7 border mx-0 p-0" id="leftSide">
                <div class="search-queries mx-4 mt-2 mb-0 pb-2 pt-2 border-bottom">
                    <b class="text-uppercase ">search criteria: </b>
                    <span class="word-space"> Lienholder: US Bank | saleAmnt: $5,000 - 10,000 | estValue: $46,000+ |
                        propType: Multi-Farm</span>
                    <div class="text-uppercase pt-3"><b>results: </b>3</div>
                </div>
                <!-- search results list -->
                <div class="container my-1" id="resultsDiv">
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
                <!-- action tabs -->
                <div class="sticky-tab">
                    <div class="" id="note">Select any 2 items to compare</div>
                    <ul class="actions-list d-flex px-2 py-2 m-0">
                        <li class="text-white">
                            <a class="btn btn-success action-btn pt-sm-1 pt-lg-2" id="selectAllBtn">Select all</a>
                        </li>
                        <li class="text-white">
                            <a class="btn btn-danger action-btn pt-sm-1" id="deselectAllBtn">Deselect all</a>
                        </li>
                        <li class="dropdown">
                            <a class="btn action-btn text-dark filter mt-0">Filter</a>
                            <div class="dropdown-content" id="bottom">
                                <ul class="dropdown-list">
                                    <li>
                                        <div class="row justify-content-center">
                                            <span class="col-4">Sale Amount:</span>
                                            <div class="col-8">
                                                <ul class="sales-amount-list">
                                                    <li id="1" class="active">$2k or less</li>
                                                    <li id="2">$2k - $5k</li>
                                                    <li id="3">$5k - $10k</li>
                                                    <li id="4">$10k +</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="row justify-content-center mt-2">
                                            <span class="col-4 fw-bold">OR between:</span>
                                            <div class="col-8 flex flex-wrap or">
                                                <span>$</span>
                                                <input id="1" type="number" class="sale-value">
                                                <span class="mx-2">and</span>
                                                <span>$</span>
                                                <input id="2" type="number" class="sale-value">
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="row justify-content-center">
                                            <span class="col-4">Property Type:</span>
                                            <ul class="col-8 d-flex flex-wrap" id="property-type">
                                                <li class="prop">Single Family</li>
                                                <li class="prop">Multi Family</li>
                                                <li class="prop">Land</li>
                                                <li class="prop">Commercial</li>
                                            </ul>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="row justify-content-center">
                                            <span class="col-4">Est. Home Value:</span>
                                            <div class="col-8 flex flex-wrap or mt-2">
                                                <span class="fw-bold mx-1">between</span>
                                                <span>$</span>
                                                <input type="number" id="1" class="home-value">
                                                <span class="mx-1">and</span>
                                                <span>$</span>
                                                <input type="number" id="2" class="home-value">
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="row justify-content-center">
                                            <span class="col-4">Length of Ownership:</span>
                                            <div class="col-8">
                                                <ul class="length-bar">
                                                    <li class="active" id="1" aria-value="1">3 years or fewer</li>
                                                    <li id="2" aria-value="2">3 - 10</li>
                                                    <li id="3" aria-value="3">10 - 20</li>
                                                    <li id="4" aria-value="4">20+ years</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="text-white">
                            <a id="compareBtn" class="btn action-btn">Compare</a>
                        </li>
                        <li>
                            <a class="btn btn-secondary big" id="avgReportBtn">Calculate Avg Report</a>
                        </li>
                        <li>
                            <a class="btn btn-secondary big" href="" style="line-height: 25px;" id="addBtn">
                                Add to My liens</a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php include_once "mylist.html"; ?>
        </div>
    </div>
    <?php include_once "modals.html" ?>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
    <script src="search_results.js" type="module"></script>
</body>

</html>