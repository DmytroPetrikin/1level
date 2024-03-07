<?php
$counterFile = 'counter';

if (!file_exists($counterFile)) {
    touch($counterFile);
}

$currentCount = (int)file_get_contents($counterFile);

echo "<h1>Counter: $currentCount</h1>";

$currentCount++;

file_put_contents($counterFile, $currentCount);

if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
    file_put_contents($counterFile, 0);
    header('Location: http://localhost/1level/myhomework6.php');
}
?>

<a href="?reset=true">Обнулити лічильник</a>


