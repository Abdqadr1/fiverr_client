<!DOCTYPE html>
<html>

<body>

    <?php
    include 'web-crawler.php';
    include 'tax-codes.php';

    /******** SETTINGS *********/
    $state = 'TN';
    /***************************/

    $array = openFile("./TEST FILES/gibson_tn_TEST FILE.csv");
    $header = array_shift($array); // remove the first element from the array
    //$header_map = array_map( 'keepOnlyDesired', $header );



    for ($i = 0; $i < 2; $i++) { //count($array)

        // Remove excess whitespace first with 'keepOnlyDesired' function
        // then remove anything that is not a letter or whitespace (the funny chars)
        $row = array_map('keepOnlyDesired', $array[$i]);
        [$adv_num, $parcel_str, $alternate_id, $charge_type, $face_amount, $status] = $row;


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
        @[$jur, $control_map, $group, $parcel] = preg_split('/\s+/', $parcel_str, 4);
        $parcel = str_replace('.', '', $parcel);
        $jur = str_pad($jur, 3, '0', STR_PAD_LEFT);
        $identifier = "%20";
        $special_interest = "000";

        /**************** CREATE THE TAX ASSESSMENT URL *******************/
        $url = "assessment.cot.tn.gov/TPAD/Parcel/Details?&";
        $parcel_id = "$control_map%20$group%20$parcel$identifier$special_interest";
        $parcel_key = "$jur$control_map%20$group%20$parcel$identifier$special_interest";

        $options = "parcelId=$parcel_id&jur=$jur&parcelKey=$parcel_key";
        $tax_link = $url . $options;

        /******************** TEST BELOW *************************/
        echo "<a href='https://" . $tax_link . "'>" . $tax_link . "</a><br>";
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

        $owner_name = $propInfo['owner'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $prop_loc = $propInfo["prop_address"] ?? "";

        $owner_loc = $propInfo["owner_street"] ?? "";
        $city_state = $propInfo["city_state"] ?? "";

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        @[$city, $owner_state, $zip_code] = preg_split('/\s+/', $city_state, 3);
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

        $appraisal_value = $taxAssessInfo['Total Market Appraisal:'] ?? 0;
        $ass_value = $taxAssessInfo["Assessment:"] ?? 0;
        $taxes_as_text = getTaxesAsText($appraisal_value);

        $improvement_type = $taxAssessInfo['Improvement Type'] ?? '';
        $prop_class = $propInfo["Class"] ?? "";

        $date_bought = $saleHist[0]['Sale Date'] ?? NULL;

        $bldg_desc = $propInfo["Bldg desc:"] ?? "";
        $bldg_descrip = parseNJBldgDescrip($bldg_desc);
        if (!empty($bldg_desc) && empty($bldg_descrip))
            $bldg_descrip = $bldg_desc;


        // Generate a string of sale entries in XML format
        $sale_hist_data = "";

        foreach (array_reverse($saleHist)
            as
            [
                'Sale Date' => $date, 'Price' => $price, 'Book' => $book, 'Page' => $page, 'Vacant/Improved' => $vacantImproved,
                'Type Instrument' => $instrument, 'Qualification' => $qualification
            ]) {

            $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
            $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . $book . "</b><pa>" . $page . "</pa><v>" . $vacantImproved . "<v/>
            <t>" . $instrument . "</t><q>" . $qualification . "</q><m>" . $sale_descrip . "</m></e>";

            if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
        }
        $sale_hist_data = "<r>" . $sale_hist_data . "</r>";



        $structure = [
            'certNo'        =>    $adv_num,
            'auctionID'        =>    NULL,
            'parcelNo'        =>    urldecode($parcel_id),
            'alternateID'        =>    NULL,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $ass_value ?? NULL,
            'appraisedValue'    =>    $appraisal_value,
            'propClass'        =>    $prop_class,
            'propType'        =>    $improvement_type,
            'propLocation'        =>    $prop_loc,
            'city'            =>    $city,
            'zip'            =>    $zip_code,
            'buildingDescrip'    =>    $bldg_descrip,
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

            $ownerDiv = getElementByPath($doc, "/html/body/div/main/div/div[2]/div[2]/div[1]/div/div[2]/div/div", true);
            $propAddress = getElementByPath($doc, "//html/body/div/main/div/div[2]/div[2]/div[2]/div/div[2]/div[1]/div/p", true)
                ?->nodeValue ?? "";
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

            return [
                'Property Info'    =>     array_merge($ownerInfo, ['prop_address' => explode(':', $propAddress, 2)[1]], $generalInfo1, $generalInfo2),
                'Sale Info'       =>     $saleInfo,
                'Tax Assess Info'  =>    array_merge($buildingInfo1, $buildingInfo2, $valueInfo)
            ];
        } catch (Exception | Error  $x) {
            return false;
        }
    }

    function parseOwnerDiv($div)
    {
        $data_map = [];
        $divs = $div->getElementsByTagName('div');

        if ($divs->length > 0) {

            for ($i = 1; $i < $divs->length; $i++) {
                $node = $divs->item($i);
                $val = trim($node->nodeValue);

                switch ($i) {
                    case 1:
                        $data_map['owner'] = $val ?? "";
                        break;
                    case 2:
                        $data_map['owner_street'] = $val ?? "";
                        break;
                    case 4:
                        $data_map['city_state'] = $val ?? "";
                        break;
                    default:
                        break;
                }
            }
        }

        return $data_map;
    }

    function parseValueDiv($div)
    {
        $data_map = [];
        $divs = $div->getElementsByTagName('div');

        if ($divs->length > 0) {

            for ($i = 0; $i < $divs->length; $i += 2) {
                $key = trim($divs->item($i)?->nodeValue ?? "");
                $val = trim($divs->item($i + 1)?->nodeValue ?? "");

                $data_map[$key] = $val;
            }
        }
        return $data_map;
    }

    function parseGeneralDiv($div)
    {
        $data_map = [];
        $divs = $div->getElementsByTagName('div');

        if ($divs->length > 0) {

            for ($i = 0; $i < $divs->length; $i++) {
                $str = trim($divs->item($i)?->nodeValue ?? "");

                @[$key, $val] = explode(':', $str, 2);


                $data_map[trim($key)] = trim($val ?? "");
            }
        }
        return $data_map;
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