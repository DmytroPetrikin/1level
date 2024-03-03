<?php
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

function outputHttpResponse($statuscode, $statusmessage, $headers, $body)
{
    $response = "HTTP/1.1 $statuscode $statusmessage\n";
    $response .= "Date: " . date("l, d F  Y h:i:sa") . "\n";
    $response .= "Server: Apache/2.2.14 (Win32)\n";
    $response .= "Content-Length: " . strlen($body) . "\n";
    $response .= "Connection: Closed\n";
    $response .= "Content-Type: text/html; charset=utf-8\n";
    $response .= "\n$body";

    echo $response;
}

function processHttpRequest($method, $uri, $headers, $body)
{
    $statuscode = getStatusCode($method, $uri);
    $statusmessage = getStatusMessage($method, $uri);
    $body = getResult($uri, $statusmessage);
    outputHttpResponse($statuscode, $statusmessage, $headers, $body);
}

function getResult($uri, $statusmessage)
{
    if ($statusmessage !== "OK") {
        return $statusmessage;
    }
    $array = explode("=", $uri);
    $nums = explode(",", end($array));
    $sum = 0;
    for ($i = 0; $i < sizeof($nums); $i++) {
        if (is_numeric($nums[$i])) {
            $sum += $nums[$i];
        }
    }
    return $sum;

}

function getStatusMessage($method, $uri)
{
    if (start(explode("?", $uri)) != "/sum") {
        return "Not Found";
    } elseif (strpos($uri, "?nums=") === false || strpos($method, "GET") === false) {
        return "Bad Request";
    } else {
        return "OK";
    }
}

function getStatusCode($method, $uri)
{
    if (start(explode("?", $uri)) != "/sum") {
        return "404";
    } elseif (strpos($uri, "?nums=") === false || strpos($method, "GET") === false) {
        return "400";
    } else {
        return "200";
    }
}

function start(array $explode)
{
    return $explode[0];
}

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
            $header = [
                $keyAndValue[0] => $keyAndValue[1]
            ];
            $headers[] = $header;
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

//$mystr = "GET /sum=1,2,3 HTTP/1.1
//Host: student.shpp.me
//";

$http = parseTcpStringAsHttpRequest($contents);
processHttpRequest($http["method"], $http["uri"], $http["headers"], $http["body"]);