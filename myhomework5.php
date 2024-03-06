<?php
const START_DIRECTORY = "/home/dmytro/";

//function readHttpLikeInput()
//{
//    $f = fopen('php://stdin', 'r');
//    $store = "";
//    $toread = 0;
//    while ($line = fgets($f)) {
//        $store .= preg_replace("/\r/", "", $line);
//        if (preg_match('/Content-Length: (\d+)/', $line, $m))
//            $toread = $m[1] * 1;
//        if ($line == "\r\n")
//            break;
//    }
//    if ($toread > 0)
//        $store .= fread($f, $toread);
//    return $store;
//}
//
//$contents = readHttpLikeInput();

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
    $statuscode = getStatusCode($headers, $uri);
    $statusmessage = getStatusMessage($statuscode);
    $body = getResult($statuscode, $uri, $headers);
    outputHttpResponse($statuscode, $statusmessage, $headers, $body);

}

function getResult($code, $uri, $headers)
{
    switch ($code) {
        case "200":
            return file_get_contents(START_DIRECTORY . getHost($headers) . $uri);
        case "403":
            return "Access denied";
        case "404":
            return "Not Found";
        default:
            return "Unknown Status";
    }
}

function getstatusmessage($code)
{
    switch ($code) {
        case "200":
            return "OK";
        case "403":
            return "Access denied";
        case "404":
            return "Not Found";
        default:
            return "Unknown Status";
    }
}

function getStatusCode($headers, $uri)
{
    if (checkHost($headers)) {
        return "404";
    } elseif (checkDirectory($uri, $headers)) {
        return "403";
    } elseif (checkFile($uri, $headers)) {
        return "404";
    } else {
        return "200";
    }
}

function checkFile($uri, $headers)
{
    $host = getHost($headers);
    $path = START_DIRECTORY . $host . $uri;
    return !file_exists($path);
}

function checkDirectory($uri, $headers)
{
    $host = getHost($headers);
    $directory = dirname($uri);
    $path = START_DIRECTORY . $host . $directory;
    echo file_get_contents($path);
    return !is_dir($path);
}


function checkHost($headers)
{
    $host = getHost($headers);
    if (strpos($host, "student.shpp.me") == 0 || strpos($host, "another.shpp.me") == 0) {
        return false;
    }
    return true;
}

/**
 * @throws HostNotFoundException
 */
function getHost($headers)
{
    foreach ($headers as $subarray) {
        foreach ($subarray as $key => $value) {
            if ($key === "Host") {
                return $value;
            }
        }
    }
    throw new HostNotFoundException("The Host not found in the headers");
}

class HostNotFoundException extends Exception
{
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
    $emptyIndex = array_search("", $lines); // Знаходимо індекс порожнього рядка, розділяючого заголовки та тіло
    return trim(implode(array_slice($lines, $emptyIndex + 1))); // Повертаємо всі рядки після порожнього рядка як тіло запиту
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

$mystr = "GET /a/d HTTP/1.1
Host: student.shpp.me
Accept: image/gif, image/jpeg, */*
Accept-Language: en-us
Accept-Encoding: gzip, deflate
User-Agent: Mozilla/4.0";

$http = parseTcpStringAsHttpRequest($mystr);
processHttpRequest($http["method"], $http["uri"], $http["headers"], $http["body"]);