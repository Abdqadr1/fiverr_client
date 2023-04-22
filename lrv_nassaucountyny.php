<!DOCTYPE html>
<html>

<body>

    <?php
    require_once 'web-crawler.php';
    require_once 'tax-codes.php';

    /******** SETTINGS *********/
    $state = 'NY';
    /***************************/


    $array = openFile('./TEST FILES/nassau county_ny_TEST FILE.csv');
    $header = array_shift($array);


    for ($i = 0; $i < 5; $i++) { //count($array)

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
        [$section, $block, $lot, $suffix, $condo, $unit] = explode('-', $parcel_id, 6);
        $section = str_pad($section, 2, '0', STR_PAD_LEFT);
        $block = is_numeric($section) ? str_pad($block, 3, '0', STR_PAD_LEFT) : '++' . $block;
        $suffix = $suffix ?? '0';
        $lot = empty($lot) ? '' : str_pad($lot, 4, '0', STR_PAD_LEFT) . $suffix;
        $unit = empty($unit) ? '' : str_pad($unit, 5, '0', STR_PAD_LEFT);

        /**************** CREATE THE TAX ASSESSMENT URL *******************/
        $url = "lrv.nassaucountyny.gov/info/";

        $options = "$section$block++$lot$condo$unit/";
        $link = $url . $options;


        /******************** TEST BELOW *************************/
        echo "<a href='http://" . $link . "'>" . $link . "</a><br>";
        /*********************************************************/

        // GO TO THE LINK AND DOWNLOAD THE PAGE

        $parsedPage = parsePage($link);
        if (!$parsedPage) {
            echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
            continue;
        }

        [
            'Property Info' => $propInfo,
            'Sale Info' => $saleHist,
            'Tax Assess Info' => $taxAssessInfo
        ] = $parsedPage;

        $owner_name = $propInfo['Owner Name'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $owner_loc = $propInfo["Owner Address"] ?? "";
        $owner_state = "";

        $prop_loc = $propInfo["Location"] ?? "";
        $prop_city_zip = $propInfo["city_zip"] ?? "";
        [$city, $zip_code] = explode(',', $prop_city_zip, 2);
        $city = trim($city);
        $zip_code = trim($zip_code);
        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

        $taxes_as_text = getTaxesAsText_NY(0, 0);
        $date_bought = $saleHist[0]['Date of Sale'] ?? "";
        $ass_value = $taxAssessInfo["Final Assessed/Tentative Assessed Value"] ?? "";
        $appr_value = $taxAssessInfo["Fair Market Values"] ?? "";
        $assess_value = (int) filter_var($ass_value, FILTER_SANITIZE_NUMBER_INT);
        $appraised_value = (int) filter_var($appr_value, FILTER_SANITIZE_NUMBER_INT);

        $beds = $baths = $half_baths = 0;
        if (isset($propInfo["Number of Bedrooms"]))
            $beds = intval($propInfo["Number of Bedrooms"]);
        if (isset($propInfo["Full Bathrooms"]))
            $baths = intval($propInfo["Full Bathrooms"]);
        if (isset($propInfo["Half Bathrooms"]))
            $half_baths = intval($propInfo["Half Bathrooms"]);
        $total_baths = $baths + $half_baths;

        $prop_class = $propInfo["Land Category"] ?? "";
        $prop_type = $propInfo["Land Title"] ?? "";

        $bldg_descrip = (isset($propInfo["Story Height"]) ? $propInfo["Story Height"] . "-story" : "") . ' ' . ($propInfo["Style"] ?? "");

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";
        foreach (array_reverse($saleHist)
            as
            [
                'Date of Sale' => $date, 'Sale Price' => $price, 'Condition of Transfer' => $msg
            ]) {

            $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><m>" . $msg . "</m></e>";

            if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
        }
        $sale_hist_data = "<r>" . $sale_hist_data . "</r>";



        $structure = [
            'certNo'        =>    intval($adv_num),
            'auctionID'        =>    NULL,
            'parcelNo'        =>    $parcel_id,
            'alternateID'        =>    $alternate_id,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $assess_value,
            'appraisedValue'    =>    $appraised_value,
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
        // var_dump($structure);
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

            $address_div = getElementByPath($doc, "/html/body/div[2]/div/div[4]/div[1]/div[2]/section[1]/div[1]/div", true);
            $block_div_1 = getElementByPath($doc, "/html/body/div[2]/div/div[4]/div[1]/div[2]/section[2]/div[1]", true);
            $block_div_2 = getElementByPath($doc, "/html/body/div[2]/div/div[4]/div[1]/div[2]/section[2]/div[2]", true);
            $tax_info_table = getElementByPath($doc, "/html/body/div[2]/div/div[4]/div[2]/section/div/div/div[1]/div/table", true);
            $tab_content_div = getElementByPath($doc, "/html/body/div[2]/div/div[4]/div[2]/section/div/div/div[4]/div/div[1]/div/div", true);
            $tables = $tab_content_div->getElementsByTagName('table');
            $latest_desc_table = $tables[0];
            $recent_sales_table = getElementByPath($doc, "/html/body/div[2]/div/div[4]/div[2]/section/div/div/div[5]/div/table", true);
            $inventory_table_1 = getElementByPath($doc, "/html/body/div[2]/div/div[4]/div[2]/section/div/div/div[4]/div/div[2]/div[2]/table", true);
            $inventory_table_2 = getElementByPath($doc, "/html/body/div[2]/div/div[4]/div[2]/section/div/div/div[4]/div/div[2]/div[3]/table", true);

            $address = explode(':', $address_div->nodeValue, 2)[1] ?? '';
            @[$street_add, $city_zip] = explode('.', $address, 2);

            $block_1_data = parseBlockData($block_div_1);
            $block_2_data = parseBlockData($block_div_2);
            $desc_data = parseDescTable($latest_desc_table);
            $recent_sales_data = parseTableData($recent_sales_table);
            $tax_info_data = parseAttributesTable($tax_info_table, false, false, 0, 1);
            $inventory_1_data = parseAttributesTable($inventory_table_1, false, true, 0, 0);
            $inventory_2_data = parseAttributesTable($inventory_table_2, false, true, 0, 0);


            return [
                'Property Info'    =>     array_merge(
                    $block_1_data,
                    $block_2_data,
                    $desc_data,
                    $inventory_1_data,
                    $inventory_2_data,
                    ['Location' => $street_add, 'city_zip' => $city_zip]
                ),
                'Sale Info'       =>     $recent_sales_data,
                'Tax Assess Info' => $tax_info_data,
            ];
        } catch (Exception | Error $x) {
            return false;
        }
    }
    function parseDescTable($table)
    {
        $data_map = [];
        if (!$table || !$table instanceof DOMElement) return $data_map;

        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);


        for ($i = 0; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $tds = $rows[$i]->getElementsByTagName('td');
            $ths = $rows[$i]->getElementsByTagName('th');

            $td_count = count($tds);

            if ($td_count !== count($ths)) continue;

            for ($j = 0; $j < $td_count; $j++) {
                $key = trim($ths[$j]->nodeValue);
                $val = trim($tds[$j]->nodeValue);

                if (!$key || empty($key)) continue;
                $data_map[$key] = $val;
            }
        }
        return $data_map;
    }

    function parseBlockData($div)
    {
        $data = [];
        if (!$div || !$div instanceof DOMElement) return $data;

        $divs = $div->getElementsByTagName('div');
        $count = count($divs);

        if ($count < 1) return $data;

        for ($i = 0; $i < $count; $i++) {
            $text = $divs[$i]->nodeValue;
            if (!str_contains($text, ':')) continue;

            [$key, $val] = explode(':', $text, 2);
            $data[$key] = $val;
        }

        return $data;
    }

    function parseAttributesTable($table, $removeFirstRow = false, $is_key_th = true, $key_i = 0, $val_i = 0)
    {
        $data_map = [];
        if (!$table || !$table instanceof DOMElement) return $data_map;

        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $start = $removeFirstRow ? 1 : 0;

        for ($i = $start; $i < $rows_count; $i++) {
            $tds = $rows[$i]->getElementsByTagName('td');
            if (count($tds) < 1) continue;

            $td = $tds[$val_i];
            $th = ($is_key_th) ? $rows[$i]->getElementsByTagName('th')[$key_i] : $tds[$key_i];

            if (!$td || !$th) continue;

            $key = trim($th->nodeValue);
            $val = trim($td->nodeValue);

            $data_map[$key] = $val;
        }
        return $data_map;
    }

    function parseTableData($table, $useFirstRow_asHeader = true)
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