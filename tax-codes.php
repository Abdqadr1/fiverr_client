<?php
/* 
      Change charge type to:
	T - Property Taxes, W - Water, S - Sewer, E - PILOT,
	O - Utility, A - Sp Assmnt, 1 - Misc,
	2 - Boarding Up, 3 - Demolition,
	Q - QFARM, B - Bill Board, R - Cell Tower
    */

$charge_patterns = [
	'T' 	=>	'Property Taxes',
	'W'	=> 	'Water',
	'S'	=>	'Sewer',
	'E'	=>	'PILOT',
	'O'	=>	'Utility',
	'A'	=>	'Sp Assmnt',
	'1'	=>	'Misc',
	'2'	=>	'Boarding Up',
	'3'	=>	'Demolition',
	'Q'	=>	'QFARM',
	'B'	=>	'Bill Board',
	'R'	=>	'Cell Tower',
];


$structure_patterns = [
	'AL' 	=>	'aluminum siding',
	'CB'	=>	'concrete block',
	'B'	=> 	'brick',
	'F'	=>	'frame',
	'M'	=>	'metal',
	'RC'	=>	'reinforced concrete',
	'SS'	=>	'structural steel',
	'ST'	=>	'stone',
	'S'	=>	'stucco',
	'W'	=>	'wood',
];

$style_patterns = [
	'A'	=> 	'commercial',
	'B'	=> 	'industrial',
	'C'	=>	'apartments',
	'D'	=>	'dutch colonial',
	'E'	=>	'english tudor',
	'F'	=>	'cape cod',
	'L'	=>	'colonial',
	'M'	=>	'mobile home',
	'R'	=>	'rancher',
	'S'	=>	'split level',
	'T'	=>	'twin',
	'W'	=>	'row home',
	'X'	=>	'duplex',
	'Z'	=>	'raised rancher',
	'O'	=>	'other',
	'2'	=>	'bi-level',
	'3'	=>	'tri-level',
];

$garage_patterns = [
	'AG'	=> 	'attached garage',
	'UG' 	=>	'unattached garage',
	'DG'	=> 	'detached garage',
	'G'	=> 	'garage',
];



function parseChargeTypeDescrip($type)
{
	global $charge_patterns;

	return strtr(strtoupper(trim($type)), $charge_patterns);
}

/** 
    This Function Parses the bldg_descrip using Regex.

    Format should be '#Stories - <Structure> - <Style> - #<Garage>'
    But oftentimes it just appears as '2SF ...' or '2 B' or '2SF2UG COLONIAL'
 **/

function parseNJBldgDescrip($descrip)
{
	global  $structure_patterns,
		$style_patterns,
		$garage_patterns;

	if (!is_string($descrip) || empty($descrip))
		return '';

	/**	
	Parsing the Regex
	==================

	Ex.    2S F 2 UG COLONIAL
    	       | | | |  |
    	       v v v v  v
    	       2 F 2 UG COL..

	Possible Permutations:
	- 2SF2UG COLONIAL
	- 2S-F-2-UG
	- 2S-F-CL-2UG
	- 2SAL CON
	- 2SCB UG CONDO
	- 2 B
	 **/

	/** 
      Works like this:
    	Looks for an optional number at the beginning of the string and potential decimal places
	    --> Assigns to Group 1 'stories'
	Then matches an optional Structure pattern consisting of 1-2 letters
	    --> Assigns to Group 2 'struc'
	Then matches an optional style pattern (one char long) which must be separated from the 
	structure pattern by a space ' ' or dash '-' and must be followed by a non-letter character
	otherwise, it will be treated as a garage pattern
	Therefore '2S-CL-2UG' will work but '2S-CL2UG' won't
	 ** Side Note: This regex will only capture the last letter, hence: 'CL' => 'L' <==> Colonial **
	    --> Assigns to Group 3 'style'
	Matches an optional garage pattern prefaced by an integer number of garages
	    --> Assigns to Group 4 'num_g'
	...and a garage pattern consisting of 1-2 letters
	    --> Assigns to Group 5 'garage'
	Looks for an optional group of letter chars at the end of the string more than 3 chars
	long separated from the rest of the string by at least 1 space ' '
	    --> Assigns to Group 6 'rest'
	 **/

	$regex = '/^(?:(?<stories>[\d]+(?:[\.][\d]+)?)[S]?)?[\s\-]*(?<struc>[A-Z]{1,2})?(?:[\s\-]+(?:(?<style>[A-Z])+[^A-Z])?)?[\s\-]*(?:(?<num_g>[\d]+)?[\s\-]*(?<garage>[A-Z]{1,2}))?(?:[\s]+(?<end>(?:[\S]+[\s]*){3,}))?$/i';

	preg_match($regex, trim($descrip), $parsed, PREG_UNMATCHED_AS_NULL);
	$result = [];

	/**
       The PHP function strtr() replaces substrings with the appropriate value
	corresponding to the replacement array 
       It iterates over the string from left to right and does not replace substrings
	that were previously replaced which would have happened had we used str_replace()
	or preg_replace()
       It only touches each part of the search string ONCE !!

      If given 2 arguments:  strtr(string $string, array $replacements)
	--> will replace matches with longer substrings even if a shorter substring comes first
	--> For ex. Given: { "hi all", array('h' => '-', 'hi' => 'hello') } will result in "hello all"
      If given 3 arguments:  strtr(string $string, string $from, string $to)
	--> will treat each character as a byte and replace accordingly
	 **/

	if (isset($parsed['stories']))
		$result[] = $parsed['stories'] . "-story";
	if (isset($parsed['struc']))
		$result[] = strtr($parsed['struc'], $structure_patterns);
	if (isset($parsed['style']))
		$result[] = strtr($parsed['style'], $style_patterns);
	if (isset($parsed['num_g']))
		$result[] = $parsed['num_g'] . "-car";
	if (isset($parsed['garage']))
		$result[] = strtr($parsed['garage'], $garage_patterns);
	if (isset($parsed['end']))
		$result[] = $parsed['end'];

	return ucwords(implode(" ", $result)); //capitalize the first letter of each word in the sentence
}


