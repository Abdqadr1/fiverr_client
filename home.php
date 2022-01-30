<!DOCTYPE html>
<html>

<?php include_once "head.html"; ?>

<body style="overflow-y: hidden;">
    <?php include_once "navbar.html"; ?>
    <div class="container-fluid" id="contain">
        <div class="row justify-content-between mx-0 pt-2 img-bg">
            <div class="col-4 col-lg-3 border p-0">
                <div class="bg-light">
                    <div>
                        <h4 class="text-center py-3">Upcoming Auctions</h4>
                        <!-- Upcoming auctions list -->
                        <ul class="auctions-list">
                            <li>
                                <span>Ongoing Auctions</span>
                                <span>Feb 5, 2022</span>
                            </li>
                            <li>
                                <span>Ongoing Auctions</span>
                                <span>Feb 5, 2022</span>
                            </li>
                            <li>
                                <span>Ongoing Auctions</span>
                                <span>Feb 5, 2022</span>
                            </li>
                        </ul>
                        <!-- calender  -->
                        <div class="my-3 mx-0 px-1">
                            <img src="assets/img_calendar.png" class="d-inline-block" alt="calendar" height="180px" style="width: 95%;">
                        </div>
                    </div>
                    <div class="text-center see-full">
                        <span>See the full calendar >></span>
                    </div>
                </div>
            </div>
            <div class="col-4 col-lg-3 border rounded p-0" id="myListSide">
                <div class="bg-light">
                    <h4 class="text-center py-3">My List</h4>
                    <!-- my list -->
                    <!-- first city list -->
                    <ul class="list border">
                        <span class="legend">Nutley, NJ</span>
                        <li>
                            <div class="row justify-content-center">
                                <div class="col-9">
                                    <span class="name">Lien #21-453</span>
                                    <span class="address">6541 Main St</span>
                                </div>
                                <div class="col-3 fs-3" title="Delete"><i class="bi bi-trash text-danger"></i></div>
                            </div>
                        </li>
                        <li>
                            <div class="row justify-content-center">
                                <div class="col-9">
                                    <span class="name">Lien #21-563</span>
                                    <span class="address">543 Main St</span>
                                </div>
                                <div class="col-3 fs-3" title="Delete"><i class="bi bi-trash text-danger"></i></div>
                            </div>
                        </li>
                        <li>
                            <div class="row justify-content-center">
                                <div class="col-9">
                                    <span class="name">Lien #21-003</span>
                                    <span class="address">785 Main St</span>
                                </div>
                                <div class="col-3 fs-3" title="Delete"><i class="bi bi-trash text-danger"></i></div>
                            </div>
                        </li>
                    </ul>
                    <!-- second city list -->
                    <ul class="list border">
                        <span class="legend">Phoenix, AZ</span>
                        <li>
                            <div class="row justify-content-center">
                                <div class="col-9">
                                    <span class="name">Lien #21-453</span>
                                    <span class="address">6541 Main St</span>
                                </div>
                                <div class="col-3 fs-3" title="Delete"><i class="bi bi-trash text-danger"></i></div>
                            </div>
                        </li>
                        <li>
                            <div class="row justify-content-center">
                                <div class="col-9">
                                    <span class="name">Lien #21-563</span>
                                    <span class="address">543 Main St</span>
                                </div>
                                <div class="col-3 fs-3" title="Delete"><i class="bi bi-trash text-danger"></i></div>
                            </div>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    <script src="home.js" type="module"></script>
</body>

</html>