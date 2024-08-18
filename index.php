<?php
require_once 'config.php';
$conn = getDBConnection();

// Set the timezone to GMT+2
date_default_timezone_set('Europe/Amsterdam');

// Get the current timestamp
$currentTimestamp = date('Y-m-d H:i:s');

$location_id = 1;

// Fetch the closest weather data to the current datetime
$currentWeatherQuery = "
    SELECT *, ABS(TIMESTAMPDIFF(SECOND, date_time, '$currentTimestamp')) AS time_diff
    FROM hourly_weather 
    WHERE location_id = $location_id 
    ORDER BY time_diff ASC 
    LIMIT 1";
$currentWeatherResult = $conn->query($currentWeatherQuery);
$currentWeather = $currentWeatherResult->fetch_assoc();

// Fetch past 7 days of weather data
$past7DaysQuery = "
    SELECT * 
    FROM daily_weather 
    WHERE location_id = $location_id
    AND date < CURDATE()
    ORDER BY date ASC 
    LIMIT 7";
$past7DaysResult = $conn->query($past7DaysQuery);

// Fetch next 7 days of forecast data
$next7DaysQuery = "
    SELECT * 
    FROM daily_weather 
    WHERE location_id = $location_id 
    AND date > CURDATE() 
    ORDER BY date ASC 
    LIMIT 7";
$next7DaysResult = $conn->query($next7DaysQuery);

function isThermometer($temperature)
{
    return $temperature > 40 || $temperature < 0;
}

function isSwim($precipitation)
{
    return $precipitation > 0;
}

function isHurricane($windSpeed)
{
    return $windSpeed > 25;
}

function temperatreTrend($t1, $t2)
{
    if ($t1 < $t2) {
        return true;
    } elseif ($t1 > $t2) {
        return false;
    } else {
        return null;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Weather Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">
        <h1>Weather Dashboard for Leiderdorp</h1>

        <div class="current-weather">
            <h2>Current Weather</h2>
            <div class="weather-info">
                <span><strong>Date & Time:</strong> <?php echo $currentWeather['date_time']; ?></span>
                <span><strong>Temperature:</strong> <?php echo $currentWeather['temperature_2m']; ?> °C</span>
                <span><strong>Precipitation:</strong> <?php echo $currentWeather['precipitation']; ?> mm</span>
                <span><strong>Wind Speed:</strong> <?php echo $currentWeather['wind_speed_10m']; ?> km/h</span>

                <?php if (isThermometer($currentWeather['temperature_2m'])): ?>
                    <img src="./images/thermometer.png" alt="Extreme Temperature" width="32">
                <?php endif; ?>

                <?php if (isSwim($currentWeather['precipitation'])): ?>
                    <img src="./images/swim.png" alt="Rainy" width="32">
                <?php endif; ?>

                <?php if (isHurricane($currentWeather['wind_speed_10m'])): ?>
                    <img src="./images/hurricane.png" alt="Windy" width="32">
                <?php endif; ?>
            </div>
        </div>

        <div class="table-container">
            <h2 class="table-title">Past 7 Days</h2>
            <div class="scrollable">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Max Temperature (°C)</th>
                            <th>Min Temperature (°C)</th>
                            <th>Precipitation (mm)</th>
                            <th>Max Wind Speed (km/h)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $previousRow = null;
                        $trend = null;
                        while ($row = $past7DaysResult->fetch_assoc()):
                            if ($previousRow !== null) {
                                $trend = temperatreTrend($previousRow['temperature_2m_max'], $row['temperature_2m_max']);
                            }
                            ?>
                            <tr>
                                <td><?php echo $row['date']; ?></td>
                                <td><?php echo $row['temperature_2m_max']; ?></td>
                                <td><?php echo $row['temperature_2m_min']; ?></td>
                                <td><?php echo $row['precipitation_sum']; ?></td>
                                <td><?php echo $row['wind_speed_10m_max']; ?></td>
                                <td>
                                    <?php if (isset($trend) && $trend == true): ?>
                                        <img src="./images/happy.png" alt="Hotter" width="32">
                                    <?php elseif (isset($trend) && $trend == false): ?>
                                        <img src="./images/sad.png" alt="Colder" width="32">
                                    <?php endif; ?>
                                    <?php if (isThermometer($row['temperature_2m_max']) || isThermometer($row['temperature_2m_min'])): ?>
                                        <img src="./images/thermometer.png" alt="Extreme Temperature" width="32">
                                    <?php endif; ?>
                                    <?php if (isSwim($row['precipitation_sum'])): ?>
                                        <img src="./images/swim.png" alt="Rainy" width="32">
                                    <?php endif; ?>
                                    <?php if (isHurricane($row['wind_speed_10m_max'])): ?>
                                        <img src="./images/hurricane.png" alt="Windy" width="32">
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            $previousRow = $row;
                        endwhile;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Next 7 Days Section -->
        <div class="table-container">
            <h2 class="table-title">Next 7 Days</h2>
            <div class="scrollable">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Max Temperature (°C)</th>
                            <th>Min Temperature (°C)</th>
                            <th>Precipitation (mm)</th>
                            <th>Max Wind Speed (km/h)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $previousRow = null;
                        $trend = null;
                        while ($row = $next7DaysResult->fetch_assoc()):
                            if ($previousRow !== null) {
                                $trend = temperatreTrend($previousRow['temperature_2m_max'], $row['temperature_2m_max']);
                            } else {
                                $trend = temperatreTrend($currentWeather['temperature_2m'], $row['temperature_2m_max']);
                            }
                            ?>
                            <tr>
                                <td><?php echo $row['date']; ?></td>
                                <td><?php echo $row['temperature_2m_max']; ?></td>
                                <td><?php echo $row['temperature_2m_min']; ?></td>
                                <td><?php echo $row['precipitation_sum']; ?></td>
                                <td><?php echo $row['wind_speed_10m_max']; ?></td>
                                <td>
                                    <?php if (isset($trend) && $trend == true): ?>
                                        <img src="./images/happy.png" alt="Hotter" width="32">
                                    <?php elseif (isset($trend) && $trend == false): ?>
                                        <img src="./images/sad.png" alt="Colder" width="32">
                                    <?php endif; ?>
                                    <?php if (isThermometer($row['temperature_2m_max']) || isThermometer($row['temperature_2m_min'])): ?>
                                        <img src="./images/thermometer.png" alt="Extreme Temperature" width="32">
                                    <?php endif; ?>
                                    <?php if (isSwim($row['precipitation_sum'])): ?>
                                        <img src="./images/swim.png" alt="Rainy" width="32">
                                    <?php endif; ?>
                                    <?php if (isHurricane($row['wind_speed_10m_max'])): ?>
                                        <img src="./images/hurricane.png" alt="Windy" width="32">
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            $previousRow = $row;
                        endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>