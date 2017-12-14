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
		
		$sql = "INSERT INTO EnvironmentalData (CurrentTime, MyYear, MyMonth, MyDay, MyHour, MyMinute, MySecond, MyDayOfTheWeek, 
				      CalendarType, Latitude, Longitude, DownloadRate, DownloadBytes, ElapsedTime, DownloadSize, Operator, OperatorNumericName, 
				      CellularNetworkType, RawCellularNetworkType, SimpleCellularNetworkType, ActiveNetworkType, AndroidId, SerialNumber, 
				      DeviceModel, Region, MyType)
					   VALUES ('$data->CurrentTime', '$data->MyYear', '$data->MyMonth', '$data->MyDay', '$data->MyHour', '$data->MyMinute', 
		                                '$data->MySecond', '$data->MyDayOfTheWeek', '$data->CalendarType', '$data->Latitude', '$data->Longitude',  
		                                '$data->DownloadRate', '$data->DownloadBytes', '$data->ElapsedTime', '$data->DownloadSize', '$data->Operator', 
		                                '$data->OperatorNumericName', '$data->CellularNetworkType', '$data->RawCellularNetworkType', 
		                                '$data->SimpleCellularNetworkType', '$data->ActiveNetworkType', '$data->AndroidId', '$data->SerialNumber', 
		                                '$data->DeviceModel', '$data->Region', '$data->MyType')";
		
		if ($mysqli->query($sql) === TRUE) {
			$response = array("Status" => 200, "dataIDinClientDB" => $data->dataIDinClientDB, "offset" => $data->offset);
			echo json_encode($response);
		} else {
			$response = array("Status" => "Insert into DB failed");
			echo json_encode($response);
			// echo "Error: " . $sql . "<br>" . $mysqli->error;
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
?>