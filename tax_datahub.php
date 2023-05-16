<!DOCTYPE html>
<html>

<body>

    <?php
    include 'dynamic_crawler.php';
    include 'tax-codes.php';


    // $array = openFile("./TEST FILES/essex_county_TEST_FILE.csv");
    // $header = array_shift($array);
    function parseRow(mysqli $conn, $index, $row, $headers, $extra_header, $saveDataToDB)
    {
        /******** SETTINGS *********/
        $state = 'NJ';
        /***************************/
        global $err_message, $adv_num, $tax_link, $juris_id;

        try {
            // Remove excess whitespace first with 'keepOnlyDesired' function
            // then remove anything that is not a letter or whitespace (the funny chars)
            $row = array_map('keepOnlyDesired', $row);
            [$adv_num, $parcel_id, $alternate_id, $charge_type, $face_amount, $status, $town] = $row;


            // for extra headers
            $row_address = isset($extra_header["prop_location"]) ? $row[$extra_header["prop_location"]] : null;
            $row_owner_name = isset($extra_header["last_recorded_owner"]) ? $row[$extra_header["last_recorded_owner"]] : null;
            $row_owner_address = isset($extra_header["last_recorded_owner_address"]) ? $row[$extra_header["last_recorded_owner_address"]] : null;
            $row_owner_state = isset($extra_header["last_recorded_owner_state"]) ? $row[$extra_header["last_recorded_owner_state"]] : null;
            $row_zip = isset($extra_header["zip"]) ? $row[$extra_header["zip"]] : null;
            $row_city = isset($extra_header["city"]) ? $row[$extra_header["city"]] : null;


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



            /*************** PARSE THE BLOCK, LOT ******************/
            $parcel_array = explode("-", $parcel_id, 2);
            $block = $parcel_array[0] ?? "";
            $lot_qual = $parcel_array[1] ?? "";

            $lot_qual_array = explode("--", $lot_qual, 2);
            $lot = $lot_qual_array[0] ?? "";
            $qual = $lot_qual_array[1] ?? "";

            /**************** CREATE THE TAX ASSESSMENT URL *******************/
            $tax_link = getLink();

            /******************** TEST BELOW *************************/
            // echo "<a href='https://" . $tax_link . "'>" . $tax_link . "</a><br>";
            /*********************************************************/

            // set time limit 
            ini_set('max_execution_time', 3);

            // GO TO THE LINK AND DOWNLOAD THE PAGE
            $parsedPage = parsePage($tax_link, $block, $lot, $qual, $town);
            if (!$parsedPage || is_empty($parsedPage)) {
                // echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
                $err_message = empty($err_message) ? "No data found on the website" : $err_message;
                return $saveDataToDB($conn, $err_message, $adv_num, $index, $tax_link, false);
            }

            [
                'Property Info' => $propInfo,
                'Sale Info' => $saleHist,
                'Tax Assess Info' => $taxAssessInfo,
            ] = $parsedPage;

            $prop_loc = $row_address ?? $propInfo["Property Location:"] ?? "";
            $owner_name = $row_owner_name ?? $propInfo['Name:'] ?? "";
            $owner_type = determineOwnerType($owner_name);
            $owner_loc = $row_owner_address ?? $propInfo["Street:"] ?? "";
            $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
            $city_state = $propInfo["City&State:"] ?? "";
            [$owner_city, $owner_state] = preg_split("/\s*,\s*/", $city_state, 2);
            $owner_city = $row_city ?? $owner_city;
            $owner_state = $row_owner_state ?? $owner_state;
            $owner_zip = $row_zip ?? $propInfo["Zip:"] ?? "";
            $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

            $total_appraised_value = $propInfo['Net Tax Value:'] ?? 0;
            $total_appraised_value = (int) filter_var($total_appraised_value, FILTER_SANITIZE_NUMBER_INT);
            $taxes_as_text = getTaxesAsText_NJ($total_appraised_value);

            $date_bought = $saleHist[0]['Date'] ?? NULL;

            $land_description = $propInfo["Land Description:"] ?? "";
            $prop_class = $propInfo["Property Class Code:"] ?? "";
            [$prop_class, $prop_type] = getPropTypeFromClass($prop_class);


            // Generate a string of sale entries in XML format
            $sale_hist_data = "";
            foreach (array_reverse($saleHist) as ['Date' => $date, 'Price' => $price]) {

                $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><m>" . $sale_descrip . "</m>
            <db>" . ($taxAssessInfo['Deed Book:'] ?? '') . "</db><dp>" . ($taxAssessInfo['Deed Page:'] ?? '') . "</dp></e>";
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
                'assessedValue'        =>    NULL,
                'appraisedValue'    =>    $total_appraised_value,
                'propClass'        =>    $prop_class,
                'propType'        =>    $prop_type,
                'propLocation'        =>    $prop_loc,
                'city'            =>    $owner_city,
                'zip'            =>    $owner_zip,
                'buildingDescrip'    =>    $land_description,
                'numBeds'        =>    NULL,
                'numBaths'        =>    NULL,
                'lastRecordedOwner'    =>    $owner_name,
                'lastRecordedOwnerType'    =>    $owner_type,
                'lastRecordedDateOfSale' =>    date('Y-m-d', strtotime($date_bought)),
                'absenteeOwner'        => ($absentee_owner ? '1' : '0'),
                'livesInState'        => ($lives_in_state ? '1' : '0'),
                'saleHistory'        =>    $sale_hist_data,
                'priorDelinqHistory'    =>    NULL,
                'propertyTaxes'        =>    $taxes_as_text,
                'taxJurisdictionID'    =>    $juris_id
            ];

            return $saveDataToDB($conn, $structure, $adv_num,  $index, $tax_link);
        } catch (Throwable $x) {
            $err_message = $x->getMessage() . " Line: " . $x->getLine();
            return $saveDataToDB($conn, $err_message, $adv_num, $index, $tax_link, false);
        }
    }

    function getLink($county = 'essex')
    {
        switch (strtolower($county)) {
            case 'burlington':
                return "https://www.taxdatahub.com/623af8995103551060110abc/Burlington%20County";
            case 'camden':
                return "https://www.taxdatahub.com/60d088c3d3501df3b0e45ddb/Camden%20County";
            case 'middlesex':
                return "https://www.taxdatahub.com/623085dd284c51d4d32ff9fe/Middlesex%20County";
            default:
                return "https://www.taxdatahub.com/6229fbf0ce4aef911f9de7bc/Essex%20County";
        }
    }



    function parsePage($target, $block, $lot, $qual, $town)
    {
        $search_results_path = "/html/body/div[1]/div[2]/div[2]/div[4]/div/div/table[1]/tbody/tr[1]/td[1]/a";
        $page = dataHubCrawler($target, 40, $search_results_path, $town, $block, $lot, $qual);

        if (!$page)
            return FALSE;

        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page, 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        $tables = $doc->getElementsByTagName('table');
        $owner_table = $tables->item(2);
        $prop_table = $tables->item(3);
        $sale_table = $tables->item(4);
        $table_headers = getElementByPath($doc, "tabulator-headers");
        $table_bodies = getElementByPath($doc, "tabulator-table");

        $owner_data = parseAttributesTable($owner_table, false, false, 0, 1);
        $prop_data = parseAttributesTable($prop_table, false, false, 0, 1);
        $sale_data = parseAttributesTable($sale_table, false, false, 0, 1);

        $sale_history_data = parseTableData($table_headers->item(1), $table_bodies->item(1));

        return [
            'Property Info'    =>     array_merge($owner_data, $prop_data),
            'Sale Info'       =>     $sale_history_data,
            'Tax Assess Info'  =>    $sale_data,
        ];
    }

    function parseAttributesTable($table, $removeFirstRow = false, $is_key_th = true, $key_i = 0, $val_i = 0)
    {
        $data_map = [];
        if (!$table || !$table instanceof DOMElement) return $data_map;

        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $start = $removeFirstRow ? 1 : 0;

        for ($i = $start; $i < $rows_count; $i++) {
            $tds = $rows[$i]?->getElementsByTagName('td');
            if (count($tds) < 1) continue;

            $td = $tds[$val_i];
            $th = ($is_key_th) ? $rows[$i]?->getElementsByTagName('th')[$key_i] : $tds[$key_i];

            if (!$td || !$th) continue;

            $key = trim($th->nodeValue);
            $val = trim($td->nodeValue);

            $data_map[$key] = $val;
        }
        return $data_map;
    }

    function parseTableData($headers_div, $body_div)
    {
        $data_map = [];
        if (!$headers_div || !$body_div) return $data_map;

        $header_map = [];
        foreach ($headers_div?->getElementsByTagName('div') as $col) {
            if ($col->getAttribute('role') == "columnheader")
                $header_map[] = trim($col->textContent);
        }

        $rows_div = array();

        foreach ($body_div?->getElementsByTagName('div') as $col) {
            if ($col->getAttribute('role') == "gridcell")
                $rows_div[] = $col;
        }

        $rows_count = count($rows_div);
        $head_count = count($header_map);

        for ($i = 0; $i < $rows_count; $i += $head_count) {
            $row_data = [];

            for ($n = 0; $n < $head_count; $n++) {
                $col = $header_map[$n] ?? "";
                $val = $rows_div[$i + $n]->nodeValue;
                $row_data[$col] = trim($val);
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