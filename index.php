<?php

include_once('./hidden.php');
include_once('./sanitize.php');

$apiKey = Hidden::API;  // Hardcoded API key (Fixed by adding hidden file.)

$city = isset($_GET['city']) ? $_GET['city'] : "New York"; // No sanitization on input
$city = sanitize($city); // Added sanitization to user input.

$currentUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units=imperial";
$forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&appid={$apiKey}&units=imperial";

$currentData = json_decode(file_get_contents($currentUrl), true);
$forecastData = json_decode(file_get_contents($forecastUrl), true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weather App</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container">
    <h1>🌤 Weather App</h1>

    <form method="GET">
        <input type="text" name="city" placeholder="Enter city..." required>
        <button type="submit">Search</button>
    </form>

    <?php if ($currentData && $currentData['cod'] == 200): ?>

        <div class="weather-box">
            <h2><?php echo $currentData['name']; ?></h2>
            <p>🌡 Temp: <?php echo $currentData['main']['temp']; ?> °F</p>
            <p>💧 Humidity: <?php echo $currentData['main']['humidity']; ?>%</p>
            <p>🌬 Wind: <?php echo $currentData['wind']['speed']; ?> m/s</p>
        </div>

    <?php else: ?>
        <p>City not found.</p>
    <?php endif; ?>

    <canvas id="weatherChart"></canvas>
</div>

<script>
const forecastData = <?php echo json_encode($forecastData); ?>;

let labels = [];
let temps = [];

forecastData.list.forEach(item => {
    labels.push(item.dt_txt);
    temps.push(item.main.temp);
});

const ctx = document.getElementById('weatherChart').getContext('2d');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Temperature (°C)',
            data: temps,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: {
                ticks: {
                    maxTicksLimit: 10
                }
            }
        }
    }
});
</script>

</body>
</html>
