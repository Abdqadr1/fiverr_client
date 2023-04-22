<!DOCTYPE html>
<html>

<body>

	<?php

	/**
	 * Download a Webpage via the HTTP GET Protocol using libcurl
	 * Developed By: Potent Pages, LLC (https://potentpages.com/)
	 * Link: https://potentpages.com/web-crawler-development/tutorials/php/creating-a-simple-php-website-crawler
	 */
	function _http($target)
	{
		//Initialize Handle (cURL object)
		$handle = curl_init();
		//Define Settings
		// Tell cURL to use the GET HTTP protocol
		curl_setopt($handle, CURLOPT_HTTPGET, true);
		//include the header of the page in case it returns a status other than 200 OK
		curl_setopt($handle, CURLOPT_HEADER, true);

		// Cookie file settings
		curl_setopt($handle, CURLOPT_COOKIEJAR, "cookie_jar.txt");
		curl_setopt($handle, CURLOPT_COOKIEFILE, "cookies.txt");

		curl_setopt($handle, CURLOPT_URL, $target);
		curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');

		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($handle, CURLOPT_AUTOREFERER, true);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);

		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
		// In the event, a 30x redirect code is found, follow it
		curl_setopt($handle, CURLOPT_MAXREDIRS, 4);
		/*
	// The user agent which identifies your program to a web server
	curl_setopt ( $handle, CURLOPT_USERAGENT, "tax-assmnt-test" );
	// Set the URL we want to download
	curl_setopt ( $handle, CURLOPT_URL, $target );
	curl_setopt ( $handle, CURLOPT_FOLLOWLOCATION, true );
	// In the event, a 30x redirect code is found, follow it
	curl_setopt ( $handle, CURLOPT_MAXREDIRS, 4 );
	// Return the file transfer in a variable
	curl_setopt ( $handle, CURLOPT_RETURNTRANSFER, true );
*/
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($handle, CURLOPT_TIMEOUT, 10);

		//Execute Request
		$output = curl_exec($handle);


		$error = false;
		if (curl_errno($handle))
			$error = curl_error($handle);

		//Close cURL handle
		curl_close($handle);

		//Separate Header and Body
		$separator = "\r\n\r\n";
		$header = substr($output, 0, strpos($output, $separator));
		$body_start = strlen($header) + strlen($separator);
		$body = substr($output, $body_start, strlen($output) - $body_start);

		//Parse Headers
		$header_array = array();
		foreach (explode("\r\n", $header) as $i => $line) {
			if ($i === 0) {
				$header_array['http_code'] = $line;
				$status_info = explode(" ", $line);
				@[$status_protocol, $status_code, $status_msg] = $status_info;
				if ($error)
					$status_msg = $error;
				$header_array['status_info'] = array(
					"status_protocol" => $status_protocol,
					"status_code" 	  => $status_code,
					"status_message"  => $status_msg
				);
			} else {
				list($key, $value) = explode(': ', $line);
				$header_array[$key] = $value;
			}
		}
		// echo $body;
		//Form Return Structure
		$ret = array("headers" => $header_array, "body" => $body);
		return $ret;
	}


	?>
</body>

</html>