<?php
header('Content-type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$data = json_decode(file_get_contents("php://input"));
	if(!isset($data)) {
		$response = array("Status" => "Where is your data?");
		die(json_encode($response));
	}

	if ($data->MyKey == '') {
		require_once("DBconfig.php");
		
		$myLat = $data->Latitude;
		$myLon = $data->Longitude;
		$earthR = 6371000;
		$maximumRadius = 300;
		
		$minLat = $myLat - rad2deg($maximumRadius/$earthR);
		$maxLat = $myLat + rad2deg($maximumRadius/$earthR);
		$minLon = $myLon - rad2deg(asin($maximumRadius/$earthR) / cos(deg2rad($myLat)));
		$maxLon = $myLon + rad2deg(asin($maximumRadius/$earthR) / cos(deg2rad($myLat)));
		
		$sql = "SET @myLat = '$myLat', @myLon = '$myLon', @earthR = '$earthR', @minLat = '$minLat', @maxLat = '$maxLat', @minLon = '$minLon', @maxLon = '$maxLon'";
		$mysqli->query($sql);
		$sql = "CALL getEstimatedBWUsingMyAlgorithmV4(@earthR, @myLat, @myLon, @minLat, @maxLat, @minLon, @maxLon, ?)";
		if(!$stmt = $mysqli->prepare($sql)) {
			$response = array("Status" => "error");
			die(json_encode($response));
		}
		$stmt->bind_param('i', $dateOffset);
		
		$estiBW = 1000.0 * getBW($stmt); // kb/s -> bits/s
		$response = array("Status" => 200, "estiBW" => $estiBW, );
		echo json_encode($response);
		$stmt->close();
		
		if ($data->DownloadRate > 20) { // 大於 20 kbit/s才存
			$sql = "INSERT INTO EnvironmentalData (CurrentTime, MyDayOfTheWeek, CalendarType, Latitude, Longitude, DownloadRate, 
			DownloadBytes, ElapsedTime, Operator, OperatorNumericName, CellularNetworkType, RawCellularNetworkType, 
			SimpleCellularNetworkType, ActiveNetworkType, SerialNumber, DeviceModel, MyType)
			VALUES ('$data->CurrentTime', '$data->MyDayOfTheWeek', '$data->CalendarType', '$data->Latitude', '$data->Longitude',
			'$data->DownloadRate', '$data->DownloadBytes', '$data->ElapsedTime', '$data->Operator',
			'$data->OperatorNumericName', '$data->CellularNetworkType', '$data->RawCellularNetworkType',
			'$data->SimpleCellularNetworkType', '$data->ActiveNetworkType', '$data->SerialNumber',
			'$data->DeviceModel', '$data->MyType')";
			$mysqli->query($sql);
		}
		
		$mysqli->close();
	} else {
		$response = array("Status" => "The key is invalid");
		echo json_encode($response);
	}
} else {
	$response = array("Status" => "Please use POST");
	echo json_encode($response);
}



function getBW(&$stmt) {
	$BW = 0;
	$BWArray = array();
	// 4天內(含今天的資料)
	for ($i = 0; $i < 4; $i++) {
		$BWtmp = getBWForOneDay($stmt, $i);
		if ($BWtmp > 0) { $BWArray[ ] =$BWtmp; }
	}
	switch (count($BWArray)) {
		// beta = 0.8
		// beta = 0.6
		default:
		case 0:
			break;
		case 1:
			$BW = $BWArray[0];
			break;
		case 2:
			//$BW = 0.8*$BWArray[0] + 0.2*$BWArray[1];
			$BW = 0.6*$BWArray[0] + 0.4*$BWArray[1];
			break;
		case 3:
			//$BW = 0.8*$BWArray[0] + 0.16*$BWArray[1] + 0.04*$BWArray[2];
			$BW = 0.6*$BWArray[0] + 0.24*$BWArray[1] + 0.16*$BWArray[2];
			break;
		case 4:
			//$BW = 0.8*$BWArray[0] + 0.16*$BWArray[1] + 0.032*$BWArray[2] + 0.008*$BWArray[3];
			$BW = 0.6*$BWArray[0] + 0.24*$BWArray[1] + 0.096*$BWArray[2] + 0.064*$BWArray[3];
			break;
	}
	
	return $BW;
}

