<!DOCTYPE html>
<html>

<body>

    <?php
    include 'web-crawler.php';
    include 'tax-codes.php';

    /******** SETTINGS *********/
    $state = 'NY';
    /***************************/


    $array = openFile('./TEST FILES/genesee county_ny_TEST FILE.csv');

    $header = array_shift($array); // remove the first element from the array
    //$header_map = array_map( 'keepOnlyDesired', $header );



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
        echo "<a href='http://" . $link_1 . "'>" . $link_1 . "</a><br>";
        echo "<a href='http://" . $tax_link_1 . "'>" . $tax_link_1 . "</a><br>";
        /*********************************************************/


        // GO TO THE LINK AND DOWNLOAD THE PAGE

        $parsedPage = parsePage($link_1);
        if (!$parsedPage) {
            echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
            continue;
        }

        [
            'Property Info' => $propInfo,
            'Sale Info' => $saleHist,
            'Tax Assess Info' => $taxAssessInfo,
            'Owners Info' => $owners_info
        ] = $parsedPage;

        $owner_name = $owners_info[0]['Owner Name'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $prop_loc = $propInfo["Location"] ?? "";
        $owner_loc = $owners_info[0]["Address 1"] ?? "";
        $city = $owners_info[0]["City"] ?? "";
        $owner_state = $owners_info[0]["State"] ?? "";
        $zip_code = $owners_info[0]["Zip"] ?? "";

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

        $bldg_desc = $propInfo["Bldg desc:"] ?? "";
        $bldg_descrip = parseNJBldgDescrip($bldg_desc);
        if (!empty($bldg_desc) && empty($bldg_descrip))
            $bldg_descrip = $bldg_desc;

        $ass_value = $taxAssessInfo["Total Assessed Value*"] ?? "";
        $taxes_as_text = getTaxesAsText($taxAssessInfo['Full Market Value'] ?? "");
        $date_bought = $saleHist[0]['Sale Date'] ?? "";

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";

        if (count($saleHist) > 0) {
            foreach (array_reverse($saleHist)
                as
                [
                    'Deed Date' => $date, 'Sale Price' => $price, 'Deed Book' => $b, 'Deed Page' => $p
                ]) {

                $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . $b . "</b><p>" . $p . "</p><m>" . $sale_descrip . "</m></e>";

                if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                    $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
            }
            $sale_hist_data = "<r>" . $sale_hist_data . "</r>";
        }

        $beds = $propInfo["Number of Bedrooms"] ?? "";
        $baths = $propInfo["Number of Full Baths"] ?? "";
        $half_bath = $propInfo["Number of Half Baths"] ?? "";

        $prop_class = ($propInfo["Number of Stories"] ?? "") . ' ' . ($propInfo["Building Style"] ?? "");
        $prop_type = $propInfo["Property Type"] ?? "";


        $structure = [
            'certNo'        =>    $adv_num,
            'auctionID'        =>    NULL,
            'parcelNo'        =>    $propInfo['Parcel_data'] ?? NULL,
            'alternateID'        =>    $alternate_id ?? NULL,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $ass_value ?? '',
            'appraisedValue'    =>    NULL,
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
        $arr = explode('<br>', innerHTML($td), 4);

        $data = [];
        $count = count($arr);
        // $arr = explode("\n", $owner_td->nod, 3);
        $data["owner_name"] = ($count > 3) ? $arr[0] . ' ' . $arr[1] : $arr[0];
        $data['owner_street'] = $arr[2];
        $data['city_state'] = $arr[3];
        return $data;
    }

    function parseTableData($table, $useFirstRow_asHeader = true, $ignore_first_row = true)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $startIndex = $ignore_first_row ? 1 : 0;

        $header_map = [];
        if ($useFirstRow_asHeader) {
            /*** First row determines the column values ***/
            foreach ($rows[$startIndex]->getElementsByTagName('th') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
        }

        $data_map = [];

        for ($i = ++$startIndex; $i < $rows_count; $i++) { // go thru the table starting at row #2
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