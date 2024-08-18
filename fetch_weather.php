<?php

require_once "config.php";

$conn = getDbConnection();

// Fetch weather data from the Open Meteo API
function getWeatherData($latitude, $longitude)
{
    $params = [
        "latitude" => $latitude,
        "longitude" => $longitude,
        "hourly" => "temperature_2m,precipitation,wind_speed_10m",
        "daily" => "temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max",
        "timezone" => "auto",
        "past_days" => 7,
        "models" => "knmi_seamless"
    ];

    $query = http_build_query($params);
    $url = API_URL . '?' . $query;
    $response = file_get_contents($url);
    return json_decode($response, true);
}

// Store weather data in the database
function storeWeatherData($conn, $locationId, $weatherData)
{
    // Insert hourly data
    if (isset($weatherData['hourly'])) {
        $hourlyTimes = $weatherData['hourly']['time'];
        $hourlyTemperatures = $weatherData['hourly']['temperature_2m'];
        $hourlyPrecipitations = $weatherData['hourly']['precipitation'];
        $hourlyWindSpeeds = $weatherData['hourly']['wind_speed_10m'];

        $sqlQuery = $conn->prepare("DELETE FROM hourly_weather WHERE location_id = ?");
        $sqlQuery->bind_param("i", $locationId);
        $sqlQuery->execute();

        for ($i = 0; $i < count($hourlyTimes); $i++) {
            // Convert time to a MySQL datetime format
            $hourlyTime = str_replace('T', ' ', $hourlyTimes[$i]) . ':00';

            $temperature = $hourlyTemperatures[$i];
            $precipitation = $hourlyPrecipitations[$i];
            $windSpeed = $hourlyWindSpeeds[$i];

            $sqlQuery = $conn->prepare("INSERT INTO hourly_weather (location_id, date_time, temperature_2m, precipitation, wind_speed_10m) VALUES (?, ?, ?, ?, ?)");
            $sqlQuery->bind_param("isddd", $locationId, $hourlyTime, $temperature, $precipitation, $windSpeed);
            $sqlQuery->execute();
        }
    }

    // Insert daily data
    if (isset($weatherData['daily'])) {
        $dailyTimes = $weatherData['daily']['time'];
        $dailyMaxTemperatures = $weatherData['daily']['temperature_2m_max'];
        $dailyMinTemperatures = $weatherData['daily']['temperature_2m_min'];
        $dailyPrecipitationSums = $weatherData['daily']['precipitation_sum'];
        $dailyMaxWindSpeeds = $weatherData['daily']['wind_speed_10m_max'];

        $sqlQuery = $conn->prepare("DELETE FROM daily_weather where location_id = ?");
        $sqlQuery->bind_param("i", $locationId);
        $sqlQuery->execute();

        for ($i = 0; $i < count($dailyTimes); $i++) {
            $date = $dailyTimes[$i];
            $maxTemperature = $dailyMaxTemperatures[$i];
            $minTemperature = $dailyMinTemperatures[$i];
            $precipitationSum = $dailyPrecipitationSums[$i];
            $maxWindSpeed = $dailyMaxWindSpeeds[$i];

            $sqlQuery = $conn->prepare("INSERT INTO daily_weather (location_id, date, temperature_2m_max, temperature_2m_min, precipitation_sum, wind_speed_10m_max) VALUES (?, ?, ?, ?, ?, ?)");
            $sqlQuery->bind_param("isdddd", $locationId, $date, $maxTemperature, $minTemperature, $precipitationSum, $maxWindSpeed);
            $sqlQuery->execute();
        }
    }
}

$latitude = LATITUDE;
$longitude = LONGITUDE;
$locationName = LOCATION_NAME;
// Location information
$locationQuery = $conn->prepare("SELECT id FROM weather_locations WHERE location_name = ?");
$locationQuery->bind_param("s", $locationName);
$locationQuery->execute();
$locationQuery->store_result();

if ($locationQuery->num_rows > 0) {
    $locationQuery->bind_result($locationId);
    $locationQuery->fetch();
} else {
    $locationInsert = $conn->prepare("INSERT INTO weather_locations (latitude, longitude, location_name) VALUES (?, ?, ?)");
    $locationInsert->bind_param("dds", $latitude, $longitude, $locationName);
    $locationInsert->execute();
    $locationId = $locationInsert->insert_id;
}

$weatherData = getWeatherData($latitude, $longitude);
storeWeatherData($conn, $locationId, $weatherData);

$conn->close();

echo "Weather data fetched and stored successfully!";
?>