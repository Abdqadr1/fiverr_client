<!DOCTYPE html>
<html>

<body>

    <?php
    include 'dynamic_crawler.php';
    include 'tax-codes.php';


    // $array = openFile("./TEST FILES/banks county_ga_TEST FILE.csv");
    // $header = array_shift($array);


    function parseRow(mysqli $conn, $index, $row, $headers, $extra_header, $saveDataToDB)
    {
        /******** SETTINGS *********/
        $state = 'GA';
        /***************************/
        global $err_message, $adv_num, $tax_link;
        try {

            // Remove excess whitespace first with 'keepOnlyDesired' function
            // then remove anything that is not a letter or whitespace (the funny chars)
            $row = array_map('keepOnlyDesired', $row);
            [$adv_num, $parcel_id, $alternate_id, $charge_type, $face_amount, $status] = $row;

            // for extra headers
            $row_address = isset($extra_header["prop_location"]) ? $row[$extra_header["prop_location"]] : "";
            $row_owner_name = isset($extra_header["prop_location"]) ? $row[$extra_header["prop_location"]] : "";
            $row_owner_address = isset($extra_header["prop_location"]) ? $row[$extra_header["prop_location"]] : "";
            $row_owner_state = isset($extra_header["prop_location"]) ? $row[$extra_header["prop_location"]] : "";
            $row_zip = isset($extra_header["prop_location"]) ? $row[$extra_header["prop_location"]] : "";
            $row_city = isset($extra_header["prop_location"]) ? $row[$extra_header["prop_location"]] : "";



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
            $url = "qpublic.schneidercorp.com/Application.aspx?AppID=715&LayerID=11428&PageTypeID=2&PageID=4905&Q=351580267&KeyValue=";

            $options = "$block+$lot";
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
                return $saveDataToDB($conn, $err_message, $adv_num, $index, $tax_link, false);
            }

            [
                'Property Info' => $propInfo,
                'Sale Info' => $saleHist,
                'Tax Assess Info' => $taxAssessInfo
            ] = $parsedPage;

            $parcel_no = $propInfo['Parcel Number'] ?? '';
            $prop_loc = $row_address ?? $propInfo["Location Address"] ?? "";
            $owner_name = $row_owner_name ?? $propInfo['Owner name'] ?? "";
            $owner_type = determineOwnerType($owner_name);
            $owner_loc = $row_owner_address ?? $propInfo["Owner Address"] ?? "";
            $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
            $city_state_zip = $propInfo["City State Zip"] ?? "";

            $city_state_zip_array = explode(" ", $city_state_zip, 3);
            $city = $city_state_zip_array[0] ?? "";
            $owner_state = $city_state_zip_array[1] ?? "";
            $zip_code = $city_state_zip_array[2] ?? "";

            $owner_state = $row_owner_state ?? $owner_state;
            $city = str_replace(',', '', $city);
            $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);


            $bldg_desc = "";

            $prop_class = $propInfo["Class"] ?? "";
            $prop_type = $propInfo["Style"] ?? "";

            $beds = $propInfo["Number Of Bedrooms"] ?? NULL;
            $baths = $propInfo["Number Of Full Bathrooms"] ?? NULL;
            $half_baths = $propInfo["Number Of Half Bathrooms"] ?? NULL;
            $exterior_walls = $propInfo['Exterior Walls'] ?? '';
            $date_bought = $saleHist[0]['Sale Date'] ?? NULL;
            $current_value = $taxAssessInfo['Current Value'] ?? NULL;
            $current_value = (int) filter_var($current_value, FILTER_SANITIZE_NUMBER_INT);

            $taxes_as_text = getTaxesAsText_GA($current_value, 0);

            // Generate a string of sale entries in XML format
            $sale_hist_data = "";

            foreach (array_reverse($saleHist) as ['Sale Date' => $date, 'Sale Price' => $price, 'Grantee' => $buyer, 'Grantor' => $seller]) {

                $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . $buyer . "</b><m>" . $sale_descrip . "</m></e>";
                if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                    $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
            }
            $sale_hist_data = "<r>" . $sale_hist_data . "</r>";


            $structure = [
                'certNo'        =>    $adv_num,
                'auctionID'        =>    NULL,
                'parcelNo'        =>    $parcel_no,
                'alternateID'        =>    $alternate_id,
                'chargeType'        =>    $charge_descrip,
                'faceAmnt'        =>    $face_amount,
                'status'        => ($status ? '1' : '0'),
                'assessedValue'        =>    $current_value ?? '',
                'appraisedValue'    =>    NULL,
                'propClass'        =>    $prop_class,
                'propType'        =>    $prop_type,
                'propLocation'        =>    $prop_loc,
                'city'            =>    $row_city ?? $city,
                'zip'            =>    $row_zip ?? $zip_code,
                'buildingDescrip'    =>    $bldg_descrip ?? "",
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
            return $saveDataToDB($conn, $structure, $adv_num,  $index, $tax_link);
        } catch (Throwable $x) {
            $err_message = $x->getMessage() . " Line: " . $x->getLine();
            return $saveDataToDB($conn, $err_message, $adv_num, $index, $tax_link, false);
        }
    }





    function parsePage($target)
    {
        $require_element_path = "/html/body/form/div[6]/div/div[1]/main/section[1]/div/div/table";
        $page = _dynamicCrawler($target, 20, $require_element_path);

        if (!$page)
            return FALSE;


        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page, 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        $summary_table = getElementByPath($doc, $require_element_path, true);
        $owner_info_th = getElementByPath($doc, "/html/body/form/div[6]/div/div[1]/main/section[2]/div/table/tbody/tr/th", true);
        $residential_table = getElementByPath($doc, "/html/body/form/div[6]/div/div[1]/main/section[4]/div/table", true);
        $sales_table = getElementByPath($doc, '//*[@id="ctlBodyPane_ctl11_ctl01_gvwSales"]', true);
        $valuation_table = getElementByPath($doc, '//*[@id="ctlBodyPane_ctl13_ctl01_grdValuation"]', true);

        $summary_data = parseAttributesTable($summary_table);
        $owner_data = parseOwnerTh($owner_info_th);
        $residential_data = parseAttributesTable($residential_table);
        $sales_data = parseTableData($sales_table, true, false);
        $valuation_data = parseAttributesTable($valuation_table, true, true, 0, 1);

        return [
            'Property Info'    =>     array_merge($summary_data, $owner_data, $residential_data),
            'Sale Info'       =>     $sales_data,
            'Tax Assess Info'  =>    $valuation_data
        ];
    }

    function parseOwnerTh($th)
    {
        $data = [];
        if (!$th || !$th instanceof DOMElement) return $data;

        $spans = $th->getElementsByTagName('span');
        $spans_count = count($spans);
        $keys = ['Owner name', 'Owner Address', 'City State Zip'];

        for ($i = 0; $i < $spans_count; $i++) {
            $val = $spans[$i]->nodeValue;
            $data[$keys[$i] ?? ''] = $val;
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

        $head_count = count($header_map);

        for ($i = ++$startIndex; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $td_elements = $rows[$i]->getElementsByTagName('td');
            $th_elements = $rows[$i]->getElementsByTagName('th');

            $num_td_elements = $td_elements->count();
            $row_data = [];

            if ($head_count - $num_td_elements > 1)
                continue;

            for ($n = 0; $n < $head_count; $n++) {
                $val = $n == 0 ? $th_elements[0]?->nodeValue : $td_elements[$n - 1]->nodeValue;
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