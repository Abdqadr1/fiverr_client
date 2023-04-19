<!DOCTYPE html>
<html>

<body>

    <?php
    include 'web-crawler.php';
    include 'tax-codes.php';

    /******** SETTINGS *********/
    $state = 'NY';
    /***************************/


    $array = openFile('./TEST FILES/chautauqua county_ny_TEST FILE.csv');
    $header = array_shift($array); // remove the first element from the array



    for ($i = 0; $i < 1; $i++) { //count($array)

        // Remove excess whitespace first with 'keepOnlyDesired' function
        // then remove anything that is not a letter or whitespace (the funny chars)
        $row = array_map('keepOnlyDesired', $array[$i]);


        [$adv_num, $parcel_id, $alternate_id, $charge_type, $face_amount, $status] = $row;
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

        $parcelNo = $block . "-" . $lot . "-" . $qual;

        /**************** CREATE THE TAX ASSESSMENT URL *******************/
        $url = "maps.chautauquacounty.com/public/PropertyInformationTool/property?";
        $tax_url = "app.co.chautauqua.ny.us/cctaxonline/#/parcel/";


        $swiss = "062889";
        $print_key = "280.18-3-31";
        $options = "printkey=$print_key&swis=$swiss";
        $opt = "$swiss-$print_key/current";
        $link = $url . $options;
        $tax_link = $tax_url . $opt;


        /******************** TEST BELOW *************************/
        echo "<a href='http://" . $link . "'>" . $link . "</a><br>";
        echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a><br>";
        /*********************************************************/


        // GO TO THE LINK AND DOWNLOAD THE PAGE

        $parsedPage = parsePage($link);
        // $parsedTaxPage = parseTaxPage($tax_link);
        if (!$parsedPage) {
            echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
            continue;
        }

        [
            'Property Info' => $propInfo,
            'Sale Info' => $saleHist
        ] = $parsedPage;

        $owner_name = $propInfo['Owner Name'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $prop_loc = $propInfo["Location"] ?? "";

        $owner_loc = ($propInfo["Mailing Address 1"] ?? "") . ' ' . ($propInfo["Mailing Address 2"] ?? "");
        $city_state = $propInfo["Mailing City, State"] ?? "";

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        @[$city, $owner_state] = preg_split('/\s+/', $city_state, 2);
        $city = str_replace(',', '', $city);
        $zip_code = $propInfo['Mailing ZIP Code'] ?? "";
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);


        $bldg_desc = $propInfo["Bldg desc:"] ?? "";
        $bldg_descrip = parseNJBldgDescrip($bldg_desc);
        if (!empty($bldg_desc) && empty($bldg_descrip))
            $bldg_descrip = $bldg_desc;


        $ass_value = $propInfo['Total Assessed Value (100.00% Market)'] ?? "";
        $full_market_value = $propInfo['Full Market Value'] ?? 0;
        $taxes_as_text = getTaxesAsText($full_market_value);

        $beds = $propInfo["# of Bedrooms"] ?? "";
        $baths = $propInfo["# of Baths"] ?? "";

        $prop_type = $propInfo["Property Type"] ?? "";
        $prop_class = ($propInfo["# of Stories"] ?? "") . ' ' . ($propInfo["Home/Building Style"] ?? "");
        $date_bought = $propInfo['Last Sale Date'] ?? "";

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";
        foreach (array_reverse($saleHist)
            as
            [
                'Sale Date' => $date, 'Sale Price' => $price, 'Deed Book' => $db, 'Deed Page' => $dp
            ]) {

            $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
            $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><db>" . $db . "</db><dp>" . $dp . "</dp><m>" . $sale_descrip . "</m></e>";

            if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
        }
        $sale_hist_data = "<r>" . $sale_hist_data . "</r>";


        $structure = [
            'certNo'        =>    $adv_num,
            'auctionID'        =>    NULL,
            'parcelNo'        =>    $parcel_id,
            'alternateID'        =>    $alternate_id,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $ass_value ?? '',
            'appraisedValue'    =>    $full_market_value ?? NULL,
            'propClass'        =>    $prop_class,
            'propType'        =>    $prop_type,
            'propLocation'        =>    $prop_loc,
            'city'            =>    $city,
            'zip'            =>    $zip_code ?? NULL,
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


    function parseTaxPage($target)
    {
        $page = _http($target);
        $headers = $page['headers'];
        $http_status_code = $headers['status_info']['status_code'];

        if ($http_status_code >= 400)
            return FALSE;


        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page['body'], 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        $tax_info = [];


        return [
            'Tax Assess Info'  =>    $tax_info
        ];
    }

    function parsePage($target)
    {
        try {
            $page = _http($target);
            $headers = $page['headers'];
            $http_status_code = $headers['status_info']['status_code'];

            if ($http_status_code >= 400)
                return FALSE;


            $doc = new DOMDocument('1.0', 'utf-8');
            // don't propagate DOM errors to PHP interpreter
            libxml_use_internal_errors(true);
            // converts all special characters to utf-8
            $content = mb_convert_encoding($page['body'], 'HTML-ENTITIES', 'UTF-8');
            $doc->loadHTML($content);

            $location_div = getElementByPath($doc, "/html/body/div[1]/div/div[2]/div[2]/div/div[1]/h4", true);
            [$loc_key, $loc_val] = explode(':', $location_div->nodeValue, 2);
            $prop_table = getElementByPath($doc, "/html/body/div[1]/div/div[3]/div[2]/div[1]/div/table", true);
            $physical_info_table = getElementByPath($doc, "/html/body/div[1]/div/div[3]/div[2]/div[2]/div/table", true);
            $history_table = getElementByPath($doc, "/html/body/div[1]/div/div[3]/div[2]/div[6]/div/table", true);

            $prop_data = parsePropTable($prop_table);
            $info_data = parsePropTable($physical_info_table);
            $saleInfo = parseTableData($history_table);

            return [
                'Property Info'    =>     array_merge($prop_data, $info_data, [$loc_key => $loc_val]),
                'Sale Info'       =>     $saleInfo
            ];
        } catch (Exception | Error $x) {
            return false;
        }
    }

    function parsePropTable($table)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $data_map = [];

        for ($i = 0; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $td_elements = $rows[$i]->getElementsByTagName('td');
            $num_td_elements = $td_elements->count();
            if ($num_td_elements % 2 == 1) continue;

            for ($n = 0; $n < $num_td_elements; $n += 2) {

                $key = trim($td_elements[$n]?->textContent);
                $key = preg_replace('/\s+/', ' ', $key);
                $value = trim($td_elements[$n + 1]?->textContent);

                if (!$key) continue;

                $data_map[$key] = $value;
            }
        }
        return $data_map;
    }

    function parseTableData($table, $useFirstRow_asHeader = true, $header = null)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows); // count only once

        $header_map = [];
        if ($useFirstRow_asHeader) {
            /*** First row determines the column values ***/
            foreach ($rows[0]->getElementsByTagName('th') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
        } else {
            $header_map = $header;
        }

        $data_map = [];

        for ($i = 1; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $td_elements = $rows[$i]->getElementsByTagName('td');
            $num_td_elements = $td_elements->count();
            $row_data = [];

            // Eliminate those rows that do not have the proper structure (i.e. same # of cols)
            if ($num_td_elements !== count($header_map))
                continue;

            for ($n = 0; $n < $num_td_elements; $n++) {
                // add each <td> to the array with the corresponding key based on the header
                $col = $header_map[$n] ?? ""; // in case of undefined index error
                $row_data[$col] = trim($td_elements[$n]->textContent);
            }

            $data_map[] = $row_data;
        }
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