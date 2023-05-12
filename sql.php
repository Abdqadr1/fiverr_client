<?php

$conn = NULL;

function custom_global_exception_handler($exception)
{
	//this code should log the exception to the default logging system
	error_log($exception->getMessage(), 0);
}

function custom_global_error_handler($error_level, $error_message)
{
	echo "<b>Error:</b> [" . $error_level . "] " . $error_message . "<br>";
	echo "Ending Script";
	die();
}

//set_error_handler( 'custom_global_error_handler', E_ALL );
//set_exception_handler( 'custom_global_exception_handler' );

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function connect($with_DB = true)
{

	global $conn;

	$server_name = 'localhost';
	$username = 'root';
	$pass = '';
	$database = 'taxlien_2';

	// Create connection
	$conn = $with_DB ? new mysqli($server_name, $username, $pass, $database) : new mysqli($server_name, $username, $pass);
	if (!$conn) {
		exit("Connection failed: " . $conn->connect_error);
	}

	$conn->set_charset('utf8mb4'); // charset
}


function clean_data(String $txt): String
{

	$txt = trim($txt);
	$txt = htmlspecialchars($txt);
	return $txt;
}


function getStates()
{

	global $conn;

	$query = "SELECT jurisdiction_id, name from `states`";
	$stmt  = mysqli_prepare($conn, $query);
	$result = [];

	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $id, $name);

	/* Fetch values and store in an array */
	while (mysqli_stmt_fetch($stmt)) {
		$result[] = [
			'id'	=> 	$id,
			'name'	=>	$name
		];
	}

	return $result;
}


function getCountiesInState(int $stateJurisCode): ?array
{

	global $conn;
	// Get counties possessing the same first 2 digits as the state jurisdiction code

	if ($stateJurisCode) {
		$query = "SELECT jurisdiction_id, name from `counties` WHERE jurisdiction_id BETWEEN ? AND ?";
		$stmt  = mysqli_prepare($conn, $query);
		$result = [];

		// Lower limit is the state_jurisdiction_code ex. 		=> 32 000 000
		// Upper limit is the state_jurisdiction_code + 1 000 000 	=> 33 000 000

		$lowerLimit = $stateJurisCode;
		$upperLimit = $stateJurisCode + 1000000;

		mysqli_stmt_bind_param($stmt, 'ii', $lowerLimit, $upperLimit);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $id, $name);

		/* Fetch values and store in an array */
		while (mysqli_stmt_fetch($stmt)) {
			$result[] = [
				'id'	=> 	$id,
				'name'	=>	$name
			];
		}

		return $result;
	}

	return NULL;
}


function getMunicipalitiesInCounty(int $countyJurisCode): ?array
{

	global $conn;
	// Get municipalities possessing the same middle 3 digits as the county jurisdiction code

	if ($countyJurisCode) {
		$query = "SELECT jurisdiction_id, name from `municipalities` WHERE jurisdiction_id BETWEEN ? AND ?";
		$stmt  = mysqli_prepare($conn, $query);

		// Lower limit is the county_jurisdiction_code ex. 		=> 32 004 000
		// Upper limit is the state_jurisdiction_code + 1 000 	=> 33 005 000

		$lowerLimit = $countyJurisCode;
		$upperLimit = $countyJurisCode + 1000;

		mysqli_stmt_bind_param($stmt, 'ii', $lowerLimit, $upperLimit);
		mysqli_stmt_execute($stmt);
		mysqli_stmt_bind_result($stmt, $id, $name);

		/* Fetch values and store in an array */
		while (mysqli_stmt_fetch($stmt)) {
			$result[] = [
				'id'	=> 	$id,
				'name'	=>	$name
			];
		}

		return $result;
	}

	return NULL;
}
