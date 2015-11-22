# restMySql
A simple REST, CRUD api for MySql in php including authentication and request validation

# Requirements
* MySql
* php

# Installation
Copy the api folder to your project and edit the mysql connection data and allowed requests configuration in config.php
RestMySql requires a mysql database with an "auth" table having "login", "password" and "permission" columns. A registration and authentication php script is supplied. These workshop best with Ajax requests as no output formatting is done.

# Configuration
In the config.php file basic MySql connection data must be supplied.
Furthermore valid request uri's and valid post data can be supplied to be user permission dependent. This is configured by supplying an array which links a user permission integer to an array of possibilities. The user id cn be used in this configuration by using the '''%s''' identifier.
