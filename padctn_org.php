<!DOCTYPE html>
<html>

<body>

    <?php
    include 'web-crawler.php';
    include 'tax-codes.php';

    /******** SETTINGS *********/
    $city = 'Absecon';
    $state = 'TN';
    $district = "0101";
    /***************************/

    $array = openFile("./davidson_tn_2023_TEST FILE.csv");

    // To display a segment of the array data
    //var_dump( $array[1] ); exit;


    function removeWhitespace($str)
    {

        // -- REMOVES EXCESS WHITESPACE while preserving single spaces between words --
        // Matches whitespace of any kind incl. multiple whitespace chars between words
        // and returns it in a capturing group, then replaces it with the empty string ""
        return preg_replace('/(^\s+|\s+$|\s+(?=\s))/', "", $str);
    }

    function keepOnlyDesired($str)
    {
        // Uses removeWhitespace to remove any leading or trailing whitespace
        // then removes any non-desired characters but preserves inner spacing
        // i.e. remove any non-alphanumeric char, underscore or whitespace (ex. tab/space/line break)
        return preg_replace('/([^\w\s!@#$%^&*()`~\-+=,\.\/\?<>\\|:]+)/', "", removeWhitespace($str));
    }


    if (!function_exists('str_contains')) {

        // Polyfill for PHP 4 - PHP 7, safe to utilize with PHP 8

        function str_contains(string $haystack, string $needle)
        {
            // stripos is case-insensitive
            return empty($needle) || stripos($haystack, $needle) !== false;
        }
    }


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


        /**************** CREATE THE TAX ASSESSMENT URL *******************/
        $url = "www.padctn.org/prc/property/";
        $tax_id = $alternate_id;

        $options = "$tax_id/card/1";
        $hist = "$tax_id/card/1/historical";

        $tax_link = $url . $options;
        $history_link = $url . $hist;

        /******************** TEST BELOW *************************/
        echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a>
        <br/><a href='http://" . $history_link . "'>" . $history_link . "</a><br>";
        /*********************************************************/




        // GO TO THE LINK AND DOWNLOAD THE PAGE

        $parsedPage = parsePage($tax_link);
        $parsedHistoryPage = parseHistoryPage($history_link);
        if (!$parsedPage || !$parsedHistoryPage) {
            echo "Page failed to Load for lienNo: " . $adv_num . "<br/>";
            continue;
        }

        [
            'propInfo' => $propInfo,
            'Tax_Assess_Info' => $taxAssessInfo
        ] = $parsedPage;


        $propInfo = array_merge($parsedHistoryPage[0], $propInfo);
        $saleHist = $parsedHistoryPage[1];

        $owner_name = $propInfo['Current Owner'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $prop_loc = $propInfo["Location Address"] ?? "";

        $owner_loc = $propInfo["Mailing Address"] ?? "";

        @[$owner_street, $owner_city, $state_code] = explode(',', $owner_loc, 3);

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        @[$owner_state, $zip_code] = explode(" ", trim($state_code), 2);
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);


        $bldg_desc = $propInfo["Bldg desc:"] ?? "";
        $bldg_descrip = parseNJBldgDescrip($bldg_desc);
        if (!empty($bldg_desc) && empty($bldg_descrip))
            $bldg_descrip = $bldg_desc;

        $appraisedValue = $taxAssessInfo['Total Appraisal Value'] ?? "";
        $taxes_as_text = getTaxesAsText($appraisedValue);
        $date_bought = $propInfo['Sale Date'] ?? "";

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";
        // Generate a string of sale entries in XML format
        $sale_hist_data = "";

        foreach (array_reverse($saleHist)
            as
            [
                'Sale Date' => $date, 'Sale Price' => $price, 'Deed Type' => $dt, 'Deed Book & Page' => $bg
            ]) {

            $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
            $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><d>" . $dt . "</d><bg>" . $bg . "</bg><m>" . $sale_descrip . "</m></e>";

            if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
        }
        $sale_hist_data = "<r>" . $sale_hist_data . "</r>";

        $_qual =  "";
        $ass_value = $taxAssessInfo["Assessed Value"] ?? "";
        $p_class = $propInfo["Prop class:"] ?? "";
        $prop_use = $taxAssessInfo["Property Use"] ?? "";
        $beds = $taxAssessInfo["Number of Beds"] ?? "";
        $baths = $taxAssessInfo["Number of Baths"] ?? "";
        $half_bath = $taxAssessInfo["Number of Half Bath"] ?? "";

        [$prop_class, $prop_type] = getPropTypeFromClass($p_class);

        $structure = [
            'certNo'        =>    $adv_num,
            'auctionID'        =>    NULL,
            'parcelNo'        =>    $propInfo['Map & Parcel'] ?? "",
            'alternateID'        =>    NULL,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $ass_value ?? '',
            'appraisedValue'    =>    $appraisedValue ?? NULL,
            'propClass'        =>    $prop_class,
            'propType'        =>    $prop_type,
            'propLocation'        =>    $prop_loc,
            'city'            =>    $city,
            'zip'            =>    $zip_code ?? NULL,
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


    function parseHistoryPage($target)
    {
        $page = _http($target);
        $headers = $page['headers'];
        $http_status_code = $headers['status_info']['status_code'];
        //var_dump($headers);

        if ($http_status_code >= 405)
            return FALSE;


        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page['body'], 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        $LocAddDiv = getElementsByClassName($doc, "/html/body/div/section/div/div[2]/div", true);
        [$key, $val] = explode(':', trim($LocAddDiv?->nodeValue), 2);

        $historyTable = getElementsByClassName($doc, "/html/body/div/section/div/div[4]/div/table", true);

        $saleInfo = parseTableData($historyTable);

        return [[$key => $val], $saleInfo];
    }

    function parsePage($target)
    {
        $page = _http($target);
        $headers = $page['headers'];
        $http_status_code = $headers['status_info']['status_code'];
        //var_dump($headers);

        if ($http_status_code >= 405)
            return FALSE;


        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page['body'], 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        $genPropDiv = getElementsByClassName($doc, "/html/body/div[1]/section/div/div[2]/div[1]/ul", true);
        $morePropDiv = getElementsByClassName($doc, "/html/body/div[1]/section/div/div[2]/div[1]/div[4]/ul", true);
        $assessmentPropDiv = getElementsByClassName($doc, "/html/body/div[1]/section/div/div[4]/div[1]/ul", true);
        $genAttributesDiv = getElementsByClassName($doc, "/html/body/div[1]/section/div/div[4]/div[2]/div/div[1]/ul", true);
        $numbersDiv = getElementsByClassName($doc, "/html/body/div[1]/section/div/div[4]/div[2]/div/div[2]/ul", true);

        $genInfo = parseList($genPropDiv);
        $morePropInfo = parseList($morePropDiv);
        $assessmentInfo = parseList($assessmentPropDiv);
        $genAttributesInfo = parseList($genAttributesDiv);
        $numbersInfo = parseList($numbersDiv);

        return [
            'propInfo'    =>     array_merge($genInfo, $morePropInfo),
            'Tax_Assess_Info'  =>    array_merge($assessmentInfo, $genAttributesInfo, $numbersInfo)
        ];
    }

    function parseList($list)
    {
        $children = $list->getElementsByTagName('li');
        $count = count($children);
        $data = [];

        for ($i = 0; $i < $count; $i++) {
            $value = trim($children[$i]->nodeValue);
            [$key, $val] = explode(':', $value, 2);
            $data[$key] = $val;
        }
        return $data;
    }

    function parseTableData($table, $useFirstRow_asHeader = true, $header = null)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows); // count only once

        $header_map = [];
        if ($useFirstRow_asHeader) {
            /*** First row determines the column values ***/
            foreach ($rows[0]->getElementsByTagName('th') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
        } else {
            $header_map = $header;
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