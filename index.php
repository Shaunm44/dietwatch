<!DOCTYPE html>
<html>
<head>
	<title>DietWatch</title>
</head>
<body>
	<h1>Blah</h1>
	<?php
		error_reporting(E_ALL);

		require 'sendToIFTTT.php';

		// Initiate API
		$usdaKey = 'SuQtAOcE2hC5afmwHHTCBR6NkqdJQfjiqWsJhcyr';
		$searchApi = "https://api.nal.usda.gov/ndb/search/?format=json&api_key=" . $usdaKey;

		// Get the food item to search for
		// If there's no item, send a "failed entry" text
		$params = $_GET;
		$foodSearch = urldecode( array_shift($params) );
		$from = urldecode( array_shift($params) );

		if(!$foodSearch) {
			$error = "Failed to read message";
			sendError($error);
			return;
		}

		// Find the ndbno for the food by using the Search API
		$searchUrl = $searchApi . '&q=' . urlencode($foodSearch);
		$searchResults = file_get_contents($searchUrl);

		// If call to website/API failed, then upload to journal with 0 calories
		if(!$searchResults) {
			uploadFoodEntry($foodSearch);
			return;
		}

		// Otherwise, convert from JSON string to an object
		$searchResults = json_decode($searchResults);

		// If searchResults object contains 'list'
		// and 'list' contains 'item'
		// and 'item' has at least one entry,
		// then get the values from that one element.
		if( property_exists($searchResults, 'list')
			&& property_exists($searchResults->list, 'item')
			&& count($searchResults->list->item) > 0 ) {

			// Extract db number and name of first object in list
			$elem = $searchResults->list->item[0];
			$ndbno = $elem->ndbno;
			$name = $elem->name;
		}
		// Otherwise, assume search returned no results (semi-failed)
		// Send to food journal with 0 calories
		else {
			uploadFoodEntry($foodSearch);
			return;
		}

		// If USDA db number not found, send to food journal with 0 calories
		if(!$ndbno) {
			uploadFoodEntry($foodSearch, 0, 0, $name, $ndbno);
			return;
		}

		// Use the Report API to find the calorie and nutrients info
		// If no information found, send to food journal with 0 calories
		// Otherwise, send food title, calories and carbs to webhook
		$reportApi = "https://api.nal.usda.gov/ndb/reports/?format=json&api_key=" . $usdaKey;
		$reportUrl = $reportApi . "&ndbno=" . $ndbno;
		$reportResults = file_get_contents($reportUrl);

		// If call to website/API failed, then upload to journal with 0 calories
		if(!$reportResults) {
			uploadFoodEntry($foodSearch, 0, 0, $name, $ndbno);
			return;
		}

		// Otherwise, convert from JSON string to an object
		$reportResults = json_decode($reportResults);

		// If the report contains 'report' property
		// and 'report' contains 'food'
		// and 'food' contains 'nutrients',
		// then extract the nutrients values (first measure provided) for cal and carbs
		if(property_exists($reportResults, 'report')
			&& property_exists($reportResults->report, 'food')
			&& property_exists($reportResults->report->food, 'nutrients')) {

			$nutrients = $reportResults->report->food->nutrients;

			// Initiate at -1; if both values are found (0 or greater), then break out of loop
			$cal = -1;
			$carb = -1;

			// Extract calories and carbs from nutrients list
			foreach ($nutrients as $key => $ntr) {
				// USDA nutrient_id for calories is 208
				if($ntr->nutrient_id == "208") {
					$cal = $ntr->measures[0]->value;
				}

				// USDA nutrient_id for carbohydrates is 205
				if($ntr->nutrient_id == "205") {
					$carb = $ntr->measures[0]->value;
				}

				// If cal and carb values set, break out of loop
				if($carb >= 0 && $cal >= 0) {
					break;
				}
			}

			// If values not found for carb or cal, set to 0
			if($cal < 0) {
				$cal = 0;
			}
			if($carb < 0) {
				$carb = 0;
			}

			echo $name;
			echo "<br>";
			echo "Calories: " . $cal;

			// Send on final information
			uploadFoodEntry($foodSearch, $cal, $carb, $name, $ndbno);
			return;
		}

		// Otherwise, upload to journal with 0 calories
		uploadFoodEntry($foodSearch, 0, $name, $ndbno);
		return;

		// Ping error webhook to send error message to user
		function sendError($msg) {

		}

		// Keep track of what food was requested and what the search returned
		function logSearches($food, $dbFoodName, $ndbno, $cal, $carb) {
			// Fields
			// - Timestamp
			// - Food name from user's text
			// - Database matched food item
			// - USDA database number
			// - Search url

			// Fire IFTTT logDiet webhook event
			$url = 'https://maker.ifttt.com/trigger/logDiet/with/key/ceIutD1UvWmPMcJ2SBNAgQ';
		  $data = array('value1' => $food,
				'value2' => $cal,
				'value3' => $GLOBALS['searchUrl']);

		  $options = array(
		      'http' => array(
		          'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		          'method'  => 'POST',
		          'content' => http_build_query($data)
		      )
		  );

		  $context  = stream_context_create($options);
		  $result = file_get_contents($url, false, $context);
		  if ($result === FALSE) {
		    /* Handle error */
		  }
		}

		// Send food to be added to journal log
		// Note: default value for calories is 0 to make upload simple
		// Note: Uploading entry to journal adds to searchLog automatically
		function uploadFoodEntry($food, $cal=0, $carb=0, $dbFoodName='', $ndbno='') {

			logSearches($food, $dbFoodName, $ndbno, $cal, $carb);


			/*  Disabling because it's no longer needed with the logDiet webhook
			sendToIfttt($GLOBALS['from'], $food, $cal, $dbFoodName);
			*/
		}
	?>

</body>
</html>
