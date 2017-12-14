<?php
header('Content-type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$data = json_decode(file_get_contents("php://input"));
	if(!isset($data)) {
		$response = array("Status" => "Where is your data?");
		die(json_encode($response));
	}

	if ($data->MyKey == 'fdsasfjalw239210!@#$%%^%&^*&(JKKH') {
		require_once("DBconfig.php");
		
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
		
		$response = array("Status" => 200 );
		echo json_encode($response);
	} else {
		$response = array("Status" => "The key is invalid");
		echo json_encode($response);
	}
} else {
	$response = array("Status" => "Please use POST");
	echo json_encode($response);
}
?>