<!DOCTYPE html>
<html>

<body>

    <?php
    include './dynamic_crawler.php';
    include 'tax-codes.php';



    // $array = openFile('./TEST FILES/dutchess county_ny_TEST FILE.csv');
    // $header = array_shift($array); // remove the first element from the array


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


            $qual ??= "";
            $parcelNo = $parcel_id;

            /**************** CREATE THE TAX ASSESSMENT URL *******************/
            $url = "gis.dutchessny.gov/parcelaccess/property-card/?";

            [$aa, $bb, $cc, $dd, $xx] = explode('-', $parcel_id, 5);
            $aa = str_pad($aa, 6, '0', STR_PAD_LEFT);
            $bb = str_pad($bb, 6, '0', STR_PAD_LEFT);
            $cc = str_pad($cc, 4, '0', STR_PAD_LEFT);
            $dd = str_pad($dd, 6, '0', STR_PAD_LEFT);
            $xx = str_pad($xx, 4, '0', STR_PAD_LEFT);

            $alternate_id = trim($alternate_id);
            $grid = "$aa$bb$cc$dd$xx";
            $options = "parcelid=$alternate_id&parcelgrid=$grid";
            $tax_link = $url . $options;

            /******************** TEST BELOW *************************/
            // echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a><br>";
            /*********************************************************/

            // set time limit 
            ini_set('max_execution_time', 10);

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

            $parcel_number = $propInfo['Parcel Number'] ?? '';
            $prop_loc = $row_address ?? ($propInfo["Parcel Address"] ?? "");
            $municipality = $propInfo["Municipality"] ?? "";

            $owner_name = $row_owner_name ?? ($propInfo['Owner Name'] ?? "");
            $owner_type = determineOwnerType($owner_name);
            $primary_address = $propInfo['Primary Owner Mailing Address'] ?? "";
            [$owner_loc, $city, $state_zip] = explode(',', $primary_address, 3);
            $owner_loc = $row_owner_address ?? $owner_loc;
            [$owner_state, $zipcode] = preg_split('/\s+/', trim($state_zip), 2);
            $owner_state = $row_owner_state ?? $owner_state;

            $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
            $lives_in_state = livesInState($state ?? "", $owner_state, $absentee_owner);

            $land_use_class = $propInfo['Land Use Class:'] ?? '';
            $arr = preg_split('/\s+/', $land_use_class, 4);

            $prop_class = $arr[3] ?? "";
            $prop_type = ($arr[1] ?? "") . ' ' . ($arr[2] ?? "");

            $total_value = $taxAssessInfo["Total:"] ?? 0;
            $total_value = (int) filter_var($total_value, FILTER_SANITIZE_NUMBER_INT);
            $full_market_value = $taxAssessInfo['Full Market Value:'] ?? 0;
            $full_market_value = (int) filter_var($full_market_value, FILTER_SANITIZE_NUMBER_INT);
            $taxes_as_text = getTaxesAsText_NY($total_value, 0);

            $beds = $propInfo["No. Bedrooms:"] ?? NULL;
            $baths = $propInfo["No. Full Baths:"] ?? NULL;
            $half_bath = $propInfo["No. Half Baths:"] ?? NULL;

            $date_bought = $saleHist['Sale Date:'] ?? NULL;

            $bldg_descrip = "";


            // Generate a string of sale entries in XML format
            $sale_hist_data = "";
            for ($k = 0; $k < 1; $k++) {
                $price = $saleHist['Sale Price:'];

                if (!$price) continue;

                $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
                $entry = "<e><d>" . ($saleHist['Sale Date:']) . "</d><p>" . $price . "</p><d>" . ($saleHist['Deed Book:']) . "</d><p>" . ($saleHist['Deed Page:']) . "</p><m>" . $sale_descrip . "</m></e>";

                if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                    $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str

            }
            $sale_hist_data = "<r>" . $sale_hist_data . "</r>";



            $structure = [
                'certNo'        =>    $adv_num,
                'auctionID'        =>    NULL,
                'parcelNo'        =>    $parcel_number,
                'alternateID'        =>    $propInfo['Alternate ID'] ?? NULL,
                'chargeType'        =>    $charge_descrip,
                'faceAmnt'        =>    $face_amount,
                'status'        => ($status ? '1' : '0'),
                'assessedValue'        =>    $total_value,
                'appraisedValue'    =>    $full_market_value,
                'propClass'        =>    $prop_class,
                'propType'        =>    $prop_type,
                'propLocation'        =>    $prop_loc,
                'city'            =>    $row_city ?? $municipality,
                'zip'            =>    $row_zip,
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
        $requires_path_contain = '//*[@id="pid-parcelnum"]';
        $page = _dynamicCrawler($target, 7, $requires_path_contain);

        if (!$page) return FALSE;

        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page, 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        $info_div = getElementByPath($doc, "/html/body/main/section[2]/div/div[1]", true);
        $parcel_details_div = getElementByPath($doc, "/html/body/main/section[3]/div", true);
        $assessment_info_div = getElementByPath($doc, "/html/body/main/section[4]/div", true);
        $last_sale_div = getElementByPath($doc, "/html/body/main/section[5]/div", true);
        $residential_info_div = getElementByPath($doc, "/html/body/main/section[7]/div/div", true);

        $info_data = parseInfoDiv($info_div);
        $parcel_details_data = parseAttributesDiv($parcel_details_div);
        $assessment_info_data = parseAttributesDiv($assessment_info_div);
        $sales_info = parseAttributesDiv($last_sale_div);
        $residential_info = parseAttributesDiv($residential_info_div);

        return [
            'Property Info'    =>     array_merge($info_data, $parcel_details_data, $residential_info),
            'Sale Info'       =>     $sales_info,
            'Tax Assess Info'  =>    $assessment_info_data
        ];
    }

    function parseInfoDiv($div)
    {
        $data = [];
        if (!$div || !$div instanceof DOMElement) return $data;

        $divs = $div->getElementsByTagName('div');
        $div_count = count($divs);

        for ($i = 0; $i < $div_count; $i++) {
            $divv = $divs[$i];
            $key = trim($divv->getElementsByTagName('h4')[0]?->nodeValue);
            $val = trim($divv->getElementsByTagName('p')[0]?->nodeValue);
            $val_2 = trim($divv->getElementsByTagName('p')[1]?->nodeValue ?? '');

            if (!empty($val_2)) $val = $val . ',' . $val_2;

            $data[$key] = $val;
        }

        return $data;
    }
    function parseAttributesDiv($parent)
    {
        $data_map = [];
        if (!$parent || !$parent instanceof DOMElement) return $data_map;

        $props = $parent->getElementsByTagName('div');
        $props_count = count($props);

        for ($i = 0; $i < $props_count; $i++) {
            $p_s = $props[$i]?->getElementsByTagName('p');
            $p_s_count = count($p_s);

            if ($p_s_count % 2 == 1) continue;

            $key = trim($p_s[0]->nodeValue);
            $val = trim($p_s[1]->nodeValue);
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