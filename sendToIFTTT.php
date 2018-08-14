<?php

$lookup = array(
  '9544453119' => 'ceIutD1UvWmPMcJ2SBNAgQ',
  '4782134228' => 'rQktIqIi-5kntt79T8RAV');

// Send an update to IFTTT of the food name, calories and carbs
function sendToIfttt($from, $name='', $cal=0, $dbName='') {
  $url = 'https://maker.ifttt.com/trigger/diet/with/key/' + $lookup[$from];
  $data = array('value1' => $name, 'value2' => $cal, 'value3' => $dbName);

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

  // var_dump($result);
}
