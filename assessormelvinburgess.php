<!DOCTYPE html>
<html>

<body>

    <?php
    require_once 'web-crawler.php';
    require_once 'tax-codes.php';



    // $array = openFile('./TEST FILES/shelby county_tn_TEST FILE.csv');
    // $header = array_shift($array);


    function parseRow(mysqli $conn, $index, $row, $headers, $extra_header, $saveDataToDB)
    {
        /******** SETTINGS *********/
        $state = 'TN';
        /***************************/
        global $err_message, $adv_num, $tax_link, $juris_id;

        try {

            // Remove excess whitespace first with 'keepOnlyDesired' function
            // then remove anything that is not a letter or whitespace (the funny chars)
            $row = array_map('keepOnlyDesired', $row);
            [$adv_num, $parcel_id, $alternate_id, $charge_type, $face_amount, $status, $address] = $row;


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

            $parcel_id = str_ends_with($parcel_id, '0') ? substr($parcel_id, 0, strlen($parcel_id) - 1) : $parcel_id;
            $len = strlen($parcel_id);
            $first_6 = substr($parcel_id, 0, 5);
            $last_6 = substr($parcel_id,  $len - 5);
            $middle = substr($parcel_id, 5, $len - 10);
            $middle = str_replace('0', '%20', $middle);
            $parcel_key = "$first_6$middle$last_6";

            /**************** CREATE THE TAX ASSESSMENT URL *******************/
            $url = "www.assessormelvinburgess.com/propertyDetails?";

            $options = "parcelid=$parcel_key&IR=true";
            $tax_link = $url . $options;

            /******************** TEST BELOW *************************/
            // echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a><br>";
            /*********************************************************/
            // set time limit 
            ini_set('max_execution_time', 9);

            // GO TO THE LINK AND DOWNLOAD THE PAGE
            $parsedPage = parsePage($tax_link);

            if (!$parsedPage || is_empty($parsedPage)) {
                // echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
                $err_message = empty($err_message) ? "No data found on the website" : $err_message;
                return $saveDataToDB($conn, $err_message, $adv_num, $index, $tax_link, false);
            }

            [
                'Property Info' => $propInfo,
                'Sale Info' => $saleHist,
                'Tax Assess Info' => $taxAssessInfo
            ] = $parsedPage;

            $owner_name = $row_owner_name ?? ($propInfo['Owner Name :'] ?? "");
            $owner_type = determineOwnerType($owner_name);
            $prop_loc = $row_address ?? ($propInfo["Property Address:"] ?? "");
            $city = $row_city ?? ($propInfo['Municipal Jurisdiction:'] ?? '');

            $owner_loc = $row_owner_address ?? ($propInfo["Owner Mailing Address:"] ?? "");
            $city_state_zip = $propInfo["Owner City/State/Zip:"] ?? "";
            $arr = preg_split('/\s+/', $city_state_zip);
            $arr_count = count($arr);
            $zip_code = $row_zip ?? (array_pop($arr) ?? "");
            $owner_state = $row_owner_state ?? (array_pop($arr) ?? "");
            $owner_city = join(' ', $arr);

            if ($zip_code) $zip_code = removeAllWhitespace($zip_code);

            $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
            $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

            $total_assessment = $taxAssessInfo['Total Assessment:'] ?? "";
            $assess_value = (int) filter_var($total_assessment, FILTER_SANITIZE_NUMBER_INT);
            $total_appraisal = $taxAssessInfo['Total Appraisal:'] ?? "";
            $appraised_value = (int) filter_var($total_appraisal, FILTER_SANITIZE_NUMBER_INT);
            $taxes_as_text = getTaxesAsText_TN($appraised_value, 0.532);
            $date_bought = $saleHist[0]['Date of Sale'] ?? "";

            // Generate a string of sale entries in XML format
            $sale_hist_data = "";
            foreach ($saleHist
                as
                [
                    'Date of Sale' => $date, 'Sales Price' => $price, 'Instrument Type' => $it
                ]) {

                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><m>" . parseInstrumentType_TN($it) . "</m></e>";

                if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                    $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
            }
            $sale_hist_data = "<r>" . $sale_hist_data . "</r>";


            $beds = intval($propInfo["Bedrooms:"] ?? NULL); // intVal(NULL) returns 0
            $baths = intval($propInfo["Bathrooms :"] ?? NULL);
            $half_baths = intval($propInfo["Half Baths:"] ?? NULL);
            $total_baths = $baths + $half_baths;

            $bldg_descrip = (isset($propInfo["Stories:"]) ? $propInfo["Stories:"] . "-story" : "") . ' ' . ($propInfo["Exterior Walls:"] ?? "");
            $prop_class = $taxAssessInfo['Class:'] ?? "";
            $prop_type = $propInfo['Land Use:'] ?? "";


            $structure = [
                'certNo'        =>    intval($adv_num),
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
                'propLocation'        =>    $prop_loc,
                'city'            =>   $city,
                'zip'            =>    $zip_code,
                'buildingDescrip'    =>    $bldg_descrip,
                'numBeds'        =>    $beds,
                'numBaths'        =>    $total_baths,
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

            // var_dump($structure);
            return $saveDataToDB($conn, $structure, $adv_num,  $index, $tax_link);
        } catch (Throwable $x) {
            $err_message = $x->getMessage() . " Line: " . $x->getLine();
            return $saveDataToDB($conn, $err_message, $adv_num, $index, $tax_link, false);
        }
    }







    function parsePage($target)
    {
        global $err_message;

        $page = _http($target);
        $headers = $page['headers'];
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

        $prop_table = getElementByPath($doc, "/html/body/main/div/div/div/div/div[1]/div[2]/div/table", true);
        $appr_table = getElementByPath($doc, "/html/body/main/div/div/div/div/div[2]/div[2]/div/table", true);
        $improv_table = getElementByPath($doc, "/html/body/main/div/div/div/div/div[3]/div[2]/div/table", true);
        $sales_table = getElementByPath($doc, "/html/body/main/div/div/div/div/div[6]/div[2]/div/table", true);

        $prop_data = parseAttributesTable($prop_table, false, false, 0, 1);
        $appr_data = parseAttributesTable($appr_table, false, false, 0, 1);
        $improv_data = parseAttributesTable($improv_table, false, false, 0, 1);
        $saleInfo = parseTableData($sales_table);

        return [
            'Property Info'    =>     array_merge($prop_data, $improv_data),
            'Sale Info'       =>     $saleInfo,
            'Tax Assess Info'  =>    $appr_data
        ];
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
            foreach ($rows[0]?->getElementsByTagName('th') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
        }

        for ($i = 1; $i < $rows_count; $i++) { // go thru the table starting at row #2
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