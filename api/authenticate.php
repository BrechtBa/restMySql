<?php

	include_once('common.php');

	$token = '';
	$response_http = response_http(400);

	if( isset($_POST['login']) && isset($_POST['password']) ){
		
		//connect to the database
		$db = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DATABASE.';charset=utf8', MYSQL_USER, MYSQL_PASSWORD);
		// set the PDO error mode to exception
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$query = sprintf( "SELECT * FROM auth WHERE login='%s' AND password='%s'" ,$_POST['login'], create_hash($_POST['password']));

		$stmt = $db->query($query);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if( $user = $stmt->fetch() ){
			
			// set the valid database operations for the user
			if( $user['permission'] >= 9 ){
				$valid_uri = ['POST' => ['*'], 'GET' => ['*'], 'PUT' => ['*'], 'DELETE' => ['*']];
				$valid_data = [];
			}
			else{
				$valid_uri = ['POST' => ['table_b'], 
                              'GET' => ['table_a',sprintf('table_b/user_id/%s',$user['id'])],
                              'PUT' => [sprintf('table_b/user_id/%s',$user['id'])],
                              'DELETE' => [sprintf('table_b/user_id/%s',$user['id'])]
                             ];
				$valid_data = ['user_id'=>$user['id']];
			}

			// generate a json web token
			$payload = ['user_id'=>$user['id'],'permission'=>$user['permission'],'valid_uri'=>$valid_uri,'valid_data'=>$valid_data];
			$token = jwt_encode($payload);
			$response_http = response_http(201);

		}

		$db = null;
	}


	header('Content-Type: application/json; charset=utf-8');
	header(sprintf('HTTP/1.0 %s %s',$response_http['status'],$response_http['statusText']));
	echo json_encode($token);

?>
