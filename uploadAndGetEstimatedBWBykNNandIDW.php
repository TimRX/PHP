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
		// 存被上傳的資料
		if ($data->DownloadRate > 20) { // 大於 20 kbit/s才存
			$sql = "INSERT INTO EnvironmentalData (CurrentTime, MyDayOfTheWeek, CalendarType, Latitude, Longitude, DownloadRate,
			DownloadBytes, ElapsedTime, Operator, OperatorNumericName, CellularNetworkType, RawCellularNetworkType,
			SimpleCellularNetworkType, ActiveNetworkType, SerialNumber, DeviceModel, MyType)
			VALUES ('$data->CurrentTime', '$data->MyDayOfTheWeek', '$data->CalendarType', '$data->Latitude', '$data->Longitude',
			'$data->DownloadRate', '$data->DownloadBytes', '$data->ElapsedTime', '$data->Operator',
			'$data->OperatorNumericName', '$data->CellularNetworkType', '$data->RawCellularNetworkType',
			'$data->SimpleCellularNetworkType', '$data->ActiveNetworkType', '$data->SerialNumber',
			'$data->DeviceModel', '$data->MyType')";
			$result = $mysqli->query($sql);
			$ttterror = $mysqli->error;
		}
		//取得估計值
		$sql = "call getRecordsBykNNandFilter1hr('$data->Latitude', '$data->Longitude', 1000)";
		$result = $mysqli->query($sql);
		// 計算 estimated bandwidth
		$estiBW = 0;
		$numerator = 0;
		$denominator = 0;
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				$bandwidth = 1000.0 * $row['DownloadRate'];        // kb/s -> bits/s
				$distance = $row['dis'] > 0 ? $row['dis'] : 0.1;   // 如果距離為零，則改成給0.1公尺，即10公分
				$numerator += pow($bandwidth / $distance, 2);
				$denominator += pow(1 / $distance, 2);
			}
			$estiBW = sqrt($numerator/$denominator);
		}
		$result->close();
		
		
		$response = array("Status" => 200, "estiBW" => $estiBW);
		echo json_encode($response);
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