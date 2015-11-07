<?php

	include_once('common.php');

	$id = 0;
	$response_http = response_http(400);

	if( isset($_POST['login']) && isset($_POST['password']) ){

		//connect to the database
		$db = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DATABASE.';charset=utf8', MYSQL_USER, MYSQL_PASSWORD);
		// set the PDO error mode to exception
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$query = sprintf( "SELECT count(*) FROM auth WHERE login='%s'" ,$_POST['login']);
		$stmt = $db->prepare($query);
		$stmt->execute();
		$numRows = $stmt->fetchColumn();	

		if( $numRows==0 ){

			// set the users permissions
			$query = "SELECT count(*) FROM auth";
			$stmt = $db->prepare($query);
			$stmt->execute();
			$numUsers = $stmt->fetchColumn();	
			if($numUsers==0){
				$permission = 9;
			}
			else{
				$permission = 1;
			}

			// add the user to the database
			// generate query
			$query = sprintf( "INSERT INTO auth (login,password,permission)  VALUES ('%s','%s','%s')" ,$_POST['login'],create_hash($_POST['password']),$permission );
			$id = $query;
			$stmt = $db->prepare($query);
			$stmt->execute();

			// get the last inserted id
			$stmt = $db->query( "SELECT LAST_INSERT_ID()" );
			$id = $stmt->fetch(PDO::FETCH_NUM);
			$id = $id[0];
			$response_http = response_http(201);
		}
		else{
			$id = -1;
			$response_http = response_http(409);
		}

		$db = null;
	}

	header('Content-Type: application/json; charset=utf-8');
	header(sprintf('HTTP/1.0 %s %s',$response_http['status'],$response_http['statusText']));
	echo json_encode($id);

?>