function getBWForOneDay(&$stmt, $dOffset) {
	$BWbyOneDay = 0;
	$resultArrayForSQL = array();
	$resultArrayForSQL = callProcedure($stmt, $dOffset);
	for ($i = 0; $i < 12; $i++) {
		if ( is_null($resultArrayForSQL[$i]) ) { $resultArrayForSQL[$i] = 0;	}
	}

	// Finally use for calculating
	$disArray = array(100, 200, 300);
	$BWArray = array();
	$dArray = array();
	$BArray = array();
	$disSum = 0;

	$BWArray[] =getBWForOneDisLevel($dOffset, $resultArrayForSQL[0], $resultArrayForSQL[1], $resultArrayForSQL[2], $resultArrayForSQL[3]); // 0-100m
	$BWArray[] =getBWForOneDisLevel($dOffset, $resultArrayForSQL[4], $resultArrayForSQL[5], $resultArrayForSQL[6], $resultArrayForSQL[7]); // 100-200m
	$BWArray[] =getBWForOneDisLevel($dOffset, $resultArrayForSQL[8], $resultArrayForSQL[9], $resultArrayForSQL[10], $resultArrayForSQL[11]); //200-300m

	// 避免該距離的平均BW為零造成的錯誤，把有零的值去掉
	for ($i = 0; $i < 3; $i++) {
		if ($BWArray[$i] > 0) {
			$disSum += $disArray[$i];
			$dArray[ ] = $disArray[$i];
			$BArray[ ] =$BWArray[$i];
		}
	}

	$counter = count($dArray);
	$index = $counter - 1;
	for ($i = 0; $i < $counter; $i++) {
		$BWbyOneDay += $dArray[$index - $i] * $BArray[$i];
	}
	if ($disSum > 0) {
		$BWbyOneDay /= $disSum;
	}
	return $BWbyOneDay;
}

function getBWForOneDisLevel($dOffset, $beforeOne, $beforeTwo, $afterOne, $afterTwo) {
	$BWForOneDisLevel = 0;
	$beforeArray = array();
	$afterArray = array();
	$beforeResult = 0;
	$afterResult = 0;

	if ($beforeOne > 0) { $beforeArray[] = $beforeOne; }
	if ($beforeTwo > 0) { $beforeArray[] = $beforeTwo; }
	if ($afterOne > 0) { $afterArray[] = $afterOne; }
	if ($afterTwo > 0) { $afterArray[] = $afterTwo; }

	switch ( count($beforeArray) ) {
		// gamma = 0.8
		// gamma = 0.6
		default:
		case 0:
			break;
		case 1:
			$beforeResult = $beforeArray[0];
			break;
		case 2:
			//$BW = 0.8*$resultArray[0] + 0.2*$resultArray[1];
			$beforeResult = 0.6*$beforeArray[0] + 0.4*$beforeArray[1];
			break;
	}
	switch ( count($afterArray) ) {
		// gamma = 0.8
		// gamma = 0.6
		default:
		case 0:
			break;
		case 1:
			$afterResult = $afterArray[0];
			break;
		case 2:
			//$BW = 0.8*$resultArray[0] + 0.2*$resultArray[1];
			$afterResult = 0.6*$afterArray[0] + 0.4*$afterArray[1];
			break;
	}

	if ($dOffset > 0) {
		if ($beforeResult > 0 && $afterResult >0) {
			$BWForOneDisLevel = 0.5*$beforeResult + 0.5*$afterResult;
		} else if ($beforeResult > 0) {
			$BWForOneDisLevel = $beforeResult;
		} else {
			$BWForOneDisLevel = $afterResult;
		}
	} else {
		$BWForOneDisLevel = $beforeResult;
	}

	return $BWForOneDisLevel;
}

function callProcedure(&$stmt, $dOffset) {
	global $dateOffset;
	$dateOffset = $dOffset;
	$resultTestArray = array(0,0,0,0,0,0,0,0,0,0,0,0);

	if (!$stmt->execute()) {
		$response = array("Status" => "error");
		die(json_encode($response));
	}
	do {
		if ( !$stmt->bind_result($resultTestArray[0], $resultTestArray[1], $resultTestArray[2], $resultTestArray[3],
				$resultTestArray[4], $resultTestArray[5], $resultTestArray[6], $resultTestArray[7], $resultTestArray[8],
				$resultTestArray[9], $resultTestArray[10], $resultTestArray[11]) ) {
					$response = array("Status" => "error");
					die(json_encode($response));
				}
				while ($stmt->fetch()) { }
	} while ($stmt->more_results() && $stmt->next_result());

	return $resultTestArray;
}
?>