<?php
include_once('./hidden.php');
include_once('./sanitize.php');

$apiKey = Hidden::API;

// Sanitize user input
$city = isset($_GET['city']) ? sanitizeCity($_GET['city']) : "New York";
$unit = isset($_GET['unit']) ? sanitizeUnit($_GET['unit']) : "imperial";

// Build API URLs
$currentUrl = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}&units={$unit}";
$forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q={$city}&appid={$apiKey}&units={$unit}";

// Fetch data safely
$currentJson = safeFileGetContents($currentUrl);
$forecastJson = safeFileGetContents($forecastUrl);

$currentData = $currentJson ? json_decode($currentJson, true) : null;
$forecastData = $forecastJson ? json_decode($forecastJson, true) : null;

// Validate API responses
if (!is_array($currentData) || !isset($currentData['cod']) || $currentData['cod'] != 200) {
    $currentData = null;
}

if (!is_array($forecastData) || !isset($forecastData['cod']) || $forecastData['cod'] != "200") {
    $forecastData = null;
}

// Aggregate daily forecast safely
$dailyForecast = [];
if ($forecastData && isset($forecastData['list']) && is_array($forecastData['list'])) {
    foreach ($forecastData['list'] as $item) {
        $date = substr($item['dt_txt'], 0, 10);
        $temp_min = safeFloat($item['main']['temp_min'] ?? null);
        $temp_max = safeFloat($item['main']['temp_max'] ?? null);
        $icon = $item['weather'][0]['icon'] ?? '';

        if (!isset($dailyForecast[$date])) {
            $dailyForecast[$date] = [
                'min' => $temp_min,
                'max' => $temp_max,
                'icon' => $icon
            ];
        } else {
            $dailyForecast[$date]['min'] = min($dailyForecast[$date]['min'], $temp_min);
            $dailyForecast[$date]['max'] = max($dailyForecast[$date]['max'], $temp_max);
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

    <?php if ($currentData): ?>
        <div class="weather-box">
            <h2><?php echo htmlspecialchars($currentData['name'] ?? 'Unknown'); ?></h2>
            <?php if (isset($currentData['weather'][0]['icon'])): ?>
                <img src="https://openweathermap.org/img/wn/<?php echo $currentData['weather'][0]['icon']; ?>@2x.png"
                     alt="<?php echo htmlspecialchars($currentData['weather'][0]['description'] ?? ''); ?>">
            <?php endif; ?>
            <p>🌡 Temp: <span id="currentTemp"><?php echo floatval($currentData['main']['temp'] ?? 0); ?></span>°<?php echo $unit==='imperial'?'F':'C'; ?></p>
            <p>💧 Humidity: <?php echo intval($currentData['main']['humidity'] ?? 0); ?>%</p>
            <p>🌬 Wind: <?php echo floatval($currentData['wind']['speed'] ?? 0); ?> <?php echo $unit==='imperial'?'mph':'m/s'; ?></p>
            <p>📝 Condition: <?php echo ucfirst(htmlspecialchars($currentData['weather'][0]['description'] ?? '')); ?></p>
        </div>
    <?php else: ?>
        <p>City not found or API error.</p>
    <?php endif; ?>

    <?php if (!empty($dailyForecast)): ?>
        <h3>5-Day Forecast</h3>
        <div class="forecast-container">
            <?php foreach ($dailyForecast as $date => $data): ?>
                <div class="forecast-day">
                    <p><?php echo htmlspecialchars(date("D, M j", strtotime($date))); ?></p>
                    <?php if (!empty($data['icon'])): ?>
                        <img src="https://openweathermap.org/img/wn/<?php echo $data['icon']; ?>@4x.png" alt="Weather icon">
                    <?php endif; ?>
                    <p>🌡 <?php echo floatval($data['min']); ?>° - <?php echo floatval($data['max']); ?>°</p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($forecastData && isset($forecastData['list']) && !empty($forecastData['list'])): ?>
        <div class="chart-container">
            <canvas id="weatherChart"></canvas>
        </div>

        <script>
        const forecastData = <?php echo json_encode($forecastData); ?>;
        const unit = <?php echo json_encode($unit); ?>;
        let labels = [];
        let temps = [];

        forecastData.list.forEach(item => {
            if(item.main && item.main.temp !== undefined){
                labels.push(item.dt_txt);
                temps.push(item.main.temp);
            }
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
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { maxTicksLimit: 10 } }
                }
            }
        });
        </script>
    <?php endif; ?>
</div>

</body>
</html>
