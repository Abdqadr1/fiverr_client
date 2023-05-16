<!DOCTYPE html>
<html>

<body>

    <?php
    require_once 'web-crawler.php';
    require_once 'tax-codes.php';



    // $array = openFile('./TEST FILES/genesee county_ny_TEST FILE.csv');
    // $header = array_shift($array);


    function parseRow(mysqli $conn, $index, $row, $headers, $extra_header, $saveDataToDB)
    {
        /******** SETTINGS *********/
        $state = 'NY';
        /***************************/
        global $err_message, $adv_num, $tax_link, $juris_id;
        try {

            // Remove excess whitespace first with 'keepOnlyDesired' function
            // then remove anything that is not a letter or whitespace (the funny chars)
            $row = array_map('keepOnlyDesired', $row);
            [$adv_num, $parcel_id, $alternate_id, $charge_type, $face_amount, $status] = $row;


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


            $qual ??= "";
            $parcelNo = $parcel_id;

            /**************** CREATE THE TAX ASSESSMENT URL *******************/
            $url = "geneseecounty.prosgar.com/PROSParcel/Parcel/";
            $tax_url = "geneseecounty.prosgar.com/PROSParcel/TaxDetails/";
            // $url_1 = "niagaracounty.prosgar.com/PROSParcel/Parcel/";

            $options = "$parcel_id?swis=$alternate_id";

            $link_1 = $url . $options;

            $tax_link_1 = $tax_url . $options;

            /******************** TEST BELOW *************************/
            // echo "<a href='http://" . $link_1 . "'>" . $link_1 . "</a><br>";
            // echo "<a href='http://" . $tax_link_1 . "'>" . $tax_link_1 . "</a><br>";
            /*********************************************************/

            // set time limit 
            ini_set('max_execution_time', 3);


            // GO TO THE LINK AND DOWNLOAD THE PAGE
            $parsedPage = parsePage($link_1);
            $parsedTaxPage = parseTaxPage($tax_link_1);
            if (!$parsedPage || !$parsedTaxPage || is_empty($parsedPage) || is_empty($parsedTaxPage)) {
                // echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
                $err_message = empty($err_message) ? "No data found on the website" : $err_message;
                return $saveDataToDB($conn, $err_message, $adv_num, $index, $tax_link, false);
            }

            [
                'Property Info' => $propInfo,
                'Sale Info' => $saleHist,
                'Tax Assess Info' => $taxAssessInfo,
                'Owners Info' => $owners_info
            ] = $parsedPage;

            ['Tax Data Info' => $taxDataInfo] = $parsedTaxPage;

            $parcel_data = $propInfo['Parcel_data'] ?? '';
            $owner_name = $owners_info[0]['Owner Name'] ?? "";
            $owner_type = determineOwnerType($owner_name);
            for ($o = 1; $o < count($owners_info); $o++) {
                $name = $owners_info[$o]['Owner Name'] ?? "";
                if (!empty($name)) $owner_name .= ', ' . $name;
            }
            $owner_name = $row_owner_name ?? $owner_name;
            $prop_full_loc = $propInfo["Location"] ?? "";
            $prop_full_loc = trim($prop_full_loc);
            $loc_arr = preg_split('/\s*,\s*/', $prop_full_loc);
            $prop_loc = $row_address ?? $loc_arr[0] ?? '';
            $city = $row_city ?? $loc_arr[1] ?? '';
            $zip_code = $row_zip ?? $loc_arr[2] ?? '';

            $owner_state = $row_owner_state ?? $owners_info[0]["State"] ?? "";
            $owner_loc = $row_owner_address ?? $owners_info[0]["Address 1"] ?? "";
            $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
            $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);


            $ass_value = $taxAssessInfo["Total Assessed Value*"] ?? 0;
            $assess_value = (int) filter_var($ass_value, FILTER_SANITIZE_NUMBER_INT);
            $full_market_value = $taxAssessInfo['Full Market Value'] ?? 0;
            $appraised_value = (int) filter_var($full_market_value, FILTER_SANITIZE_NUMBER_INT);
            $country_tax = $taxDataInfo[count($taxDataInfo) - 1]['Tax Amount'] ?? 0;
            $country_tax = (int) filter_var($country_tax, FILTER_SANITIZE_NUMBER_INT);
            $taxes_as_text = getTaxesAsText_NY($country_tax, 0);
            $date_bought = $saleHist[0]['Sale Date'] ?? NULL;

            $beds = intval($propInfo["Number of Bedrooms"] ?? NULL);
            $baths = intval($propInfo["Number of Full Baths"] ?? NULL);
            $half_bath = intval($propInfo["Number of Half Baths"] ?? NULL);

            $prop_class = "";
            $bldg_descrip = (isset($propInfo["Number of Stories"]) ? $propInfo["Number of Stories"] . "-story" : "") . ', ' . ($propInfo["Building Style"] ?? "");
            $prop_type = $propInfo["Property Type"] ?? "";

            // Generate a string of sale entries in XML format
            $sale_hist_data = "";

            foreach ($saleHist
                as
                [
                    'Deed Date' => $date, 'Sale Price' => $price, 'Arms Length' => $valid
                ]) {

                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><m>" . ($valid == 'Yes' ? 'Arms Length Sale' : 'Non-Arms Length Sale') . "</m></e>";

                if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                    $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
            }
            $sale_hist_data = "<r>" . $sale_hist_data . "</r>";




            $structure = [
                'certNo'        =>    intval($adv_num),
                'auctionID'        =>    NULL,
                'parcelNo'        =>    $parcel_data,
                'alternateID'        =>    NULL,
                'chargeType'        =>    $charge_descrip,
                'faceAmnt'        =>    $face_amount,
                'status'        => ($status ? '1' : '0'),
                'assessedValue'        =>    $assess_value,
                'appraisedValue'    =>    $appraised_value,
                'propClass'        =>    $prop_class,
                'propType'        =>    $prop_type,
                'propLocation'        =>    $prop_loc,
                'city'            =>    $city,
                'zip'            =>    $zip_code,
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
                'taxJurisdictionID'    =>    $juris_id
            ];

            //  var_dump($structure);
            return $saveDataToDB($conn, $structure, $adv_num,  $index, $tax_link);
        } catch (Throwable $x) {
            $err_message = $x->getMessage() . " Line: " . $x->getLine();
            return $saveDataToDB($conn, $err_message, $adv_num, $index, $tax_link, false);
        }
    }



    function parseTaxPage($target)
    {
        global $err_message;

        $page = _http($target);
        $headers = $page['headers'];
        $http_status_code = $headers['status_info']['status_code'];
        //var_dump($headers);


        if (!isset($http_status_code) || $http_status_code >= 400) {
            $err_message = $headers['status_info']['status_message'];
            return FALSE;
        }

        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page['body'], 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        $table = $doc->getElementById('parcel-taxes');

        $tax_table_data = parseTableData($table, true, false);


        return [
            'Tax Data Info'  =>    $tax_table_data
        ];
    }

    function parsePage($target)
    {
        global $err_message;

        $page = _http($target);
        $headers = $page['headers'];
        $http_status_code = $headers['status_info']['status_code'];
        //var_dump($headers);


        if (!isset($http_status_code) || $http_status_code >= 400) {
            $err_message = $headers['status_info']['status_message'];
            return FALSE;
        }


        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page['body'], 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);


        $h2_tags = $doc->getElementsByTagName('h2');
        $build_prop_table = $doc->getElementById('residential_building_hdr');
        $assess_div = $doc->getElementById('assessment_hdr');
        $prop_desc_div = $doc->getElementById('property_description_hdr');
        $assess_table = $assess_div->getElementsByTagName('table')[0];
        $prop_desc_table = $prop_desc_div->getElementsByTagName('table')[0];
        $owners_table = $doc->getElementById('parcel-owners');
        $sales_history_table = $doc->getElementById('parcel-sales');
        [$d, $prop_loc, $parcel_no, $swis] = explode(' - ', $h2_tags[0]->nodeValue, 4);


        $prop_data = ['Location' => $prop_loc, 'Parcel_data' => $parcel_no];


        $building_data = parseAttributesTable($build_prop_table, true, false, 0, 1);
        $assess_data = parseAttributesTable($assess_table, false, false, 0, 1);
        $prop_desc_data = parseAttributesTable($prop_desc_table, false, false, 0, 1);
        $owners_info = parseTableData($owners_table);
        $saleInfo = parseTableData($sales_history_table);

        return [
            'Property Info'    =>     array_merge($prop_data, $building_data, $prop_desc_data),
            'Sale Info'       =>     $saleInfo,
            'Owners Info' => $owners_info,
            'Tax Assess Info'  =>    $assess_data
        ];
    }

    function parseOwnerTd($td)
    {
        $data = [];
        if (!$td || !$td instanceof DOMElement) return $data;
        $arr = explode('<br>', innerHTML($td), 4);

        $count = count($arr);
        // $arr = explode("\n", $owner_td->nod, 3);
        $data["owner_name"] = ($count > 3) ? $arr[0] . ' ' . $arr[1] : $arr[0];
        $data['owner_street'] = ($count > 3) ? $arr[2] : $arr[1];
        $data['city_state'] = ($count > 3) ? $arr[3] : $arr[2];
        return $data;
    }

    function parseTableData($table, $useFirstRow_asHeader = true, $ignore_first_row = true)
    {
        $data_map = [];
        if (!$table || !$table instanceof DOMElement) return $data_map;

        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $startIndex = $ignore_first_row ? 1 : 0;

        $header_map = [];
        if ($useFirstRow_asHeader) {
            /*** First row determines the column values ***/
            foreach ($rows[$startIndex]?->getElementsByTagName('th') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
        }


        for ($i = ++$startIndex; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $td_elements = $rows[$i]?->getElementsByTagName('td');
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

    function parseAttributesTable($table, $removeFirstRow = false, $is_key_th = true, $key_i = 0, $val_i = 0)
    {
        // $index of td that contains the key
        $data_map = [];
        if (!$table || !$table instanceof DOMElement) return $data_map;

        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $start = $removeFirstRow ? 1 : 0;

        for ($i = $start; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $tds = $rows[$i]?->getElementsByTagName('td');
            $td = $tds[$val_i];


            $th = ($is_key_th) ? $rows[$i]?->getElementsByTagName('th')[$key_i] : $tds[$key_i];



            if (!$td || !$th) continue;

            $key = trim($th->nodeValue);
            $val = trim($td->nodeValue);

            $data_map[$key] = $val;
        }
        return $data_map;
    }

    function parseValueTable($table, $removeFirstRow = true)
    {

        $data_map = [];
        if (!$table || !$table instanceof DOMElement) return $data_map;
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $start = $removeFirstRow ? 1 : 0;

        for ($i = $start; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $td = $rows[$i]?->getElementsByTagName('td')[0];
            $th = $rows[$i]?->getElementsByTagName('th')[0];

            if (!$td || !$th) continue;

            $key = trim($th->textContent);
            $val = trim($td->textContent);
            $data_map[$key] = $val;
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