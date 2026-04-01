<?php
include_once('./hidden.php');
include_once('./sanitize.php');

$apiKey = Hidden::API;

$city = isset($_GET['city']) ? sanitize($_GET['city']) : "New York";
$unit = isset($_GET['unit']) && $_GET['unit'] === 'metric' ? 'metric' : 'imperial';

$currentUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units={$unit}";
$forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&appid={$apiKey}&units={$unit}";

$currentData = json_decode(file_get_contents($currentUrl), true);
$forecastData = json_decode(file_get_contents($forecastUrl), true);

// Aggregate daily forecast
$dailyForecast = [];
if ($forecastData && $forecastData['cod'] == "200") {
    foreach ($forecastData['list'] as $item) {
        $date = substr($item['dt_txt'], 0, 10);
        if (!isset($dailyForecast[$date])) {
            $dailyForecast[$date] = [
                'min' => $item['main']['temp_min'],
                'max' => $item['main']['temp_max'],
                'icon' => $item['weather'][0]['icon']
            ];
        } else {
            $dailyForecast[$date]['min'] = min($dailyForecast[$date]['min'], $item['main']['temp_min']);
            $dailyForecast[$date]['max'] = max($dailyForecast[$date]['max'], $item['main']['temp_max']);
        }
    }
}
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
        <input type="text" name="city" placeholder="Enter city..." required value="<?php echo htmlspecialchars($city); ?>">
        <select name="unit">
            <option value="imperial" <?php if($unit==='imperial') echo 'selected'; ?>>°F</option>
            <option value="metric" <?php if($unit==='metric') echo 'selected'; ?>>°C</option>
        </select>
        <button type="submit">Search</button>
    </form>

    <?php if ($currentData && $currentData['cod'] == 200): ?>
        <div class="weather-box">
            <h2><?php echo $currentData['name']; ?></h2>
            <img src="https://openweathermap.org/img/wn/<?php echo $currentData['weather'][0]['icon']; ?>@2x.png" 
                 alt="<?php echo $currentData['weather'][0]['description']; ?>">
            <p>🌡 Temp: <span id="currentTemp"><?php echo $currentData['main']['temp']; ?></span>°<?php echo $unit==='imperial'?'F':'C'; ?></p>
            <p>💧 Humidity: <?php echo $currentData['main']['humidity']; ?>%</p>
            <p>🌬 Wind: <?php echo $currentData['wind']['speed']; ?> <?php echo $unit==='imperial'?'mph':'m/s'; ?></p>
            <p>📝 Condition: <?php echo ucfirst($currentData['weather'][0]['description']); ?></p>
        </div>
    <?php else: ?>
        <p>City not found.</p>
    <?php endif; ?>

    <?php if (!empty($dailyForecast)): ?>
        <h3>5-Day Forecast</h3>
        <div class="forecast-container">
            <?php foreach ($dailyForecast as $date => $data): ?>
                <div class="forecast-day">
                    <p><?php echo date("D, M j", strtotime($date)); ?></p>
                    <img src="https://openweathermap.org/img/wn/<?php echo $data['icon']; ?>@2x.png" alt="Weather icon">
                    <p>🌡 <?php echo $data['min']; ?>° - <?php echo $data['max']; ?>°</p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <canvas id="weatherChart"></canvas>
</div>

<script>
const forecastData = <?php echo json_encode($forecastData); ?>;
const unit = '<?php echo $unit; ?>';
let labels = [];
let temps = [];

forecastData.list.forEach(item => {
    labels.push(item.dt_txt);
    temps.push(item.main.temp);
});

const ctx = document.getElementById('weatherChart').getContext('2d');

const weatherChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Temperature (°' + (unit==='imperial'?'F':'C') + ')',
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
            x: { ticks: { maxTicksLimit: 10 } }
        }
    }
});
</script>

</body>
</html>
