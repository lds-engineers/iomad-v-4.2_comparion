<?php

$curl = curl_init();

curl_setopt_array($curl, [
	CURLOPT_URL => "https://facematch.p.rapidapi.com/API/verify/Facematch",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS => json_encode([
		'method' => 'facevalidate',
		'txn_id' => 'test-f23a-4bed-88fa-270befab4407',
		'clientid' => '222',
		'image_url1' => 'https://phantom-marca.unidadeditorial.es/6b2856a3418f09b17af61143079c53a6/resize/828/f/jpg/assets/multimedia/imagenes/2024/06/15/17184214562297.jpg',
		'image_url2' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/18/Lionel-Messi-Argentina-2022-FIFA-World-Cup_sharpness.jpg/375px-Lionel-Messi-Argentina-2022-FIFA-World-Cup_sharpness.jpg'
	]),
	CURLOPT_HTTPHEADER => [
		"Content-Type: application/json",
		"x-rapidapi-host: facematch.p.rapidapi.com",
		"x-rapidapi-key: 6705a4b80emshfdf0c05e7294147p193596jsna4936e46b7e2"
	],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	echo $response;
}