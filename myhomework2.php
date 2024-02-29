<?php


// не звертайте на цю функцію уваги
// вона потрібна для того щоб правильно зчитати вхідні дані
function readHttpLikeInput()
{
    $f = fopen('php://stdin', 'r');
    $store = "";
    $toread = 0;
    while ($line = fgets($f)) {
        $store .= preg_replace("/\r/", "", $line);
        if (preg_match('/Content-Length: (\d+)/', $line, $m))
            $toread = $m[1] * 1;
        if ($line == "\r\n")
            break;
    }
    if ($toread > 0)
        $store .= fread($f, $toread);
    return $store;
}

$contents = readHttpLikeInput();

function parseTcpStringAsHttpRequest($string)
{
    return array(
        "method" => getMethod($string),
        "uri" => getUri($string),
        "headers" => getHeaders($string),
        "body" => getBody($string),
    );
}

function getHeaders($string)
{
    $lines = explode("\n", $string);

    $headers = array();

    for ($i = 1; $i < sizeof($lines); $i++) {
        if (strpos($lines[$i], ":")) {
            $keyAndValue = explode(": ", $lines[$i]);
            //$header[$keyAndValue[0]]= $keyAndValue[1];
            $headers[] = $keyAndValue;
        }
    }

    return $headers;
}

function getBody($string)
{
    $lines = explode("\n", $string);
    $body = end($lines);
    if (!strpos($body, ":")) {
        return $body;
    } else return "";
}

function getUri($string)
{
    $result = explode(" ", $string);
    return $result[1];
}

function getMethod($string)
{
    $array = explode(" ", $string);
    return $array[0];
}

$http = parseTcpStringAsHttpRequest($contents);
echo(json_encode($http, JSON_PRETTY_PRINT));
