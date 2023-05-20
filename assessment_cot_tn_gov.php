<!DOCTYPE html>
<html>

<body>

    <?php
    require_once 'web-crawler.php';
    require_once 'tax-codes.php';


    // $array = openFile("./TEST FILES/gibson_tn_TEST FILE.csv");
    // $header = array_shift($array); // remove the first element from the array
    //$header_map = array_map( 'keepOnlyDesired', $header );


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
            [$adv_num, $parcel_str, $alternate_id, $charge_type, $face_amount, $status] = $row;


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

            $parcel_array = preg_split('/\s+/', $parcel_str, 4);
            $jur = $parcel_array[0] ?? "";
            $control_map = $parcel_array[1] ?? "";
            $group = $parcel_array[2] ?? "";
            $parcel = $parcel_array[3] ?? "";
            $parcel = str_replace('.', '', $parcel);
            $jur = str_pad($jur, 3, '0', STR_PAD_LEFT);
            $identifier = "%20";
            $special_interest = "000";
            $group ??= "%20";
            $group = empty($group) ? "%20" : $group;

            /**************** CREATE THE TAX ASSESSMENT URL *******************/
            $url = "assessment.cot.tn.gov/TPAD/Parcel/Details?&";
            $parcel_id = "$control_map%20$group%20$parcel$identifier$special_interest";
            $parcel_key = "$jur$control_map%20$group%20$parcel$identifier$special_interest";

            $options = "parcelId=$parcel_id&jur=$jur&parcelKey=$parcel_key";
            $tax_link = $url . $options;

            /******************** TEST BELOW *************************/
            // echo "<a href='https://" . $tax_link . "'>" . $tax_link . "</a><br>";
            /*********************************************************/

            // set time limit 
            ini_set('max_execution_time', 3);

            // GO TO THE LINK AND DOWNLOAD THE PAGE

            $parsedPage = parsePage($tax_link);

            if (!$parsedPage || is_empty($parsedPage)) {
                // echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
                $err_message = empty($err_message) ? "No data found on the website" : $err_message;
                return ["error" => $err_message];
            }

            [
                'Property Info' => $propInfo,
                'Sale Info' => $saleHist,
                'Tax Assess Info' => $taxAssessInfo
            ] = $parsedPage;

            $owner_name = $row_owner_name ?? ($propInfo['owner'] ?? "");
            $owner_type = determineOwnerType($owner_name);
            $prop_loc = $row_address ?? ($propInfo["prop_address"] ?? "");

            $owner_loc = $row_owner_address ?? ($propInfo["owner_street"] ?? "");
            $city_state = $propInfo["city_state"] ?? "";

            $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
            $arr = preg_split('/\s+/', $city_state);
            $arr_count = count($arr);
            $owner_zip_code = array_pop($arr) ?? "";
            $owner_state = $row_owner_state ?? (array_pop($arr) ?? "");
            $city = $row_city ?? ($propInfo['City'] ?? '');
            $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

            $appr_value = $taxAssessInfo['Total Market appraised:'] ?? 0;
            $appraised_value = (int) filter_var($appr_value, FILTER_SANITIZE_NUMBER_INT);
            $ass_value = $taxAssessInfo["Assessment:"] ?? 0;
            $assess_value = (int) filter_var($ass_value, FILTER_SANITIZE_NUMBER_INT);
            $taxes_as_text = getTaxesAsText_TN($assess_value, 0.416);

            $improvement_type = $taxAssessInfo['Improvement Type'] ?? '';
            $prop_class = $propInfo["Class"] ?? "";

            $date_bought = $saleHist[0]['Sale Date'] ?? NULL;


            // Generate a string of sale entries in XML format
            $sale_hist_data = "";

            foreach ($saleHist
                as
                [
                    'Sale Date' => $date, 'Price' => $price,
                    'Type Instrument' => $instrument, 'Qualification' => $qualification
                ]) {

                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . "N/A" . "</b><m>" . $instrument . ", " . $qualification . "</m></e>";

                if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                    $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
            }
            $sale_hist_data = "<r>" . $sale_hist_data . "</r>";



            $structure = [
                'certNo'        =>    intval($adv_num),
                'auctionID'        =>    NULL,
                'parcelNo'        =>    urldecode($parcel_id),
                'alternateID'        =>    NULL,
                'chargeType'        =>    $charge_descrip,
                'faceAmnt'        =>    $face_amount,
                'status'        => ($status ? '1' : '0'),
                'assessedValue'        =>    $assess_value,
                'appraisedValue'    =>    $appraised_value,
                'propClass'        =>    $prop_class,
                'propType'        =>    $improvement_type,
                'propLocation'        =>    $prop_loc,
                'city'            =>    $city,
                'zip'            =>    $row_zip,
                'buildingDescrip'    =>    NULL,
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

            // var_dump($structure);
            return ['data' => $structure];
        } catch (Throwable $x) {
            $err_message = $x->getMessage() . " Line: " . $x->getLine();
            return ["error" => $err_message];
        }
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

        $ownerDiv = getElementByPath($doc, "/html/body/div/main/div/div[2]/div[2]/div[1]/div/div[2]/div/div", true);
        $propAddress = getElementByPath($doc, "//html/body/div/main/div/div[2]/div[2]/div[2]/div/div[2]/div[1]/div/p", true)
            ->nodeValue ?? "";
        // $propDiv = getElementByPath($doc, "/html/body/div/main/div/div[2]/div[2]/div[2]/div/div[2]", true);
        $valueDiv = getElementByPath($doc, "/html/body/div/main/div/div[2]/div[3]/div[1]/div/div[2]/div", true);
        $generalDiv1 = getElementByPath($doc, "/html/body/div/main/div/div[2]/div[4]/div/div/div[2]/div/div[1]", true);
        $generalDiv2 = getElementByPath($doc, "/html/body/div/main/div/div[2]/div[4]/div/div/div[2]/div/div[2]", true);

        $buildingInfoDiv1 = getElementByPath($doc, "/html/body/div/main/div/div[2]/div[5]/div/div/div[2]/div[2]/div[1]", true);
        $buildingInfoDiv2 = getElementByPath($doc, "/html/body/div/main/div/div[2]/div[5]/div/div/div[2]/div[2]/div[2]", true);

        $saleInfoTable = getElementByPath($doc, "/html/body/div/main/div/div[2]/div[7]/div/div/div[2]/div/table", true);


        $ownerInfo = parseOwnerDiv($ownerDiv);
        $valueInfo = parseValueDiv($valueDiv);
        $generalInfo1 = parseGeneralDiv($generalDiv1);
        $generalInfo2 = parseGeneralDiv($generalDiv2);

        $buildingInfo1 = parseGeneralDiv($buildingInfoDiv1);
        $buildingInfo2 = parseGeneralDiv($buildingInfoDiv2);

        $saleInfo = parseTableData($saleInfoTable);
        $prop_arr = explode(':', $propAddress, 2);
        $prop_address = preg_replace('/\s+/', '', $prop_arr[1] ?? '');

        return [
            'Property Info'    =>     array_merge($ownerInfo, ['prop_address' => $prop_address], $generalInfo1, $generalInfo2),
            'Sale Info'       =>     $saleInfo,
            'Tax Assess Info'  =>    array_merge($buildingInfo1, $buildingInfo2, $valueInfo)
        ];
    }

    function parseOwnerDiv($div)
    {
        $data_map = [];
        if (!$div || !$div instanceof DOMElement) return $data_map;
        $divs = $div->getElementsByTagName('div');

        $startIndex = 0;

        $val = trim($divs[++$startIndex]->nodeValue);
        $data_map['owner'] = $val ?? "";
        if (str_contains($val, '&')) {
            $val = trim($divs[++$startIndex]?->nodeValue);
            $data_map['owner'] = $data_map['owner'] . ' ' . $val;
        }

        $val = trim($divs[++$startIndex]?->nodeValue);
        $data_map['owner_street'] = $val ?? "";

        $val = trim($divs[$startIndex + 2]?->nodeValue);
        $data_map['city_state'] = $val ?? "";


        return $data_map;
    }

    function parseValueDiv($div)
    {
        $data_map = [];
        if (!$div || !$div instanceof DOMElement) return $data_map;

        $divs = $div->getElementsByTagName('div');

        if ($divs->length > 0) {

            for ($i = 0; $i < $divs->length; $i += 2) {
                $key = trim($divs->item($i)->nodeValue ?? "");
                $val = trim($divs->item($i + 1)->nodeValue ?? "");

                $data_map[$key] = $val;
            }
        }
        return $data_map;
    }

    function parseGeneralDiv($div)
    {
        $data_map = [];
        if (!$div || !$div instanceof DOMElement) return $data_map;

        $divs = $div->getElementsByTagName('div');

        if ($divs->length > 0) {

            for ($i = 0; $i < $divs->length; $i++) {
                $str = trim($divs->item($i)->nodeValue ?? "");

                $str_arr = explode(':', $str, 2);
                $key = $str_arr[0] ?? "";
                $val = $str_arr[1] ?? "";

                $data_map[trim($key)] = trim($val ?? "");
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
            foreach ($rows[0]?->getElementsByTagName('th') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
        } else {
            $header_map = $header;
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