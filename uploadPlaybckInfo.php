<?php
header('Content-type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$data = json_decode(file_get_contents("php://input"), TRUE);
	if(!isset($data)) {
		$response = array("Status" => "Where is your data?");
		die(json_encode($response));
	}

	if ($data['MyKey'] == 'fdsasfjalw239210!@#$%%^%&^*&(JKKH') {
		
		require_once("DBconfig.php");
		if($mysqli->autocommit(FALSE) === FALSE) {
			$mysqli->close();
			$response = array("Status" => "Transaction Failure");
			die(json_encode($response));
		}
		$dataType = $data['PlaybackInfo'][12];
		$result = TRUE;
		$myID = -1;
		
		// 存到 PlaybackInfo
		$sql = "INSERT INTO PlaybackInfo (deviceModel, serialNumber, playbackStartSessionTimeInMs, playbackEndSessionTimeInMs, formalStartTime, 
			formalEndTime, dayOfTheWeek, calendarType, totalBufferDelayInMs, playbackSuspendCounter, qualitySwitchCounter, algorithmType, dataType)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $mysqli->prepare($sql);
		$stmt->bind_param('ssiissisiiisi', $data['PlaybackInfo'][0], $data['PlaybackInfo'][1], $data['PlaybackInfo'][2], $data['PlaybackInfo'][3],
				$data['PlaybackInfo'][4], $data['PlaybackInfo'][5], $data['PlaybackInfo'][6], $data['PlaybackInfo'][7], $data['PlaybackInfo'][8],
				$data['PlaybackInfo'][9], $data['PlaybackInfo'][10], $data['PlaybackInfo'][11], $data['PlaybackInfo'][12]);
		$result = $result && $stmt->execute();
		if ( !$result) {
			$stmt->close();
			$mysqli->close();
			$response = array("Status" => "Inserting into table PlaybackInfo occurred a error");
			die(json_encode($response));
		}
		$stmt->close();
		$myID = $mysqli->insert_id;
		if ( $myID < 0) {
			$mysqli->close();
			$response = array("Status" => "Got a invalid ID.");
			die(json_encode($response));
		}
		
		// EstimatedBW
		$counter = $data['EstimatedBW']['counter'];
		for ($i = 0; $i < $counter; $i++) {
			$sql = "INSERT INTO EstimatedBW (ID, bitrate, sessionTimeInMs, waitingTimeInMs, dataType) VALUES (?, ?, ?, ?, ?)";
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param('iiiii', $myID, $data['EstimatedBW'][$i][0], $data['EstimatedBW'][$i][1], $data['EstimatedBW'][$i][2], $dataType);
			$result = $result && $stmt->execute();
			if ( !$result) {
				$stmt->close();
				$mysqli->close();
				$response = array("Status" => "Inserting into table EstimatedBW occurred a error");
				die(json_encode($response));
			}
			$stmt->close();
		}
		
		// MeasuredBW
		$counter = $data['MeasuredBW']['counter'];
		for ($i = 0; $i < $counter; $i++) {
			$sql = "INSERT INTO MeasuredBW (ID, bitrate, sessionTimeInMs, dataType) VALUES (?, ?, ?, ?)";
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param('iiii', $myID, $data['MeasuredBW'][$i][0], $data['MeasuredBW'][$i][1], $dataType);
			$result = $result && $stmt->execute();
			if ( !$result) {
				$stmt->close();
				$mysqli->close();
				$response = array("Status" => "Inserting into table MeasuredBW occurred a error");
				die(json_encode($response));
			}
			$stmt->close();
		}
		
		// PresentationBitrate
		$counter = $data['PresentationBitrate']['counter'];
		for ($i = 0; $i < $counter; $i++) {
			$sql = "INSERT INTO PresentationBitrate (ID, bitrate, sessionTimeInMs, dataType) VALUES (?, ?, ?, ?)";
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param('iiii', $myID, $data['PresentationBitrate'][$i][0], $data['PresentationBitrate'][$i][1], $dataType);
			$result = $result && $stmt->execute();
			if ( !$result) {
				$stmt->close();
				$mysqli->close();
				$response = array("Status" => "Inserting into table PresentationBitrate occurred a error");
				die(json_encode($response));
			}
			$stmt->close();
		}
		
		// LoadedVideoBitrate
		$counter = $data['LoadedVideoBitrate']['counter'];
		for ($i = 0; $i < $counter; $i++) {
			$sql = "INSERT INTO LoadedVideoBitrate (ID, bitrate, sessionTimeInMs, mediaStartTimeInMs, dataType) VALUES (?, ?, ?, ?, ?)";
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param('iiiii', $myID, $data['LoadedVideoBitrate'][$i][0], $data['LoadedVideoBitrate'][$i][1], $data['LoadedVideoBitrate'][$i][2], $dataType);
			$result = $result && $stmt->execute();
			if ( !$result) {
				$stmt->close();
				$mysqli->close();
				$response = array("Status" => "Inserting into table LoadedVideoBitrate occurred a error");
				die(json_encode($response));
			}
			$stmt->close();
		}
		
		// BufferDelayRecord
		$counter = $data['BufferDelayRecord']['counter'];
		for ($i = 0; $i < $counter; $i++) {
			$sql = "INSERT INTO BufferDelayRecord (ID, bufferDelayInMs, delayStartInMs, delayEndInMs, dataType) VALUES (?, ?, ?, ?, ?)";
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param('iiiii', $myID, $data['BufferDelayRecord'][$i][0], $data['BufferDelayRecord'][$i][1], $data['BufferDelayRecord'][$i][2], $dataType);
			$result = $result && $stmt->execute();
			if ( !$result) {
				$stmt->close();
				$mysqli->close();
				$response = array("Status" => "Inserting into table BufferDelayRecord occurred a error");
				die(json_encode($response));
			}
			$stmt->close();
		}
		
		// BufferDuration
		$counter = $data['BufferDuration']['counter'];
		for ($i = 0; $i < $counter; $i++) {
			$sql = "INSERT INTO BufferDuration (ID, bufferDuration, sessionTimeInMs, dataType) VALUES (?, ?, ?, ?)";
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param('iiii', $myID, $data['BufferDuration'][$i][0], $data['BufferDuration'][$i][1], $dataType);
			$result = $result && $stmt->execute();
			if ( !$result) {
				$stmt->close();
				$mysqli->close();
				$response = array("Status" => "Inserting into table BufferDuration occurred a error");
				die(json_encode($response));
			}
			$stmt->close();
		}
		
		// VidelQualityLevel
		$counter = $data['VideoQualityLevel']['counter'];
		for ($i = 0; $i < $counter; $i++) {
			$sql = "INSERT INTO VideoQualityLevel (ID, videoQualityLevel, sessionTimeInMs, dataType) VALUES (?, ?, ?, ?)";
			$stmt = $mysqli->prepare($sql);
			$stmt->bind_param('iiii', $myID, $data['VideoQualityLevel'][$i][0], $data['VideoQualityLevel'][$i][1], $dataType);
			$result = $result && $stmt->execute();
			if ( !$result) {
				$stmt->close();
				$mysqli->close();
				$response = array("Status" => "Inserting into table VideoQualityLevel occurred a error");
				die(json_encode($response));
			}
			$stmt->close();
		}
		
		// 所有table操作結束，提交
		if ($result) {
			$mysqli->commit();
			$sql = "call insertOtherInfoIntoPlaybackInfo()";
			$mysqli->query($sql);
			$mysqli->commit();
			$response = array("Status" => 200, "ID" => $myID);
			echo json_encode($response);
		} else {
			$response = array("Status" => "Insert into DB failed");
			echo json_encode($response);
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