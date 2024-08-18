-- Create the weatherdb database
CREATE DATABASE IF NOT EXISTS weatherdb;

-- Use the weatherdb database
USE weatherdb;

-- Create the weather_locations table
CREATE TABLE IF NOT EXISTS weather_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    latitude FLOAT NOT NULL,
    longitude FLOAT NOT NULL,
    location_name VARCHAR(255)
);

-- Create the hourly_weather table
CREATE TABLE IF NOT EXISTS hourly_weather (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    date_time DATETIME NOT NULL,
    temperature_2m FLOAT,
    precipitation FLOAT,
    wind_speed_10m FLOAT,
    FOREIGN KEY (location_id) REFERENCES weather_locations(id) ON DELETE CASCADE
);

-- Create an index on the date_time column for faster querying
CREATE INDEX idx_date_time ON hourly_weather(date_time);

-- Create the daily_weather table
CREATE TABLE IF NOT EXISTS daily_weather (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_id INT NOT NULL,
    date DATE NOT NULL,
    temperature_2m_max FLOAT,
    temperature_2m_min FLOAT,
    precipitation_sum FLOAT,
    wind_speed_10m_max FLOAT,
    FOREIGN KEY (location_id) REFERENCES weather_locations(id) ON DELETE CASCADE
);

-- Create an index on the date column for faster querying
CREATE INDEX idx_date ON daily_weather(date);