function determineOwnerType($owner_name)
{
	// Using regex expressions to check if $owner_name contains the following substrings
	// The '/i' ensures case-insensitivity
	$owner_type = "";
	switch (true) {
		case preg_match("/(inc|llc|corp|lp)/i", $owner_name):
			$owner_type = "Company";
			break;
		case preg_match("/(trust|bank)/i", $owner_name):
			$owner_type = "Entity";
			break;
		case preg_match("/(condo)/i", $owner_name):
			$owner_type = "Condominium";
			break;
		case preg_match("/((?<!real\s)est)/i", $owner_name):
			// Identify as an 'Estate' only if 'real' and a white space char do not proceed it
			// It's necessary to mandate only (1) white space char bc otherwise throws an error
			// => Saying: negative look-behind must be of 'fixed length' i.e. NO ?,+,* allowed
			// removeWhiteSpace() removes extra space chars between words so the only issue is: 'realestate'
			$owner_type = "Estate";
			break;
		default:
			$owner_type = "Individual(s)";
	}

	return $owner_type;
}


function isAbsenteeOwner($property_location, $owner_location)
{
	$absentee_owner = FALSE;

	if (!empty($prop_location) && !empty($owner_location)) {
		$prop_loc_asArray = explode(" ", $prop_location);
		$owner_loc_asArray = explode(" ", $owner_location);
		$testSimilarity = array_intersect($prop_loc_asArray, $owner_loc_asArray);
		$absentee_owner = count($testSimilarity) < 2; // must have at least 2 identical parts
	}

	return $absentee_owner;
}


function livesInState($property_state, $owner_state, $absentee_owner)
{
	$lives_in_state = TRUE;

	if ($absentee_owner) {
		if ($owner_state)
			$lives_in_state = ($owner_state == $property_state);
	}

	return $lives_in_state;
}

function innerHTML($node)
{
	return implode(array_map(
		[$node->ownerDocument, "saveHTML"],
		iterator_to_array($node->childNodes)
	));
}

function listData(array $data)
{
	echo "============================================ <br/>";
	foreach ($data as $x => $x_value) {
		echo $x . " = " . (gettype($x_value) === 'array' ? listData($x_value) : $x_value);
		echo "<br>";
	}
}

function getTaxesAsText($taxes)
{

	$taxes = (float) preg_replace("/([^0-9\\.]+)/i", "", $taxes);
	$taxes_as_text = NULL;

	if (!empty($taxes)) {
		$taxes_perQtr = floatval($taxes) / 4;  // floatval() removes non-numeric chars
		$taxes_as_text = "<r>
			    <e><a>" . $taxes_perQtr . "</a><b>1</b></e>
			    <e><a>" . $taxes_perQtr . "</a><b>1</b></e>
			    <e><a>" . $taxes_perQtr . "</a><b>1</b></e>
			    <e><a>" . $taxes_perQtr . "</a><b>1</b></e>
		    	 </r>";
	}
	return $taxes_as_text;
}

function getElementsByClassName($dom, $className, $is_path = false)
{

	$xpath = new DOMXpath($dom);
	$query = $is_path ? $className : '//*[contains(concat(" ", normalize-space(@class), " "), " ' . $className . ' ")]';
	$results = $xpath->query($query);


	// echo "Length: " . $results?->length . "<br/>";

	// if ($results->length > 0) {
	// 	foreach ($results as $child) {
	// 		echo $res = $child?->nodeValue . "<br/>";
	// 	}
	// }


	return $results?->length == 1 ? $results->item(0) : $results;
}

function getPropTypeFromClass(string $prop_class)
{
	switch (trim($prop_class)) {
		case '1':
			return ["1 - Vacant Land", "Land"];
		case '2':
			return ["2 - Residential", "Residential"];
		case '3A':
			return ["3A - Farm Property (regular)", "Land"];
		case '3B':
			return ["3B - Farm Property (qualified)", "Land"];
		case  '4A':
			return ["4A - Commercial Property", "Commercial"];
		case '4B':
			return ["4B - Industrial", "Commercial"];
		case '4C':
			return ["4C - Apartment Building", "Commercial"];
		case '15F':
			return ["15F - Tax Exempt", "Other"];
		default:
			return ["Unknown", "Other"];
	}
}

function openFile(string $path): array
{
	$open = fopen($path, "r") // open the file for reading only
		or exit("Unable to open file");
	$array = [];
	if ($open !== FALSE) {

		while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
			$array[] = $data;
		}
	} else
		exit("Problem occured opening file");

	fclose($open);
	return $array;
}

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
