<!DOCTYPE html>
<html>

<body>

    <?php
    include 'dynamic_crawler.php';
    include 'tax-codes.php';


    // $array = openFile("./TEST FILES/montgomery county_tn_TEST FILE.csv");
    // $header = array_shift($array);

    function parseRow($row, $extra_header)
    {
        /******** SETTINGS *********/
        $state = 'TN';
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



            /*************** PARSE THE BLOCK, LOT ******************/
            [$block, $lot] = explode(" ", $parcel_id, 2);
            $block = empty($block) ? "" : str_replace('.', '', $block);
            $lot = empty($lot) ? "" : str_replace('.', '', $lot);

            /**************** CREATE THE TAX ASSESSMENT URL *******************/
            $url = "property.spatialest.com/tn/montgomery#/property/";


            // $options = "";
            $tax_link = $url . trim($alternate_id);

            /******************** TEST BELOW *************************/
            // echo "<a href='https://" . $tax_link . "'>" . $tax_link . "</a><br>";
            /*********************************************************/

            // set time limit 
            ini_set('max_execution_time', 20);

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
                'Tax Assess Info' => $taxAssessInfo,
                'Building Data' => $building_data,
            ] = $parsedPage;


            $parcel_id = $propInfo['Parcel ID'] ?? '';
            $prop_loc = $row_address ?? $propInfo["prop_loc"] ?? "";
            $owner_name = $row_owner_name ?? $propInfo['owner_name'] ?? "";
            $owner_type = determineOwnerType($owner_name);
            $owner_loc = $row_owner_address ?? $propInfo["owner_street"] ?? "";
            $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
            $city_state_zip = $propInfo["city_state_zip"] ?? "";

            $city_state_zip_array = explode(" ", $city_state_zip, 3);
            $city = $row_city ?? ($city_state_zip_array[0] ?? "");
            $owner_state = $city_state_zip_array[1] ?? "";
            $zip_code = $row_zip ?? ($city_state_zip_array[2] ?? "");


            $owner_state = $row_owner_state ?? $owner_state;
            $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

            $total_appraised_value = $taxAssessInfo['Total Appraised Value'] ?? 0;
            $total_appraised_value = (int) filter_var($total_appraised_value, FILTER_SANITIZE_NUMBER_INT);
            $total_assessed_value = $taxAssessInfo['Total Assessed Value'] ?? 0;
            $total_assessed_value = (int) filter_var($total_assessed_value, FILTER_SANITIZE_NUMBER_INT);
            $taxes_as_text = getTaxesAsText_TN($total_appraised_value, 0);

            $beds = $propInfo["Number Of Bedrooms"] ?? NULL;
            $baths = $propInfo["Number Of Full Bathrooms"] ?? NULL;
            $half_baths = $propInfo["Number Of Half Bathrooms"] ?? NULL;
            $date_bought = $saleHist[0]['Transfer Date'] ?? NULL;

            $zoning = $propInfo['Zoning'] ?? "";
            $split = explode('-', $zoning, 4);
            $prop_type_class = preg_split('/\s+/', trim($split[3]), 2);
            $prop_class = $prop_type_class[0] ?? "";
            $prop_type = $prop_type_class[1] ?? "";

            $building_type_desc = $building_data[0]['Bldg Type Description'] ?? "";

            // Generate a string of sale entries in XML format
            $sale_hist_data = "";
            foreach (array_reverse($saleHist) as ['Transfer Date' => $date, 'Sales Price' => $price, 'Grantee' => $buyer, 'Grantor' => $seller]) {

                $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . $buyer . "</b><m>" . $sale_descrip . "</m></e>";
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
                'assessedValue'        =>    $total_assessed_value,
                'appraisedValue'    =>    $total_appraised_value,
                'propClass'        =>    $prop_class,
                'propType'        =>    $prop_type,
                'propLocation'        =>    $prop_loc,
                'city'            =>    $row_city,
                'zip'            =>    $row_zip,
                'buildingDescrip'    =>    $building_type_desc,
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

            // listData($structure);
            return ['data' => $structure];
        } catch (Throwable $x) {
            $err_message = $x->getMessage() . " Line: " . $x->getLine();
            return ["error" => $err_message];
        }
    }




    function parsePage($target)
    {
        $require_element_path = "/html/body/main/div/div[2]/div[1]/div[2]/div/section/div/div[1]/div[2]/header/div/div/div[1]/div[2]";
        $page = _dynamicCrawler($target, 30, $require_element_path);

        if (!$page)
            return FALSE;


        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page, 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        $key_info_list  = getElementByPath($doc, "/html/body/main/div/div[2]/div[1]/div[2]/div/section/div/div[4]/div/div/div/div[1]/div/div/div[2]/div/div/div/div[1]/div/div/div/div/div/div/div/ul", true);
        $appr_info_list  = getElementByPath($doc, "/html/body/main/div/div[2]/div[1]/div[2]/div/section/div/div[4]/div/div/div/div[1]/div/div/div[2]/div/div/div/div[2]/div/div/div/div/div/div/div/ul", true);
        $location_div = getElementByPath($doc, $require_element_path, true);
        $owner_div = getElementByPath($doc, "/html/body/main/div/div[2]/div[1]/div[2]/div/section/div/div[1]/div[2]/header/div/div/div[2]/div/div", true);
        $zoner_info_list = getElementByPath($doc, "/html/body/main/div/div[2]/div[1]/div[2]/div/section/div/div[4]/div/div/div/div[3]/div/div/div[2]/div/div/div/div/div/div/div/div/div/div/div/ul", true);
        $sales_table = getElementByPath($doc, "/html/body/main/div/div[2]/div[1]/div[2]/div/section/div/div[4]/div/div/div/div[6]/div/div/div[2]/div/div/div/div/div/div/div/div/div/table", true);
        $building_data_table = getElementByPath($doc, "/html/body/main/div/div[2]/div[1]/div[2]/div/section/div/div[4]/div/div/div/div[7]/div/div/div[2]/div/div/div/div[2]/div/div/div/div/div/table", true);

        $key_info_data = parseAttributesList($key_info_list);
        $appr_info_data = parseAttributesList($appr_info_list);
        $owner_data = parseOwnerDiv($owner_div);
        $zoner_info_data = parseAttributesList($zoner_info_list);
        $sales_data = parseTableData($sales_table, true, false);
        $building_data = parseTableData($building_data_table, true, false);

        return [
            'Property Info'    =>     array_merge(
                $owner_data,
                $key_info_data,
                $zoner_info_data,
                ['prop_loc' => $location_div->nodeValue]
            ),
            'Sale Info'       =>     $sales_data,
            'Tax Assess Info'  =>    $appr_info_data,
            'Building Data' => $building_data
        ];
    }


    function parseOwnerDiv($div)
    {
        $data = [];
        if (!$div || !$div instanceof DOMElement) return $data;

        $arr = explode('<br>', innerHTML($div), 3);

        $count = count($arr);
        // $arr = explode("\n", $owner_td->nod, 3);
        $data["owner_name"] = ($count > 3) ? $arr[0] . ', ' . $arr[1] : $arr[0];
        $data['owner_street'] = ($count > 3) ? $arr[2] : $arr[1];
        $data['city_state'] = ($count > 3) ? $arr[3] : $arr[2];
        return $data;
    }

    function parseAttributesList($list)
    {
        $data_map = [];
        if (!$list || !$list instanceof DOMElement) return $data_map;

        $rows = $list->getElementsByTagName('li');
        $rows_count = count($rows);

        for ($i = 0; $i < $rows_count; $i++) {
            $spans = $rows[$i]?->getElementsByTagName('span');
            $spans_count = count($spans);

            if ($spans_count % 2 == 1) continue;

            $key = trim($spans[0]->nodeValue);
            $val = trim($spans[1]->nodeValue);
            $data_map[$key] = $val;

            if ($spans_count == 4) {
                $k = trim($spans[2]->nodeValue);
                $v = trim($spans[3]->nodeValue);
                $data_map[$k] = $v;
            }
        }
        return $data_map;
    }


    function parseTableData($table, $useFirstRow_asHeader = true, $ignore_first_row = true)
    {
        $data_map = [];
        if (!$table || !$table instanceof DOMElement) return $data_map;

        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $startIndex = 0;

        $header_map = [];
        if ($useFirstRow_asHeader) {
            /*** First row determines the column values ***/
            foreach ($rows[$startIndex]?->getElementsByTagName('th') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
        }

        $head_count = count($header_map);

        for ($i = ++$startIndex; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $td_elements = $rows[$i]?->getElementsByTagName('td');

            $num_td_elements = $td_elements->count();
            $row_data = [];

            if ($head_count - $num_td_elements > 1)
                continue;

            for ($n = 0; $n < $head_count; $n++) {
                $val = $td_elements[$n]->nodeValue;
                $col = $header_map[$n] ?? "";
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