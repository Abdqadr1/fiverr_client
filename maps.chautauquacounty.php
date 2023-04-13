<!DOCTYPE html>
<html>

<body>

    <?php
    include 'web-crawler.php';
    include 'tax-codes.php';

    /******** SETTINGS *********/
    $city = 'Absecon';
    $state = 'NJ';
    $district = "0101";
    /***************************/


    $open = fopen("./absecon_2022_TEST FILE.csv", "r") // open the file for reading only
        or exit("Unable to open file");
    $array = [];
    if ($open !== FALSE) {

        while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
            $array[] = $data;
        }
    } else
        exit("Problem occured opening file");

    fclose($open);

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



    for ($i = 0; $i < 3; $i++) { //count($array)

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



        /*************** PARSE THE BLOCK, LOT AND QUAL ******************/
        $parcel_id = preg_replace("/\s/", "", $parcel_id); // get rid of all whitespace

        // @  <=>  suppress undefined index errors
        [$block, $lot_qual] = explode("-", $parcel_id, 2);
        @[$lot, $qual] = explode("--", $lot_qual, 2);
        $qual ??= "";

        $parcelNo = $block . "-" . $lot . "-" . $qual;

        /**************** CREATE THE TAX ASSESSMENT URL *******************/
        $url = "maps.chautauquacounty.com/public/PropertyInformationTool/property?";

        @[$block, $sub_block] = explode(".", $block, 2);
        $sub_block ??= "";
        // offset is made negative to start counting from the end of the str
        $block_str = substr("00000", 0, strlen($block) * -1) . $block .
            (!empty($sub_block) ? substr("____", strlen($sub_block) * -1) . $sub_block
                : "____");
        @[$lotNumber, $sub_lot] = explode(".", $lot, 2);
        $lot_asArray = explode(".", $lot, 2);
        $qual_str = empty($qual) ? "_____" : substr("_____", 0, strlen($qual) * -1) . $qual;

        $options = "nDistrict=$district&szBlockNum=$block&szLotNum=$lotNumber&szBlockStuff=$sub_block&szLotStuff=$sub_lot&szQual=$qual";
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

        $owner_name = $propInfo['Owner:'] ?? "";
        $owner_type = determineOwnerType($owner_name);
        $prop_loc = $propInfo["Location:"] ?? "";

        $owner_loc = $propInfo["Mailing address:"] ?? "";
        $city_state = $propInfo["City/State:"] ?? "";

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        @[, $owner_state, $zip_code] = explode(" ", $city_state, 3);
        $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);


        $bldg_desc = $propInfo["Bldg desc:"] ?? "";
        $bldg_descrip = parseNJBldgDescrip($bldg_desc);
        if (!empty($bldg_desc) && empty($bldg_descrip))
            $bldg_descrip = $bldg_desc;

        $taxes_as_text = getTaxesAsText($propInfo['Last yr taxes:'] ?? "");
        $date_bought = $propInfo['Deed date:'] ?? "";

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";

        $_qual = $propInfo['Qual:'] !== "n/a" ? $propInfo['Qual:'] : "";
        $_1 = ($propInfo['Block / Lot:'] ?? "") . "/" . $_qual;
        $_6 = $propInfo["Last yr taxes:"] ?? "";
        $ass_value = $propInfo["Net value:"] ?? "";
        $prop_class = $propInfo["Prop class:"] ?? "";
        $_10 = $taxAssessInfo["Type/use:"] ?? "";
        $_11 = $taxAssessInfo["# bedrooms:"] ?? "";
        $_12 = $taxAssessInfo["# bathrooms:"] ?? "";

        $prop_type = '';

        switch (trim($prop_class)) {
            case '1':
                $prop_class = "1 - Vacant Land";
                $prop_type = "Land";
                break;
            case '2':
                $prop_class = "2 - Residential";
                $prop_type = "Residential";
                break;
            case '3A':
                $prop_class = "3A - Farm Property (regular)";
                $prop_type = "Land";
                break;
            case '3B':
                $prop_class = "3B - Farm Property (qualified)";
                $prop_type = "Land";
                break;
            case  '4A':
                $prop_class = "4A - Commercial Property";
                $prop_type = "Commercial";
                break;
            case '4B':
                $prop_class = "4B - Industrial";
                $prop_type = "Commercial";
                break;
            case '4C':
                $prop_class = "4C - Apartment Building";
                $prop_type = "Commercial";
                break;
            case '15F':
                $prop_class = "15F - Tax Exempt";
                $prop_type = "Other";
                break;
            default:
                $prop_class = "Unknown";
                $prop_type = "Other";
        }

        $structure = [
            'certNo'        =>    $adv_num,
            'auctionID'        =>    NULL,
            'parcelNo'        =>    $parcelNo,
            'alternateID'        =>    NULL,
            'chargeType'        =>    $charge_descrip,
            'faceAmnt'        =>    $face_amount,
            'status'        => ($status ? '1' : '0'),
            'assessedValue'        =>    $ass_value ?? '',
            'appraisedValue'    =>    NULL,
            'propClass'        =>    $prop_class,
            'propType'        =>    $prop_type,
            'propLocation'        =>    $prop_loc,
            'city'            =>    $city,
            'zip'            =>    NULL,
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

        $taxListDetailsTable = $doc->getElementById("MainContent_MOD4Table");
        $assessmentHistoryTable = $doc->getElementById("MainContent_AssmtHistTable");
        $propertyDetailsTable = $doc->getElementById("MainContent_CAMATable");
        $saleHistoryTable = $doc->getElementById("MainContent_SR1ATable");

        $propInfo = parseTableData($taxListDetailsTable, false);
        $saleInfo = parseTableData($saleHistoryTable, false);
        $detailsInfo = parseTableData($propertyDetailsTable, false);
        $assessmentInfo = parseTableData($assessmentHistoryTable);

        return [
            'Property Info'    =>     $propInfo,
            'Sale Info'       =>     $saleInfo,
            'Tax Assess Info'  =>    $detailsInfo
        ];

        /******************** END OF PARSING - TEST BELOW *************************/
        //var_dump($data_1);
        //var_dump($data_2);
        //var_dump($data_3);
        /**************************************************************************/
    }

    function parseTableData($table, $useFirstRowAsKey = true)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows); // count only once

        $header_map = [];
        $data_map = [];

        $startIndex = $useFirstRowAsKey ? 2 : 0;

        if ($useFirstRowAsKey) {
            /*** First row determines the column values ***/
            foreach ($rows[1]->getElementsByTagName('td') as $col) { // get child elements
                $header_map[] = trim($col->textContent);
            }
            for ($i = $startIndex; $i < $rows_count; $i++) {
                $td_elements = $rows[$i]->getElementsByTagName('td');
                $num_td_elements = $td_elements->count();
                // echo $num_td_elements;
                $row_data = [];

                if ($num_td_elements !== count($header_map)) continue;

                for ($n = 0; $n < $num_td_elements; $n++) {
                    $col = $header_map[$n] ?? "";
                    $row_data[$col] =  trim($td_elements[$n]?->textContent);
                }

                $data_map[] = $row_data;
            }
        } else {
            for ($i = 0; $i < $rows_count; $i++) { // go thru the table starting at row #2
                $td_elements = $rows[$i]->getElementsByTagName('td');
                $num_td_elements = $td_elements->count();
                // echo $num_td_elements;
                $row_data = [];

                // Eliminate those rows that do not have the proper structure (i.e. same # of cols)
                if ($num_td_elements % 2 !== 0)
                    continue;

                for ($n = 0; $n < $num_td_elements; $n += 2) {
                    $key = trim($td_elements[$n]?->textContent);
                    $value = trim($td_elements[$n + 1]?->textContent);

                    if (!$key) continue;

                    $data_map[$key] = $value;
                }
            }
        }


        return $data_map; // as an associative array
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