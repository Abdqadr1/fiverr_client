<!DOCTYPE html>
<html>

<body>

    <?php
    include 'web-crawler.php';
    include 'tax-codes.php';

    /******** SETTINGS *********/
    $state = 'TN';
    /***************************/


    $array = openFile('./TEST FILES/shelby county_tn_TEST FILE.csv');
    $header = array_shift($array); // remove the first element from the array
    //$header_map = array_map( 'keepOnlyDesired', $header );


    for ($i = 0; $i < 1; $i++) { //count($array)

        // Remove excess whitespace first with 'keepOnlyDesired' function
        // then remove anything that is not a letter or whitespace (the funny chars)
        $row = array_map('keepOnlyDesired', $array[$i]);
        [$adv_num, $parcel_id, $alternate_id, $charge_type, $face_amount, $status, $address] = $row;

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

        // @  <=>  suppress undefined index errors
        [$block, $lot] = explode(" ", $parcel_id, 2);
        $parcelNo = $block . " " . $lot;

        /**************** CREATE THE TAX ASSESSMENT URL *******************/
        $url = "www.assessormelvinburgess.com/propertyDetails?";

        $options = "parcelid=$block%20%20$lot&IR=true";
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

        $owner_name = $propInfo['Owner Name :'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $prop_loc = ($propInfo["Property Address:"] ?? "") . ' ' . ($propInfo['Municipal Jurisdiction:'] ?? '');

        $owner_loc = $propInfo["Owner Mailing Address:"] ?? "";
        $city_state_zip = $propInfo["Owner City/State/Zip:"] ?? "";
        [$city, $owner_state, $zip_code] = preg_split('/\s+/', $city_state_zip, 3);

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

        $bldg_desc = $propInfo["Bldg desc:"] ?? "";
        $bldg_descrip = parseNJBldgDescrip($bldg_desc);
        if (!empty($bldg_desc) && empty($bldg_descrip))
            $bldg_descrip = $bldg_desc;

        $total_assessment = $taxAssessInfo['Total Assessment:'] ?? "";
        $total_appraisal = $taxAssessInfo['Total Appraisal:'] ?? "";
        $taxes_as_text = getTaxesAsText($total_appraisal);
        $date_bought = $saleHist[0]['Date of Sale'] ?? "";

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";
        if (count($saleHist) > 0) {
            foreach (array_reverse($saleHist)
                as
                [
                    'Date of Sale' => $date, 'Sales Price' => $price, 'Deed Number' => $db, 'Instrument Type' => $it
                ]) {

                $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
                $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><db>" . $db . "</db><it>" . $it . "</it><m>" . $sale_descrip . "</m></e>";

                if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                    $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
            }
            $sale_hist_data = "<r>" . $sale_hist_data . "</r>";
        }

        $beds = $propInfo["Bedrooms:"] ?? "";
        $baths = $propInfo["Bathrooms :"] ?? "";
        $half_baths = $propInfo["Half Baths:"] ?? "";
        $land_use = $propInfo['Land Use:'] ?? "";

        $prop_type = ($propInfo["Stories:"] ?? "") . ' ' . ($propInfo["Exterior Walls:"] ?? "");
        $prop_class = $taxAssessInfo['Class:'] ?? "";


        $structure = [
            'certNo'        =>    $adv_num,
            'auctionID'        =>    NULL,
            'parcelNo'        =>    $parcel_id,
            'alternateID'        =>    $alternate_id,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $total_assessment ?? NULL,
            'appraisedValue'    =>    $total_appraisal ?? NULL,
            'propClass'        =>    $prop_class,
            'propType'        =>    $prop_type,
            'propLocation'        =>    $prop_loc,
            'city'            =>    $city ?? '',
            'zip'            =>    $zip_code ?? '',
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
            'taxJurisdictionID'    =>    NULL
        ];
        listData($structure);
    }



    function parsePage($target)
    {
        $page = _http($target);
        $headers = $page['headers'];
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

        $prop_table = getElementsByClassName($doc, "/html/body/main/div/div/div/div/div[1]/div[2]/div/table", true);
        $appr_table = getElementsByClassName($doc, "/html/body/main/div/div/div/div/div[2]/div[2]/div/table", true);
        $improv_table = getElementsByClassName($doc, "/html/body/main/div/div/div/div/div[3]/div[2]/div/table", true);
        $sales_table = getElementsByClassName($doc, "/html/body/main/div/div/div/div/div[6]/div[2]/div/table", true);

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
    {
        $data_map = [];

        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows);

        $start = $removeFirstRow ? 1 : 0;

        for ($i = $start; $i < $rows_count; $i++) {
            $tds = $rows[$i]->getElementsByTagName('td');
            if (count($tds) < 1) continue;

            $td = $tds[$val_i];
            $th = ($is_key_th) ? $rows[$i]->getElementsByTagName('th')[$key_i] : $tds[$key_i];

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