<!DOCTYPE html>
<html>

<?php include_once "head.html"; ?>

<body style="overflow-y: hidden;">
    <?php include_once "navbar.html"; ?>
    <div class="container-fluid" id="contain">
        <div class="row justify-content-between mx-0 pt-2 img-bg">
            <div class="col-4 border p-0">
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
            <?php include_once "mylist.html"; ?>
        </div>
    </div>

    <script src="home.js" type="module"></script>
</body>

</html>