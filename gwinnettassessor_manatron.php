<!DOCTYPE html>
<html>

<body>

    <?php
    require_once 'web-crawler.php';
    require_once 'tax-codes.php';



    // $array = openFile('./TEST FILES/gwinnett county_ga_TEST FILE.csv');
    // $header = array_shift($array);


    function parseRow(mysqli $conn, $index, $row, $headers, $extra_header, $saveDataToDB)
    {
        /******** SETTINGS *********/
        $state = 'GA';
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

            $parcelNo = $parcel_id;

            /**************** CREATE THE TAX ASSESSMENT URL *******************/
            $url = "gwinnettassessor.manatron.com/IWantTo/PropertyGISSearch/PropertyDetail.aspx?";

            $parcel_id = str_replace(' ', '%20', $parcel_id);
            $options = "p=$parcel_id&a=$alternate_id";
            $tax_link = $url . $options;

            /******************** TEST BELOW *************************/
            // echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a><br>";
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

            $owner_name = $row_owner_name ?? ($propInfo['owner_name'] ?? "");
            $owner_type = determineOwnerType($owner_name);
            // echo (($propInfo["Address"] ?? "") . " <br/>");
            $prop_loc = $row_address ?? ($propInfo["Address"] ?? "");

            $owner_loc = $row_owner_address ?? ($propInfo["owner_street"] ?? "");
            $city_state_zip = $propInfo["city_state"] ?? "";

            $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);

            $arr = preg_split('/\s+/', trim($city_state_zip));
            $arr_count = count($arr);
            $zip_code = array_pop($arr) ?? "";
            $zip_code = $row_zip ?? $zip_code;

            $owner_state = $row_owner_state ?? array_pop($arr);
            $city = join(' ', $arr);
            $city = $row_city ?? $city;

            $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);


            $total_appr = $taxAssessInfo['Total Appr'] ?? 0;
            $appraised_value = (int) filter_var($total_appr, FILTER_SANITIZE_NUMBER_INT);
            $total_assd = $taxAssessInfo["Total Assd"] ?? 0;
            $assess_value = (int) filter_var($total_assd, FILTER_SANITIZE_NUMBER_INT);
            $taxes_as_text = getTaxesAsText_GA($appraised_value, 0.553);
            $date_bought = $saleHist[0]['Date'] ?? NULL;


            $prop_class = $propInfo["Property Class"] ?? "";
            $bldg_descrip = (isset($propInfo["Stories"]) ? $propInfo["Stories"] . "-story" : "") . ' ' . ($taxAssessInfo["Type"] ?? "");

            $prop_type = $taxAssessInfo["Occupancy"] ?? NULL;
            $beds = intval($propInfo["Bedrooms"] ?? NULL);
            $baths = intval($propInfo["Bathrooms"] ?? NULL);
            $half_baths = 0;
            if (isset($propInfo["Bathrooms (Half)"]))
                $half_baths = intval($propInfo["Bathrooms (Half)"] ?? NULL);
            $total_baths = $baths + $half_baths;

            // Generate a string of sale entries in XML format
            $sale_hist_data = "";
            foreach ($saleHist
                as
                [
                    'Date' => $date, 'Sale Price' => $price, 'Deed' => $deed, 'Type' => $type, 'Grantee' => $buyer
                ]) {

                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . $buyer . "</b><m>" . $deed . "-" . $type . "</m></e>";

                if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                    $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
            }
            $sale_hist_data = "<r>" . $sale_hist_data . "</r>";


            $structure = [
                'certNo'        =>    intval($adv_num),
                'auctionID'        =>    NULL,
                'parcelNo'        =>    $parcelNo,
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
                'zip'            =>    $zip_code,
                'buildingDescrip'    =>    $bldg_descrip,
                'numBeds'        =>    $beds,
                'numBaths'        =>    $total_baths,
                'lastRecordedOwner'    =>    removeExcessWhitespace($owner_name),
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

        $tables = $doc->getElementsByTagName('table');
        $owner_table = $tables[1];
        $owner_td = $owner_table->getElementsByTagName('td')[0];
        $value_table = $tables[2];
        $history_table = $tables[3];
        $attributes_table = $doc->getElementById('Attribute');
        $more_attr_table = $tables[6];

        $owner_data = parseAttributesTable($owner_table);
        $attrData = parseAttributesTable($attributes_table, true, false, 1, 2);
        $more_attrData = parseAttributesTable($more_attr_table);
        $value_data = parseValueTable($value_table);
        $saleInfo = parseTableData($history_table);

        $owner_details = parseOwnerTd($owner_td);

        return [
            'Property Info'    =>     array_merge($owner_details, $owner_data, $attrData),
            'Sale Info'       =>     $saleInfo,
            'Tax Assess Info'  =>    array_merge($value_data, $more_attrData)
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
                $node = $td_elements[$n];
                $val = trim($node->textContent);
                $a_children = $node->getElementsByTagName('a');
                $anchor_count = $a_children->count();
                if ($anchor_count > 0) $val = trim($a_children[0]?->getAttribute('title') ?? $a_children[0]?->nodeValue);
                $row_data[$col] = $val;
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
            $tds = $rows[$i]->getElementsByTagName('td');
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