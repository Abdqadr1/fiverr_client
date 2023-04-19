<!DOCTYPE html>
<html>

<body>

    <?php
    include 'web-crawler.php';
    include 'tax-codes.php';

    /******** SETTINGS *********/
    $state = 'GA';
    /***************************/


    $array = openFile('./TEST FILES/gwinnett county_ga_TEST FILE.csv');
    $header = array_shift($array);



    for ($i = 0; $i < 1; $i++) { //count($array)

        // Remove excess whitespace first with 'keepOnlyDesired' function
        // then remove anything that is not a letter or whitespace (the funny chars)
        $row = array_map('keepOnlyDesired', $array[$i]);

        [$adv_num, $parcel_id, $alternate_id, $charge_type, $face_amount, $status] = $row;


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

        $options = "p=$parcel_id&a=$alternate_id";
        $tax_link = $url . $options;

        /******************** TEST BELOW *************************/
        echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a><br>";
        /*********************************************************/


        // GO TO THE LINK AND DOWNLOAD THE PAGE

        $parsedPage = parsePage($tax_link);
        if (!$parsedPage) {
            echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
            continue;
        }

        [
            'Property Info' => $propInfo,
            'Sale Info' => $saleHist,
            'Tax Assess Info' => $taxAssessInfo
        ] = $parsedPage;

        $owner_name = $propInfo['owner_name'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $prop_loc = $propInfo["Address"] ?? "";

        $owner_loc = $propInfo["owner_street"] ?? "";
        $city_state_zip = $propInfo["city_state"] ?? "";

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        @[$city, $owner_state, $zip_code] = preg_split('/\s+/', trim($city_state_zip), 3);
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);


        $bldg_desc = $propInfo["Bldg desc:"] ?? "";
        $bldg_descrip = parseNJBldgDescrip($bldg_desc);
        if (!empty($bldg_desc) && empty($bldg_descrip))
            $bldg_descrip = $bldg_desc;

        $total_appr = $taxAssessInfo['Total Appr'] ?? 0;
        $total_assd = $taxAssessInfo["Total Assd"] ?? "";
        $taxes_as_text = getTaxesAsText($total_appr);
        $date_bought = $saleHist[0]['Date'] ?? "";


        $prop_class = $propInfo["Property Class"] ?? "";
        $prop_type = ($propInfo["Stories"] ?? "") . ' ' . ($taxAssessInfo["Type"] ?? "");

        $prop_use = $taxAssessInfo["Occupancy"] ?? "";
        $beds = $propInfo["Bedrooms"] ?? "";
        $baths = $propInfo["Bathrooms"] ?? "";
        $half_bath = $propInfo["Bathrooms (Half)"] ?? "";

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";
        foreach (array_reverse($saleHist)
            as
            [
                'Date' => $date, 'Sale Price' => $price, 'Deed' => $dt, 'Book' => $b, 'Page' => $p
            ]) {

            $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
            $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><d>" . $dt . "</d><b>" . $b . "</b><p>" . $p . "</p><m>" . $sale_descrip . "</m></e>";

            if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
        }
        $sale_hist_data = "<r>" . $sale_hist_data . "</r>";


        $structure = [
            'certNo'        =>    $adv_num,
            'auctionID'        =>    NULL,
            'parcelNo'        =>    $parcelNo,
            'alternateID'        =>    $propInfo['Alternate ID'] ?? NULL,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $total_assd ?? '',
            'appraisedValue'    =>    $total_appr,
            'propClass'        =>    $prop_class,
            'propType'        =>    $prop_type,
            'propLocation'        =>    $prop_loc,
            'city'            =>    $city,
            'zip'            =>    $zip_code ?? NULL,
            'buildingDescrip'    =>    $bldg_descrip,
            'numBeds'        =>    $beds ?? NULL,
            'numBaths'        =>    $baths ?? NULL,
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
    }



    function parsePage($target)
    {
        try {
            $page = _http($target);
            $headers = $page['headers'];
            $http_status_code = $headers['status_info']['status_code'];
            //var_dump($headers);


            if ($http_status_code >= 400)
                return FALSE;


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
        } catch (Exception | Error $x) {
            return false;
        }
    }

    function parseOwnerTd($td)
    {
        $arr = explode('<br>', innerHTML($td), 4);

        $data = [];
        $count = count($arr);
        // $arr = explode("\n", $owner_td->nod, 3);
        $data["owner_name"] = ($count > 3) ? $arr[0] . ' ' . $arr[1] : $arr[0];
        $data['owner_street'] = $arr[2];
        $data['city_state'] = $arr[3];
        return $data;
    }

    function parseTableData($table, $useFirstRow_asHeader = true)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows); // count only once

        $header_map = [];
        if ($useFirstRow_asHeader) {
            /*** First row determines the column values ***/
            foreach ($rows[0]->getElementsByTagName('th') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
        }

        $data_map = [];

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

    function parseAttributesTable($table, $removeFirstRow = false, $is_key_th = true, $key_i = 0, $val_i = 0)
    { // $index of td that contains the key
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $data_map = [];

        $start = $removeFirstRow ? 1 : 0;

        for ($i = $start; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $tds = $rows[$i]->getElementsByTagName('td');
            $td = $tds[$val_i];


            $th = ($is_key_th) ? $rows[$i]->getElementsByTagName('th')[$key_i] : $tds[$key_i];



            if (!$td || !$th) continue;

            $key = trim($th->nodeValue);
            $val = trim($td->nodeValue);

            $data_map[$key] = $val;
        }
        return $data_map;
    }

    function parseValueTable($table, $removeFirstRow = true)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $data_map = [];

        $start = $removeFirstRow ? 1 : 0;

        for ($i = $start; $i < $rows_count; $i++) { // go thru the table starting at row #2
            $td = $rows[$i]->getElementsByTagName('td')[0];
            $th = $rows[$i]->getElementsByTagName('th')[0];

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