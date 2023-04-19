<!DOCTYPE html>
<html>

<body>

    <?php
    include 'web-crawler.php';
    include 'tax-codes.php';

    /******** SETTINGS *********/
    $city = 'Absecon';
    $state = 'NJ';
    $district = "0101";
    /***************************/

    $array = openFile("./TEST FILES/ocean county_nj_TEST FILE.csv");


    $header = array_shift($array); // remove the first element from the array
    //$header_map = array_map( 'keepOnlyDesired', $header );



    for ($i = 0; $i < 1; $i++) { //count($array)

        // Remove excess whitespace first with 'keepOnlyDesired' function
        // then remove anything that is not a letter or whitespace (the funny chars)
        $row = array_map('keepOnlyDesired', $array[$i]);
        [$adv_num, $parcel_id, $alternate_id, $charge_type, $face_amount, $status] = $row;


        // remove non-numeric characters and cast to float
        $face_amount = (float) preg_replace("/([^0-9\\.]+)/i", "", $face_amount);
        $status = ($status == 'Active') ? 1 : 0;


        /************** CHARGE TYPE DESCRIPTION *****************/
        if (empty($charge_type)) {
            $charge_descrip = "Property Taxes";
        } elseif (strlen($charge_type) > 1) {
            $array_ofChars = str_split($charge_type);
            $reconstructed_str = "";
            do {
                $reconstructed_str .= array_shift($array_ofChars) . ", ";
            } while (count($array_ofChars) > 1);
            $reconstructed_str .= $array_ofChars[0];

            $charge_descrip = parseChargeTypeDescrip($reconstructed_str);
        } else
            $charge_descrip = parseChargeTypeDescrip($charge_type);

        /******************** TEST BELOW *************************/
        //echo $charge_type ." | ". $charge_descrip ."<br>";
        /*********************************************************/



        /*************** PARSE THE BLOCK, LOT AND QUAL ******************/
        $parcel_id = preg_replace("/\s/", "", $parcel_id); // get rid of all whitespace

        // @  <=>  suppress undefined index errors
        [$block, $lot_qual] = explode("-", $parcel_id, 2);
        @[$lot, $qual] = explode("--", $lot_qual, 2);
        $qual ??= "";

        $parcelNo = $block . "-" . $lot . "--" . $qual;

        /**************** CREATE THE TAX ASSESSMENT URL *******************/
        $url = "tax.co.ocean.nj.us/frmTaxBoardTaxListDetail?";

        @[$block, $sub_block] = explode(".", $block, 2);
        $sub_block ??= "";
        @[$lotNumber, $sub_lot] = explode(".", $lot, 2);
        $lot_asArray = explode(".", $lot, 2);

        $options = "nDistrict=$district&szBlockNum=$block&szLotNum=$lotNumber&szBlockStuff=$sub_block&szLotStuff=$sub_lot&szQual=$qual";
        $tax_link = $url . $options;

        /******************** TEST BELOW *************************/
        echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a><br>";
        /*********************************************************/




        // GO TO THE LINK AND DOWNLOAD THE PAGE

        $parsedPage = parsePage($tax_link);
        if (!$parsedPage) {
            echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
            continue;
        }

        [
            'Property Info' => $propInfo,
            'Sale Info' => $saleHist,
            'Tax Assess Info' => $taxAssessInfo
        ] = $parsedPage;


        $owner_name = $propInfo['Owner:'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $prop_loc = $propInfo["Location:"] ?? "";

        $owner_loc = $propInfo["Mailing address:"] ?? "";
        $city_state = $propInfo["City/State:"] ?? "";

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        @[$city, $owner_state, $zip_code] = preg_split('/\s+/', $city_state, 3);
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);


        $bldg_desc = $propInfo["Bldg desc:"] ?? "";
        $bldg_descrip = parseNJBldgDescrip($bldg_desc);
        if (!empty($bldg_desc) && empty($bldg_descrip))
            $bldg_descrip = $bldg_desc;

        $last_year_taxes = $propInfo['Last yr taxes:'] ?? "";
        $net_value = $propInfo["Net value:"] ?? "";

        $taxes_as_text = getTaxesAsText($last_year_taxes);
        $date_bought = $propInfo['Deed date:'] ?? "";

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";

        foreach (array_reverse($saleHist) as ['Deed date:' => $date, 'Sales price:' => $price, 'Book/page:' => $page]) {

            $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
            $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . $page . "</b><m>" . $sale_descrip . "</m></e>";
            if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
        }
        $sale_hist_data = "<r>" . $sale_hist_data . "</r>";



        $_qual = $propInfo['Qual:'] !== "n/a" ? $propInfo['Qual:'] : "";
        $_1 = ($propInfo['Block / Lot:'] ?? "") . "/" . $_qual;
        $beds = $taxAssessInfo["# bedrooms:"] ?? "";
        $baths = $taxAssessInfo["# bathrooms:"] ?? "";
        $p_class = $propInfo["Prop class:"] ?? "";

        [$prop_class, $prop_type] = getPropTypeFromClass($p_class);
        $prop_type = $taxAssessInfo["Type/use:"] ?? "";


        $structure = [
            'certNo'        =>    $adv_num,
            'auctionID'        =>    NULL,
            'parcelNo'        =>    $parcelNo,
            'alternateID'        =>    NULL,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $last_year_taxes ?? '',
            'appraisedValue'    =>    $net_value,
            'propClass'        =>    $prop_class,
            'propType'        =>    $prop_type,
            'propLocation'        =>    $prop_loc,
            'city'            =>    $city,
            'zip'            =>    $zip_code ?? '',
            'buildingDescrip'    =>    $bldg_descrip,
            'numBeds'        =>    $beds,
            'numBaths'        =>    $baths,
            'lastRecordedOwner'    =>    $owner_name,
            'lastRecordedOwnerType'    =>    $owner_type,
            'lastRecordedDateOfSale' =>    date('Y-m-d', strtotime($date_bought)),
            'absenteeOwner'        => ($absentee_owner ? '1' : '0'),
            'livesInState'        => ($lives_in_state ? '1' : '0'),
            'saleHistory'        =>    $sale_hist_data,
            'priorDelinqHistory'    =>    NULL,
            'propertyTaxes'        =>    $taxes_as_text,
            'taxJurisdictionID'    =>    NULL
        ];
        listData($structure);
    }



    function parsePage($target)
    {
        try {

            $page = _http($target);
            $headers = $page['headers'];
            $http_status_code = $headers['status_info']['status_code'];
            //var_dump($headers);

            if ($http_status_code >= 400)
                return FALSE;


            $doc = new DOMDocument('1.0', 'utf-8');
            // don't propagate DOM errors to PHP interpreter
            libxml_use_internal_errors(true);
            // converts all special characters to utf-8
            $content = mb_convert_encoding($page['body'], 'HTML-ENTITIES', 'UTF-8');
            $doc->loadHTML($content);

            $taxListDetailsTable = $doc->getElementById("MainContent_MOD4Table");
            $assessmentHistoryTable = $doc->getElementById("MainContent_AssmtHistTable");
            $propertyDetailsTable = $doc->getElementById("MainContent_CAMATable");
            $saleHistoryTable = $doc->getElementById("MainContent_SR1ATable");

            $propInfo = parseTableData($taxListDetailsTable, false);
            $saleInfo = parseSaleHistoryTable($saleHistoryTable);
            $detailsInfo = parseTableData($propertyDetailsTable, false);
            $assessmentInfo = parseTableData($assessmentHistoryTable);

            return [
                'Property Info'    =>     $propInfo,
                'Sale Info'       =>     $saleInfo,
                'Tax Assess Info'  =>    $detailsInfo
            ];
        } catch (Exception | Error $x) {
            return false;
        }
    }

    function parseTableData($table, $useFirstRowAsKey = true)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $header_map = [];
        $data_map = [];

        $startIndex = $useFirstRowAsKey ? 2 : 0;

        if ($useFirstRowAsKey) {
            /*** First row determines the column values ***/
            foreach ($rows[1]->getElementsByTagName('td') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
            for ($i = $startIndex; $i < $rows_count; $i++) {
                $td_elements = $rows[$i]->getElementsByTagName('td');
                $num_td_elements = $td_elements->count();
                // echo $num_td_elements;
                $row_data = [];

                if ($num_td_elements !== count($header_map)) continue;

                for ($n = 0; $n < $num_td_elements; $n++) {
                    $col = $header_map[$n] ?? "";
                    $row_data[$col] =  trim($td_elements[$n]?->textContent);
                }

                $data_map[] = $row_data;
            }
        } else {
            for ($i = 0; $i < $rows_count; $i++) { // go thru the table starting at row #2
                $td_elements = $rows[$i]->getElementsByTagName('td');
                $num_td_elements = $td_elements->count();
                // echo $num_td_elements;
                $row_data = [];

                // Eliminate those rows that do not have the proper structure (i.e. same # of cols)
                if ($num_td_elements % 2 !== 0)
                    continue;

                for ($n = 0; $n < $num_td_elements; $n += 2) {
                    $key = trim($td_elements[$n]?->textContent);
                    $value = trim($td_elements[$n + 1]?->textContent);

                    if (!$key) continue;

                    $data_map[$key] = $value;
                }
            }
        }

        return $data_map;
    }

    function parseSaleHistoryTable($table)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $data_map = [];
        $row_data = [];
        for ($i = 1; $i < $rows_count; $i++) {
            $val = trim($rows[$i]?->nodeValue);
            if (empty($val)) {
                $data_map[] = $row_data;
                $row_data = [];
                continue;
            }

            $td_elements = $rows[$i]->getElementsByTagName('td');
            $num_td_elements = $td_elements->count();
            if ($num_td_elements % 2 !== 0)
                continue;

            for ($n = 0; $n < $num_td_elements; $n += 2) {
                $key = trim($td_elements[$n]?->textContent);
                $value = trim($td_elements[$n + 1]?->textContent);

                if (!$key) continue;

                $row_data[$key] = $value;
            }
        }
        if (!empty($row_data)) $data_map[] = $row_data;

        return $data_map;
    }

    /*
    JUST LEAVING THIS HERE FOR SAFE-KEEPING...
    //Loop through each <a> tag in the dom and add it to the link array
    foreach($doc->getElementsByTagName('a') as $link) {
        $links[] = array('url' => $link->getAttribute('href'), 'text' => $link->nodeValue);
    }
*/
    ?>
</body>

</html>