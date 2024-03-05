<?php

const MY_URI = "/api/checkLoginAndPassword";
const MY_CONT_TYPE = "application/x-www-form-urlencoded";
const FILE = "passwords";
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
    $statuscode = getStatusCode($headers, $uri, $body);
    $statusmessage = getStatusMessage($statuscode);
    $body = getResult($statuscode);
    outputHttpResponse($statuscode, $statusmessage, $headers, $body);
}

function getResult($statuscode)
{
    switch ($statuscode) {
        case "200":
            return '<h1 style="color:green">FOUND</h1>';
        case "404":
            return '<h1 style="color:red">NOT FOUND</h1>';
        case "401":
            return '<h1 style="color:red">WRONG PASSWORD</h1>';
        default:
            return "Unknown Status";
    }

}

function getStatusMessage($statuscode)
{
    switch ($statuscode) {
        case "200":
            return "OK";
        case "400":
            return "Bad Request";
        case "401":
            return "Unauthorized";
        case "404":
            return "Not Found";
        case "500":
            return "Internal Server Error";
        default:
            return "Unknown Status";
    }
}

function getStatusCode($headers, $uri, $body)
{
    if (checkUriEndContentType($uri, $headers)) {//якщо неправильний урі або контент тайп
        return "400";
    } elseif (!checkPassTxt()) {
        return "500";
    } elseif (checkLogin($body)) {
        return "404";
    } elseif (checkPassword($body)) {//якщо неправильний  пароль
        return "401";
    } else {
        return "200";
    }


}

function checkPassword($body)
{
    $password = getPassword($body);
    $passwordString = strval($password);
    $log = getLogin($body);
    $date = explode("\n", file_get_contents(FILE));
    foreach ($date as $line) {
        if (strpos($line, $log) !== false) {
            $parts = explode(":", $line);
            $truePass = end($parts);
            return $truePass !== $passwordString; // Порівнюємо рядки
        }
    }
    return true;
}


function getPassword($body)
{
    $logPass = explode("&", $body);
    $almostPassword = explode("password=", end($logPass));
    return end($almostPassword);
}

function checkPassTxt()
{
    return file_exists(FILE);
}

function checkLogin($body)
{
    $file = file_get_contents(FILE);
    $login = getLogin($body);
    $lines = explode("\n", $file);
    foreach ($lines as $line) {
        $parts = explode(":", $line);
        if ($parts[0] === $login) {
            return false; // Логін знайдено у файлі
        }
    }
    return true; // Логін не знайдено у файлі
}

function getLogin($body)
{
    $logPass = explode("&", $body);
    $strlogin = start($logPass);
    $login = explode("=", $strlogin);
    return end($login);
}

function checkUriEndContentType($uri, $headers)
{
    $contType = getContType($headers);
    return $uri !== MY_URI || $contType !== MY_CONT_TYPE;
}

class ContentTypeNotFoundException extends Exception
{
    public function errorMessage()
    {
        return "The Content-Type was not found in the headers.";
    }
}

function getContType($headers)
{
    foreach ($headers as $subarray) {
        foreach ($subarray as $key => $value) {
            if ($key === "Content-Type") {
                return $value;
            }
        }
    }
    throw new ContentTypeNotFoundException();
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

$mystr = "POST /api/checkLoginAndPassword HTTP/1.1
Accept: */*
Content-Type: application/x-www-form-urlencoded
User-Agent: Mozilla/4.0
Content-Length: 35

login=login1&password=1
";

$http = parseTcpStringAsHttpRequest($mystr);
processHttpRequest($http["method"], $http["uri"], $http["headers"], $http["body"]);