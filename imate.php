<!DOCTYPE html>
<html>

<body>

    <?php
    include './dynamic_crawler.php';
    include 'tax-codes.php';


    // $array = openFile('./TEST FILES/broome county_ny_TEST FILE.csv');
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



            /*************** PARSE THE BLOCK, LOT AND QUAL ******************/
            $parcel_id = preg_replace("/\s/", "", $parcel_id);

            $parcel_array = explode("-", $parcel_id, 4);
            $sections = $parcel_array[0] ?? "";
            $block = $parcel_array[1] ?? "";
            $lots = $parcel_array[2] ?? "";
            $suffix = $parcel_array[3] ?? "";

            $section_array = explode('.', $sections, 2);
            $section = $section_array[0] ?? '';
            $sub_section = $section_array[1] ?? '';

            $lot_array = explode('.', $lots, 2);
            $lot = $lot_array[0] ?? "";
            $sub_lot = $lot_array[1] ?? "";

            $section = str_pad($section, 3, '0', STR_PAD_LEFT);
            $sub_section = str_pad($sub_section, 3, '0', STR_PAD_LEFT);

            $block = str_pad($block, 4, '0', STR_PAD_LEFT);

            $lot = str_pad($lot, 3, '0', STR_PAD_LEFT);
            $sub_lot = str_pad($sub_lot, 3, '0', STR_PAD_LEFT);

            $suffix = str_pad($suffix, 4, '0', STR_PAD_LEFT);

            /**************** CREATE THE TAX ASSESSMENT URL *******************/
            $url = "imo.co.broome.ny.us/report.aspx?";

            $key = "$section$sub_section$block$lot$sub_lot$suffix";

            $options = "file=muni0302/T000027/030200160031000302600000000001.JPG&swiscode=$alternate_id&printkey=$key&sitetype=res&siteNum=1";
            $tax_link = $url . $options;

            /******************** TEST BELOW *************************/
            // echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a><br>";

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
                'Tax Assess Info' => $taxAssessInfo,
                'Owner Info' => $ownerInfo
            ] = $parsedPage;


            $owner_loc = $row_owner_address ?? ($ownerInfo[0]["owner_street"] ?? "");
            $owner_name = $ownerInfo[0]['owner_name'] ?? "";
            $owner_type = determineOwnerType($owner_name);
            for ($o = 1; $o < count($ownerInfo); $o++) {
                $name = $ownerInfo[$o]['owner_name'] ?? "";
                if (!empty($name)) $owner_name .= ', ' . $name;
            }
            $owner_name = $row_owner_name ?? $owner_name;

            $prop_loc = $row_address ?? ($propInfo["prop_loc"] ?? "");
            $city_state_zip = $ownerInfo[0]["city_state_zip"] ?? "";
            $sate_zip_array = explode(" ", $city_state_zip, 3);
            $owner_state = $sate_zip_array[1] ?? "";
            $owner_state = $row_owner_state ?? $owner_state;
            $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
            $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

            $property_class = $propInfo['Property Class:'] ?? '';
            [, $prop_class] = explode(' - ', $property_class, 2);
            $stories = ($propInfo['Number of Stories:'] ?? "");
            $building_style = ($arr['Building Style:'] ?? "");
            $prop_type =  $stories . ' ' . $building_style;

            $total_assessment = $propInfo["Total Assessment:"] ?? 0;
            $total_assessment = (int) filter_var($total_assessment, FILTER_SANITIZE_NUMBER_INT);
            $full_market_value = $propInfo['Full Market Value:'] ?? 0;
            $full_market_value = (int) filter_var($full_market_value, FILTER_SANITIZE_NUMBER_INT);
            $taxes_as_text = getTaxesAsText_NY($total_assessment, 0);

            $beds = $propInfo["Bedrooms:"] ?? NULL;
            $baths = $propInfo["Bathrooms (Full - Half):"] ?? NULL;

            $date_bought = $saleHist[0]['Sale Date'] ?? NULL;

            $bldg_descrip = "";
            $tax_map_id = $propInfo['Tax Map ID #:'] ?? NULL;

            // Generate a string of sale entries in XML format
            $sale_hist_data = "";

            /** Iterate backwards thru the array, only keeping those rows which fit within the 500-char limit **/
            foreach (array_reverse($saleHist) as ['Sale Date' => $date, 'Price' => $price, 'Deed Book and Page' => $buyer]) {

                $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . $buyer . "</b><m>" . $sale_descrip . "</m></e>";
                if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                    $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
            }
            $sale_hist_data = "<r>" . $sale_hist_data . "</r>";


            $structure = [
                'certNo'        =>    $adv_num,
                'auctionID'        =>    NULL,
                'parcelNo'        =>    $tax_map_id,
                'alternateID'        =>    $alternate_id,
                'chargeType'        =>    $charge_descrip,
                'faceAmnt'        =>    $face_amount,
                'status'        => ($status ? '1' : '0'),
                'assessedValue'        =>    $total_assessment,
                'appraisedValue'    =>    $full_market_value,
                'propClass'        =>    $prop_class,
                'propType'        =>    $prop_type,
                'propLocation'        =>    $prop_loc,
                'city'            =>    $row_city,
                'zip'            =>    $row_zip,
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

            // listData($structure);
            return ['data' => $structure];
        } catch (Throwable $x) {
            $err_message = $x->getMessage() . " Line: " . $x->getLine();
            return ["error" => $err_message];
        }
    }




    function parsePage($target)
    {
        $require_element_path = "/html/body/form/div[3]/div[2]/table";
        $page = _dynamicCrawler($target, 10, $require_element_path, "btnPublicAccess");

        if (!$page) return FALSE;


        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page, 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        $prop_title_div  = $doc->getElementById('lblReportTitle');
        $lblTotalAssessment  = $doc->getElementById('lblTotalAssessment');
        $lblFullMarketValue  = $doc->getElementById('lblFullMarketValue');

        $info_table = getElementByPath($doc, $require_element_path, true);
        $area_info_table = getElementByPath($doc, "/html/body/form/div[3]/div[3]/table", true);
        $structure_info_table = $doc->getElementById('table6');
        $sales_table = $doc->getElementById('tblSales');
        $owner_div = $doc->getElementById('pnlOwne');
        $taxes_table = $doc->getElementById('tblTax');


        $info_data = parseAttributesTable($info_table, true, true, 0, 0);
        $area_data = parseAttributesTable($area_info_table, false, true, 0, 0);
        $structure_data = parseAttributesTable($structure_info_table, false, true, 0, 0);
        $sales_data = parseTableData($sales_table);
        $taxes_data = parseTableData($taxes_table);
        $owner_data = parseOwnerDiv($owner_div);

        [, $loc_str] = explode(':', $prop_title_div?->nodeValue, 2);
        [$loc,] = explode(',', trim($loc_str), 2);
        $info_data['prop_loc'] = $loc;
        parseValueSpan($lblTotalAssessment, $info_data, "Total Assessment:");
        parseValueSpan($lblFullMarketValue, $info_data, "Full Market Value:");

        return [
            'Property Info'    =>     array_merge($info_data, $area_data, $structure_data),
            'Sale Info'       =>     $sales_data,
            'Tax Assess Info'  =>    $taxes_data,
            'Owner Info' => $owner_data
        ];
    }

    function parseValueSpan($div, &$map, $name)
    {
        $arr = explode('<br>', innerHTML($div), 3);
        [, $price] = explode(' - ', trim($arr[0]), 2);

        $map[$name ?? ''] = $price ?? '';
    }


    function parseAttributesTable($table, $removeFirstRow = false, $is_key_th = true, $key_i = 0, $val_i = 0)
    { // $index of td that contains the key
        $data_map = [];
        if (
            !$table || !$table instanceof DOMElement
        ) return $data_map;

        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $start = $removeFirstRow ? 1 : 0;

        for ($i = $start; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $tds = $rows[$i]?->getElementsByTagName('td');
            $tds_count = count($tds);
            $ths = $rows[$i]?->getElementsByTagName('th');
            $td = $tds[$val_i];


            $th = ($is_key_th) ? $ths[$key_i] : $tds[$key_i];

            if (!$td || !$th) continue;

            $key = trim($th->nodeValue);
            $val = trim($td->nodeValue);
            $data_map[$key] = $val;

            if ($tds_count == count($ths) && $tds_count > 1) {
                $key = trim($th->nodeValue);
                $val = trim($td->nodeValue);
                $data_map[trim($ths[$key_i + 1]->nodeValue)] = trim($tds[$val_i + 1]->nodeValue);
            }
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


    function parseOwnerDiv($div)
    {
        $data = [];
        if (!$div || !$div instanceof DOMElement) return $data;

        $div = $div->getElementsByTagName('div');
        $div_count = $div->count();
        for ($i = 0; $i < $div_count; $i++) {
            $owner = [];
            $child = $div[$i];
            if (empty($child->nodeValue)) continue;

            $arr = explode('<br>', innerHTML($child), 3);

            $owner["owner_name"] = $arr[0];
            $owner['owner_street'] = $arr[1];
            $owner['city_state_zip'] = $arr[2];

            $data[] = $owner;
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