<?php

////////////////////////////////////////////////////////////////////////////////
// constants
////////////////////////////////////////////////////////////////////////////////
define("MYSQL_HOST", "localhost");
define("MYSQL_USER", "testdb");
define("MYSQL_PASSWORD", "testdb");
define("MYSQL_DATABASE", "testdb");
define("PASSWORD_SALT", "passwordsalt");
define("JWT_KEY", "jwtkey");


////////////////////////////////////////////////////////////////////////////////
// database access
////////////////////////////////////////////////////////////////////////////////
$response_http = response_http(201);
$response_location = '';
$response_data = [];

// security: check if the token is signed correctly
$result = jwt_decode(getallheaders()['Authentication']);
//$result['status'] == 1

if( 1 ){
	//connect to the database
	$db = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DATABASE.';charset=utf8', MYSQL_USER, MYSQL_PASSWORD);
	// set the PDO error mode to exception
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	// called uri
	$uri = $_SERVER['REQUEST_URI'];
	$loc = explode('index.php',$uri);
	$loc = $loc[1];
	$loc = array_slice( explode('/',$loc) ,1);
	// remove the last element if it is blank
	if($loc[sizeof($loc) - 1] == ""){
		array_pop($loc);
	}


	if(isset($_SERVER['REQUEST_METHOD'])){
		switch($_SERVER['REQUEST_METHOD']){
// POST ////////////////////////////////////////////////////////////////////////
			case 'POST':
				// parse data
				$keys = [];
				$vals = [];
				foreach($_POST as $key => $val){
					$keys[] = $key;
					$vals[] = "'".$val."'";
				}
				
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
				break;

// GET /////////////////////////////////////////////////////////////////////////	
			case 'GET':

				switch(sizeof($loc)){
					case 1:
						// get all entries
						$query = sprintf( "SELECT * FROM %s" ,$loc[0] );
						break;
					case 2:

						$query = sprintf( "SELECT * FROM %s WHERE id LIKE %s" ,$loc[0], $loc[1]);
						break;
					case 3:
						
						$query = sprintf( "SELECT * FROM %s WHERE %s LIKE %s" ,$loc[0], $loc[1], $loc[2]);
						break;
				}

				$stmt = $db->query($query);
				$stmt->setFetchMode(PDO::FETCH_ASSOC);

				$response_data = [];
				while($row = $stmt->fetch()){
					$response_data[] = $row;
				}
				
				$response_http = response_http(201);
				break;

// PUT /////////////////////////////////////////////////////////////////////////	
			case 'PUT':
				// parse data
				parse_str(file_get_contents("php://input"),$post_vars);
				$keyvals = [];
				foreach($post_vars as $key => $val){
					$keyvals[] = $key."='".$val."'";
				}
				
				// generate query
				switch(sizeof($loc)){
					case 2:
						$query = sprintf( "UPDATE %s SET %s WHERE id=%s" ,$loc[0],implode(',',$keyvals),$loc[1] );
						break;
					case 3:
						$query = sprintf( "UPDATE %s SET %s WHERE %s='%s'" ,$loc[0],implode(',',$keyvals),$loc[1],$loc[2] );
						break;
				}

				$stmt = $db->prepare($query);
				$stmt->execute();

				$response_data = $query;
				$response_http = response_http(201);
				break;

// DELETE //////////////////////////////////////////////////////////////////////	
			case 'DELETE':
				// generate query
				switch(sizeof($loc)){
					case 2:
						$query = sprintf( "DELETE FROM %s WHERE id=%s" ,$loc[0],$loc[1] );
						break;
					case 3:
						$query = sprintf( "DELETE FROM  %s WHERE %s='%s'" ,$loc[0],$loc[1],$loc[2] );
						break;
				}
				$stmt = $db->prepare($query);
				$stmt->execute();

				$response_data = $query;
				$response_http = response_http(201);
				break;
		}
	}

	// close the connection
	$db = null;
		
	
}

//$response_data = $loc;

// create the response header //////////////////////////////////////////////////
header(sprintf('HTTP/1.0 %s %s',$response_http['status'],$response_http['statusText']));
header('Location: '.$response_location);
//header(sprintf('Content-Type: application/json; charset=utf-8'));


echo json_encode($response_data);







////////////////////////////////////////////////////////////////////////////////
// response_http
////////////////////////////////////////////////////////////////////////////////
	function response_http($code){
		switch($code){
			case 200:
				return ['status' => 200, 'statusText' => 'OK'];
				break;
			case 201:
				return ['status' => 201, 'statusText' => 'Created'];
				break;
			case 204:
				return ['status' => 204, 'statusText' => 'No Content'];
				break;
			case 400:
				return ['status' => 400, 'statusText' => 'Bad Request'];
				break;
			case 401:
				return ['status' => 401, 'statusText' => 'Unauthorized'];
				break;
			case 403:
				return ['status' => 403, 'statusText' => 'Forbidden'];
				break;
			case 404:
				return ['status' => 404, 'statusText' => 'Not Found'];
				break;
			case 405:
				return ['status' => 404, 'statusText' => 'Method Not Allowed'];
				break;
			case 409:
				return ['status' => 409, 'statusText' => 'Conflict'];
				break;
			case 503:
				return ['status' => 503, 'statusText' => 'Service Unavailable'];
				break;
		}
	}

////////////////////////////////////////////////////////////////////////////////
// password hashing
////////////////////////////////////////////////////////////////////////////////
	function create_hash($password){
		return base64_encode(hash('sha256', $password.PASSWORD_SALT, true));
	}

////////////////////////////////////////////////////////////////////////////////
// json web tokens
////////////////////////////////////////////////////////////////////////////////
	function jwt_signature($header,$payload){
		return base64_encode(hash_hmac($header['alg'],base64_encode(json_encode($header)).'.'.base64_encode(json_encode($payload)),JWT_KEY));
	}
	function jwt_encode($payload){
		$header = array('alg' => 'SHA256', 'typ' => 'JWT');
		$signature =  jwt_signature($header,$payload);
		
		return base64_encode(json_encode($header)).'.'.base64_encode(json_encode($payload)).'.'.$signature;
	}
	function jwt_decode($token){
		$result = array('status' => -1, 'payload' =>array());
		if($token != ''){
			$parts = explode('.',$token);
			$header = json_decode(base64_decode($parts[0]),true);
			$payload = json_decode(base64_decode($parts[1]),true);
			$signature = $parts[2];
		
			// verify the signature
			if($signature == jwt_signature($header,$payload)){
				$result = array('status' => 1, 'payload' =>$payload);
			}
		}
		return $result;
	}

?>
