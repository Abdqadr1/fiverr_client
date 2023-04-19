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



        /*************** PARSE THE BLOCK, LOT AND QUAL ******************/
        $parcel_id = preg_replace("/\s/", "", $parcel_id); // get rid of all whitespace

        // @  <=>  suppress undefined index errors
        [$block, $lot_qual] = explode("-", $parcel_id, 2);
        @[$lot, $qual] = explode("--", $lot_qual, 2);
        $qual ??= "";

        $parcelNo = $block . "-" . $lot . "-" . $qual;

        /**************** CREATE THE TAX ASSESSMENT URL *******************/
        $url = "tax1.co.monmouth.nj.us/cgi-bin/m4.cgi?";

        @[$block, $sub_block] = explode(".", $block, 2);
        $sub_block ??= "";
        // offset is made negative to start counting from the end of the str
        $block_str = substr("00000", 0, strlen($block) * -1) . $block .
            (!empty($sub_block) ? substr("____", strlen($sub_block) * -1) . $sub_block
                : "____");
        $lot_asArray = explode(".", $lot, 2);
        $lot_str = substr("00000", 0, strlen($lot_asArray[0]) * -1) . $lot_asArray[0] .
            (count($lot_asArray) === 2 ? substr("____", strlen($lot_asArray[1]) * -1) . $lot_asArray[1]
                : "____");
        $qual_str = empty($qual) ? "_____" : substr("_____", 0, strlen($qual) * -1) . $qual;

        $options = "district=" . $district . "&l02=" . $district . $block_str . $lot_str . $qual_str . "M";
        $tax_link = $url . $options;

        /******************** TEST BELOW *************************/
        echo "<a href='http://" . $tax_link . "'>" . $tax_link . "</a><br>";
        /*********************************************************/




        // GO TO THE LINK AND DOWNLOAD THE PAGE

        $parsedPage = parsePage($tax_link);
        if (!$parsedPage) {
            echo "Page failed to Load for lienNo: " . $adv_num;
            continue;
        }

        [
            'Property Info' => $propInfo,
            'Sale Info' => $saleHist,
            'Tax Assess Info' => $taxAssessInfo
        ] = $parsedPage;

        $owner_name = $propInfo['Owner:'] ?? "";
        $owner_type = determineOwnerType($owner_name);

        $prop_loc = $propInfo['Prop Loc:'] ?? "";
        $owner_loc = $propInfo['Street:'] ?? "";

        $absentee_owner = isAbsenteeOwner($prop_loc, $owner_loc);
        @[, $owner_state] = explode(",", $propInfo['City State:'], 2);
        $ownerState = substr(trim($owner_state), 0, 2);
        $lives_in_state = livesInState($state, $ownerState, $absentee_owner);

        $bldg_desc = $propInfo['Bldg Desc:'] ?? "";
        $bldg_descrip = parseNJBldgDescrip($bldg_desc);
        if (!empty($bldg_desc) && empty($bldg_descrip))
            $bldg_descrip = $bldg_desc;

        $taxes = $propInfo['Taxes:'] ?? "";
        $taxes_as_text = getTaxesAsText($taxes);

        $date_bought = $propInfo['Sale Date:'] ?? "";

        // Generate a string of sale entries in XML format
        $sale_hist_data = "";

        /** Iterate backwards thru the array, only keeping those rows which fit within the 500-char limit **/
        foreach (array_reverse($saleHist) as ['Date' => $date, 'Price' => $price, 'Grantee' => $buyer]) {

            $sale_descrip = (intval($price) < 100 ? "Non-Arms Length" : "-");
            $entry = "<e><d>" . $date . "</d><p>" . $price . "</p><b>" . $buyer . "</b><m>" . $sale_descrip . "</m></e>";
            if (strlen($entry) <= 500 - 7 - strlen($sale_hist_data)) // 7 == strlen("<r></r>")
                $sale_hist_data = $entry . $sale_hist_data; // place the entry at the beginning of the str
        }
        $sale_hist_data = "<r>" . $sale_hist_data . "</r>";

        // Get the assessed value for the current year (the first entry)
        $ass_value = count($taxAssessInfo) > 0 ? (int) $taxAssessInfo[0]['Assessed'] : 0;

        $prop_class = $propInfo['Class:'] ?? '';
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
            'assessedValue'        =>    $ass_value,
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

        var_dump($structure);

        /*
    // UPLOAD TO DATABASE
    $stmt = mysqli_prepare($con, "INSERT INTO `tax_lien` VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'sssd', ...$structure);

    mysqli_stmt_execute($stmt);
    printf("%d row inserted.\n", mysqli_stmt_affected_rows($stmt));
*/
    }


    /**	<<=================== TAX ASSESSMENT PAGE SETUP =========================>>

<Table 1>
   <Row 1>	Block: 	--	Prop Loc: --	Owner: 	     --	   Square Ft:   --
   <Row 2>	Lot: 	--	District: --	Street:      --    Year Built:  --
   <Row 3>	Qual: 	--	Class:    --	City State:  --	   Style:       --

	<Row 4>	------------------ Additional Information ------------------------

   <Row 5>	( ): 	--	( ):      --	( ): 	    --		( ):    --
   <Row 6>	( ): 	--	( ):      --	Land Desc:  --		( ):    --
   <Row 7>	( ): 	--	( ):      --	Bldg Desc:  --		( ):    --
   <Row 8>	( ): 	--	( ):      --	( ): 	    --		( ):    --
   <Row 9>	( ): 	--	( ):      --	( ): 	    --		Taxes:  --

	<Row 10> -------------------- Sale Information ---------------------------

   <Row 11>	Sale Date:  --	 	Book:  [--  Page: --]		Price:  --


<Table 2>
   <Row 1>	Sr1a	   Date	      Book	Page	 Price 	  NU#	Ratio	Grantee
   <Row 2>     [Link]       --	       --	 --	   --     --	  -- 	   --
     ...       [Link] 	    --	       --	 --	   --     --	  -- 	   --
     ...       [Link] 	    --	       --	 --	   --     --	  -- 	   --
     ...       [Link] 	    --	       --	 --	   --     --	  -- 	   --
   <Row n>     [Link] 	    --	       --	 --	   --     --	  -- 	   --


<Table 3>
	<Row 1>	--------------------- TAX-LIST-HISTORY ------------------------

   <Row 2>	Year   Property Location    Land/Imp/Tot    Exemption  	Assessed   Property Class
   <Row 3>     [Link]         --	         --	 	--	   --            --
     ...       [Link]         --	         --	 	--	   --            --
     ...       [Link]         --	         --	 	--	   --            --
   <Row n>     [Link]         --	         --	 	--	   --            --

     ** Year goes backwards all the way to 2015 when URL += "&hist=1"


	<<========================================================================>>

     **/


    function parsePage($target)
    {
        $page = _http($target);
        $headers = $page['headers'];
        $http_status_code = $headers['status_info']['status_code'];
        //var_dump($headers);

        if ($http_status_code != 200)
            return FALSE;

        $doc = new DOMDocument('1.0', 'utf-8');
        // don't propagate DOM errors to PHP interpreter
        libxml_use_internal_errors(true);
        // converts all special characters to utf-8
        $content = mb_convert_encoding($page['body'], 'HTML-ENTITIES', 'UTF-8');
        $doc->loadHTML($content);

        [$table1, $table2, $table3] = $doc->getElementsByTagName('table');


        /******** Table 1 -- Get Property Info *********/

        $trTags_1 = $table1->getElementsByTagName('tr');
        // Remove rows 4 & 10 (which contain headings)
        // !!! Row 10 becomes 9 after the first removal
        $table1->removeChild($trTags_1[3]);
        $table1->removeChild($trTags_1[8]);

        $tdTags_1 = $table1->getElementsByTagName('td');
        $data_1 = [];

        // Loop through each <td> tag in Table 1
        for ($i = 0; $i < count($tdTags_1); $i++) {

            $node = trim($tdTags_1[$i]->textContent);
            $data_1[$node] = trim($tdTags_1[++$i]->textContent);
            // increment it first to get the value of the subseq. node
            // then the for loop increments it again to skip that node
        }

        /******** Table 2 -- Get Sale Info *********/

        $data_2 = parseTableData($table2);

        /******** Table 3 -- Get Tax Assessment History *********/

        // Remove the first row which says "TAX-LIST-HISTORY"
        $table3_child_rows = $table3->getElementsByTagName("tr");
        $table3->removeChild($table3_child_rows->item(0));

        // Establish the header row as the first row
        $table3_firstRow = $table3_child_rows->item(0);

        // Remove the picture <td>
        // There seems to be information nodes at the beginning and end of the <tr> childNodes List
        // We can't simply get the first and last <td> nodes using firstChild/lastChild
        // Therefore it was necessary to get the number of childNodes and jump back 2
        $table3_firstRow->removeChild($table3_firstRow->childNodes->item(count($table3_firstRow->childNodes) - 2));

        $data_3 = parseTableData($table3);

        return [
            'Property Info'    =>     $data_1,
            'Sale Info'       =>     $data_2,
            'Tax Assess Info'  =>    $data_3
        ];

        /******************** END OF PARSING - TEST BELOW *************************/
        //var_dump($data_1);
        //var_dump($data_2);
        //var_dump($data_3);
        /**************************************************************************/
    }

    function parseTableData($table, $useFirstRow_asHeader = true, $header = null)
    {
        $rows = $table->getElementsByTagName('tr');
        $rows_count = count($rows); // count only once

        $header_map = [];
        if ($useFirstRow_asHeader) {
            /*** First row determines the column values ***/
            foreach ($rows[0]->getElementsByTagName('td') as $col) { // get child elements
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