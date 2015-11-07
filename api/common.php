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
?>
