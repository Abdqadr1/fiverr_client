<!DOCTYPE html>
<html>

<body>

<?php


/**
 * Download a Webpage via the HTTP GET Protocol using libcurl
 * Developed By: Potent Pages, LLC (https://potentpages.com/)
 * Link: https://potentpages.com/web-crawler-development/tutorials/php/creating-a-simple-php-website-crawler
 */
function _http ( $target ) {
	//Initialize Handle (cURL object)
	$handle = curl_init();
	//Define Settings
	// Tell cURL to use the GET HTTP protocol
	curl_setopt ( $handle, CURLOPT_HTTPGET, true );
	//include the header of the page in case it returns a status other than 200 OK
	curl_setopt ( $handle, CURLOPT_HEADER, true );

	// Cookie file settings
	curl_setopt ( $handle, CURLOPT_COOKIEJAR, "cookie_jar.txt" );
	curl_setopt ( $handle, CURLOPT_COOKIEFILE, "cookies.txt" );

	// The user agent which identifies your program to a web server
	curl_setopt ( $handle, CURLOPT_USERAGENT, "tax-assmnt-test" );
	// Set the URL we want to download
	curl_setopt ( $handle, CURLOPT_URL, $target );
	curl_setopt ( $handle, CURLOPT_FOLLOWLOCATION, true );
	// In the event, a 30x redirect code is found, follow it
	curl_setopt ( $handle, CURLOPT_MAXREDIRS, 4 );
	// Return the file transfer in a variable
	curl_setopt ( $handle, CURLOPT_RETURNTRANSFER, true );

	//Execute Request
	$output = curl_exec ( $handle );
	//Close cURL handle
	curl_close ( $handle );

	//Separate Header and Body
	$separator = "\r\n\r\n";
	$header = substr( $output, 0, strpos( $output, $separator ) );
	$body_start = strlen( $header ) + strlen( $separator );
	$body = substr( $output, $body_start, strlen( $output ) - $body_start );

	//Parse Headers
	$header_array = Array();
	foreach ( explode ( "\r\n", $header ) as $i => $line ) {
		if($i === 0) {
			$header_array['http_code'] = $line;
			$status_info = explode( " ", $line );
			@[ $status_protocol, $status_code, $status_msg ] = $status_info;
			$header_array['status_info'] = Array(	"status_protocol" => $status_protocol,
								"status_code" 	  => $status_code,
								"status_message"  => $status_msg	);
		} else {
			list ( $key, $value ) = explode ( ': ', $line );
			$header_array[$key] = $value;
		}
	}
	//Form Return Structure
	$ret = Array("headers" => $header_array, "body" => $body );
	return $ret;
}


?>
</body>
</html>