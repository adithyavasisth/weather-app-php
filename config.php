<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'password');
define('DB_NAME', 'weatherdb');

define('API_URL', 'https://api.open-meteo.com/v1/forecast');
define('LATITUDE', 52.1583);
define('LONGITUDE', 4.5292);
define('LOCATION_NAME', 'Leiderdorp');

// Establishing database connection
function getDbConnection()
{
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}