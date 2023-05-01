<!DOCTYPE html>
<html>

<body>

    <?php
    require_once 'web-crawler.php';
    require_once './dynamic_crawler.php';
    require_once 'tax-codes.php';

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


        $swiss = $alternate_id;
        $print_key = $parcel_id;
        $options = "printkey=$print_key&swis=$swiss";
        $opt = "$swiss-$print_key/current";
        $link = $url . $options;
        $tax_link = $tax_url . $opt;


        /******************** TEST BELOW *************************/
        echo "<a href='http://" . $link . "'>" . $link . "</a><br>";
        echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a><br>";
        /*********************************************************/


        // GO TO THE LINK AND DOWNLOAD THE PAGE

        $parsedTaxPage = parseTaxPage($tax_link);
        $parsedPage = parsePage($link);
        if (!$parsedPage || !$parsedTaxPage) {
            echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
            continue;
        }

        [
            'Property Info' => $propInfo,
            'Sale Info' => $saleHist
        ] = $parsedPage;

        ['Tax Assess Info' => $taxAssessInfo] = $parsedTaxPage;


        $owner_name = $propInfo['Owner Name'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $p_loc = $propInfo["LOCATION"] ?? "";
        [$prop_loc, $city] = preg_split('/\s*,\s*/', $p_loc);

        $owner_loc = ($propInfo["Mailing Address 1"] ?? "") . ' ' . ($propInfo["Mailing Address 2"] ?? "");
        $city_state = $propInfo["Mailing City, State"] ?? "";

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        @[$owner_city, $owner_state] = preg_split('/\s+/', $city_state, 2);
        $owner_city = str_replace(',', '', $owner_city);
        $zip_code = $propInfo['Mailing ZIP Code'] ?? "";
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);


        $ass_value = $propInfo['Total Assessed Value (100.00% Market)'] ?? "";
        $assess_value = (int) filter_var($ass_value, FILTER_SANITIZE_NUMBER_INT);
        $full_market_value = $propInfo['Full Market Value'] ?? 0;
        $appraised_value = (int) filter_var($full_market_value, FILTER_SANITIZE_NUMBER_INT);
        $town_tax = $taxAssessInfo['1_Amount Due:'] ?? 0;
        $town_tax = (int) filter_var($town_tax, FILTER_SANITIZE_NUMBER_INT);
        $school_tax = $taxAssessInfo['5_Amount Due:'] ?? 0;
        $school_tax = (int) filter_var($school_tax, FILTER_SANITIZE_NUMBER_INT);
        $taxes_as_text = getTaxesAsText_NY($town_tax, $school_tax);

        $beds = intval($propInfo["# of Bedrooms"] ?? NULL);
        $baths = intval($propInfo["# of Baths"] ?? NULL);

        $prop_type = $propInfo["Property Type"] ?? "";
        $prop_class = "";
        $bldg_descrip = (isset($propInfo["# of Stories"]) ? $propInfo["# of Stories"] . "-story" : "") . ' ' . ($propInfo["Home/Building Style"] ?? "");
        $date_bought = $propInfo['Last Sale Date'] ?? "";

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";
        foreach ($saleHist
            as
            [
                'Sale Date' => $date, 'Sale Price' => $price, 'Owner History' => $buyer, 'Valid Sale' => $valid
            ]) {

            $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . $buyer . "</b><m>" . ($valid == 'YES' ? 'Valid Sale' : 'NOT a valid sale') . "</m></e>";

            if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
        }
        $sale_hist_data = "<r>" . $sale_hist_data . "</r>";


        $structure = [
            'certNo'        =>    $adv_num,
            'auctionID'        =>    NULL,
            'parcelNo'        =>    $parcel_id,
            'alternateID'        =>    NULL,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $assess_value,
            'appraisedValue'    =>    $appraised_value,
            'propClass'        =>    $prop_class,
            'propType'        =>    $prop_type,
            'propLocation'        =>   $prop_loc,
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

        // listData($structure);
        var_dump($structure);
    }


    function parseTaxPage($target)
    {
        try {
            $required_element_path = "/html/body/div/div/div[3]/div/div[5]/h6[2]";
            $page = _dynamicCrawler($target, 10, $required_element_path);

            $doc = new DOMDocument('1.0', 'utf-8');
            // don't propagate DOM errors to PHP interpreter
            libxml_use_internal_errors(true);
            // converts all special characters to utf-8
            $content = mb_convert_encoding($page, 'HTML-ENTITIES', 'UTF-8');
            $doc->loadHTML($content);

            $all_data_div = getElementByPath($doc, "MuiGrid-root MuiGrid-container MuiGrid-spacing-xs-2");
            $all_data_div_count = $all_data_div->count();

            $tax_data = [];

            for ($t = 0; $t < $all_data_div_count; $t++) {
                $tax_data = array_merge($tax_data, parseTaxData($all_data_div->item($t), $t . '_'));
            }


            return [
                'Tax Assess Info'  =>    $tax_data
            ];
        } catch (Exception | Error $x) {
            return false;
        }
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
            [$loc_key, $loc_val] = explode(':', trim($location_div->nodeValue), 2);
            $prop_table = getElementByPath($doc, "/html/body/div[1]/div/div[3]/div[2]/div[1]/div/table", true);
            $physical_info_table = getElementByPath($doc, "/html/body/div[1]/div/div[3]/div[2]/div[2]/div/table", true);
            $history_table = getElementByPath($doc, "/html/body/div[1]/div/div[3]/div[2]/div[6]/div/table", true);

            $prop_data = parsePropTable($prop_table);
            $info_data = parsePropTable($physical_info_table);
            $saleInfo = parseTableData($history_table);

            return [
                'Property Info'    =>     array_merge($prop_data, $info_data, [$loc_key => trim($loc_val)]),
                'Sale Info'       =>     $saleInfo
            ];
        } catch (Exception | Error $x) {
            return false;
        }
    }

    function parsePropTable($table)
    {
        $data_map = [];
        if (!$table || !$table instanceof DOMElement) return $data_map;

        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);


        for ($i = 0; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $td_elements = $rows[$i]->getElementsByTagName('td');
            $num_td_elements = $td_elements->count();
            if ($num_td_elements % 2 == 1) continue;

            for ($n = 0; $n < $num_td_elements; $n += 2) {

                $key = trim($td_elements[$n]->textContent);
                $key = preg_replace('/\s+/', ' ', $key);
                $value = trim($td_elements[$n + 1]->textContent);

                if (!$key) continue;

                $data_map[$key] = $value;
            }
        }
        return $data_map;
    }

    function parseTableData($table, $useFirstRow_asHeader = true, $header = null)
    {
        $data_map = [];
        if (!$table || !$table instanceof DOMElement) return $data_map;

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

    function parseTaxData($parent, $key_prefix = '_')
    {
        $data = [];
        if (!$parent || !$parent instanceof DOMElement) return $data;
        $ps = $parent->getElementsByTagName('p');

        $ps_count = count($ps);

        if ($ps_count % 2 == 1) {
            return $data;
        }

        for ($i = 0; $i < $ps_count; $i += 2) {
            $key = trim($ps[$i]->nodeValue);
            $val = trim($ps[$i + 1]->nodeValue);

            $data[$key_prefix . $key] = $val;
        }


        return $data;
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