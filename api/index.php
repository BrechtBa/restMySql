<?php

	include_once('common.php');


	$response_http = response_http(400);
	$response_location = '';
	$response_data = [];

	// security: check if the token is signed correctly
	$result = jwt_decode(getallheaders()['Authentication']);
	//$result = ['status'=>1, 'payload'=>['user_id'=>1,'permission'=>1,'valid_uri'=>['POST' => ['*'], 'GET' => ['*'], 'PUT' => ['*'], 'DELETE' => []],'valid_data'=>['user_id'=>1]]];
	if( $result['status'] == 1 ){
		// set valid uri from payload
		$valid_uri = $result['payload']['valid_uri'];
		$valid_data = $result['payload']['valid_data'];
	}
	else{
		// default valid uri is nothing
		$valid_uri = ['POST' => [], 'GET' => [], 'PUT' => [], 'DELETE' => []];
		$valid_data = [];
	}



	//connect to the database
	$db = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DATABASE.';charset=utf8', MYSQL_USER, MYSQL_PASSWORD);
	// set the PDO error mode to exception
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// called uri
	$uri = $_SERVER['REQUEST_URI'];
	$uri = explode('index.php',$uri);
	$uri = $uri[1];


	$loc = array_slice( explode('/',$uri) ,1);
	// remove the last element if it is blank
	if($loc[sizeof($loc) - 1] == ""){
		array_pop($loc);
	}


	if(isset($_SERVER['REQUEST_METHOD'])){
		switch($_SERVER['REQUEST_METHOD']){
////////////////////////////////////////////////////////////////////////////////
// POST
////////////////////////////////////////////////////////////////////////////////
			case 'POST':
				//check if the uri is valid
				if( check_uri($uri,$valid_uri['POST']) ){

					// parse data
					$keys = [];
					$vals = [];
					$valid = true;
					foreach($_POST as $key => $val){
						$valid = $valid && check_data($key,$val,$valid_data);
						$keys[] = $key;
						$vals[] = "'".$val."'";
					}
					if($valid){
						// generate query
						$query = sprintf( "INSERT INTO %s (%s)  VALUES (%s)" ,$loc[0],implode(',',$keys),implode(',',$vals) );
						$stmt = $db->prepare($query);
						$stmt->execute();


						// get the last inserted id
						$stmt = $db->query( "SELECT LAST_INSERT_ID()" );
						$id = $stmt->fetch(PDO::FETCH_NUM);
						$id = $id[0];

						// generate the uri to get the last inserted item
						$response_location = sprintf('%s/%s',$loc[0],$id);
						$response_http = response_http(201);
					}
					else{
						$response_http = response_http(403);
					}
				}
				else{
					$response_http = response_http(403);
				}
				break;

////////////////////////////////////////////////////////////////////////////////
// GET
////////////////////////////////////////////////////////////////////////////////	
			case 'GET':
				//check if the uri is valid
				if( check_uri($uri,$valid_uri['GET']) ){
					switch(sizeof($loc)){
						case 1:
							$query = sprintf( "SELECT * FROM %s" ,$loc[0] );
							break;
						case 2:
							$query = sprintf( "SELECT * FROM %s WHERE id LIKE %s" ,$loc[0], $loc[1]);
							break;
						case 3:
							$query = sprintf( "SELECT * FROM %s WHERE %s LIKE %s" ,$loc[0], $loc[1], $loc[2]);
							break;

						case 5:
							$query = sprintf( "SELECT * FROM %s WHERE %s LIKE %s AND %s LIKE %s" ,$loc[0], $loc[1], $loc[2], $loc[3], $loc[4]);
							break;
					}

					$stmt = $db->query($query);
					$stmt->setFetchMode(PDO::FETCH_ASSOC);

					$response_data = [];
					while($row = $stmt->fetch()){
						$response_data[] = $row;
					}
			
					$response_http = response_http(201);
				}
				else{
					$response_http = response_http(403);
				}
				break;

////////////////////////////////////////////////////////////////////////////////
// PUT
////////////////////////////////////////////////////////////////////////////////
			case 'PUT':
				//check if the uri is valid
				if( check_uri($uri,$valid_uri['PUT']) ){
					// parse data
					parse_str(file_get_contents("php://input"),$post_vars);
					$keyvals = [];
					$valid = true;
					foreach($post_vars as $key => $val){
						$valid = $valid && check_data($key,$val,$valid_data);
						$keyvals[] = $key."='".$val."'";
					}
					if($valid){
						// generate query
						switch(sizeof($loc)){
							case 2:
								$query = sprintf( "UPDATE %s SET %s WHERE id=%s" ,$loc[0],implode(',',$keyvals),$loc[1] );
								break;
							case 3:
								$query = sprintf( "UPDATE %s SET %s WHERE %s='%s'" ,$loc[0],implode(',',$keyvals),$loc[1],$loc[2] );
								break;
							case 5:
								$query = sprintf( "UPDATE %s SET %s WHERE %s='%s' AND %s='%s'" ,$loc[0],implode(',',$keyvals),$loc[1],$loc[2],$loc[3],$loc[4] );
								break;
						}

						$stmt = $db->prepare($query);
						$stmt->execute();

						$response_http = response_http(201);
					}
					else{
						$response_http = response_http(403);
					}
				}
				else{
					$response_http = response_http(403);
				}
				break;

////////////////////////////////////////////////////////////////////////////////
// DELETE 
////////////////////////////////////////////////////////////////////////////////	
			case 'DELETE':
				//check if the uri is valid
				if( check_uri($uri,$valid_uri['DELETE']) ){
					// generate query
					switch(sizeof($loc)){
						case 2:
							$query = sprintf( "DELETE FROM %s WHERE id=%s" ,$loc[0],$loc[1] );
							break;
						case 3:
							$query = sprintf( "DELETE FROM  %s WHERE %s='%s'" ,$loc[0],$loc[1],$loc[2] );
							break;
						case 5:
							$query = sprintf( "DELETE FROM  %s WHERE %s='%s' AND %s='%s'" ,$loc[0],$loc[1],$loc[2],$loc[3],$loc[4] );
							break;
					}
					$stmt = $db->prepare($query);
					$stmt->execute();
					$response_http = response_http(201);
				}
				else{
					$response_http = response_http(403);
				}
				break;
		}
	}

	// close the connection
	$db = null;




// create the response header //////////////////////////////////////////////////
	header(sprintf('HTTP/1.0 %s %s',$response_http['status'],$response_http['statusText']));
	header('Location: '.$response_location);
	header('Content-Type: application/json; charset=utf-8');

// return the response /////////////////////////////////////////////////////////
	echo json_encode($response_data);




////////////////////////////////////////////////////////////////////////////////
// check_uri
////////////////////////////////////////////////////////////////////////////////
	function check_uri($uri,$valid_uri){
		$status = false;
		foreach($valid_uri as $checkuri){
			if( strpos($uri,$checkuri) || $checkuri=='*'){
				$status = true;
				break;
			}
		}
		return $status;
	}
////////////////////////////////////////////////////////////////////////////////
// check_data
////////////////////////////////////////////////////////////////////////////////
	function check_data($key,$val,$valid_data){
		$valid = true;
		foreach($valid_data as $valid_key => $valid_val){
			if($key == $valid_key && $val != $valid_val){
				$valid = false;
				break;
			}
		}
		return $valid;
	}

?>
