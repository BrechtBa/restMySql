<?php

	define("MYSQL_HOST", "localhost");
	define("MYSQL_USER", "testdb");
	define("MYSQL_PASSWORD", "testdb");
	define("MYSQL_DATABASE", "testdb");
	define("PASSWORD_SALT", "passwordsalt");
	define("JWT_KEY", "jwtkey");

	define("ADMIN_PERMISSION", 9);   // 1st user to register when there are no users will recieve admin permissions
	define("DEFAULT_PERMISSION", 1);
	

	// all uri's are checked to be allowed according to the values below and the user permission
    // the valid request strings will be parsed using sprintf( ,$user['id'])
	// '*' implies all requests are valid
	$valid_uri = [1 => ['POST' => ['table_b'], 
                        'GET' => ['table_a','table_b/user_id/%s'],
                        'PUT' => ['table_b/user_id/%s'],
                        'DELETE' => ['table_b/user_id/%s']],
				  9 => ['POST' => ['*'],
						'GET' => ['*'],
					    'PUT' => ['*'],
					    'DELETE' => ['*']]
				 ];


	// post and put data is checked according to the values below and the user permission
	// when a key is encountered it must correspond to the allowable value
	// values are parsed using sprintf( ,$user['id'])
	$valid_data = [1 => ['user_id' => '%s'],
				   9 => []
                  ];


?>
