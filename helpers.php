<?php
function collect(array $array = [])
{
    return new \Ripple\Collection($array);
}


/**
 * @param string $string String to encrypt
 * @param int $length Length of returned string
 *
 * @return string
 */
function takeRandom($string, $length = 10)
{
    $array = str_split(md5($string));
    $newString = collect($array)->take($length);
    $newString = implode('', $newString);
    return $newString;
}

/**
 * @param int $length Length of returned string
 * @param bool $alphabets Whether to return a string of only alphabets
 * @return string
 */
function genRandomStr($length, $alphabets = false)
{
    $chars = $alphabets ? 'abcdefghijklmnopqrstuvwxyz' : '0123456789abcdefghijklmnopqrstuvwxyz';
    return substr(str_shuffle($chars), 0, $length);
}

function isHttpSecure()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : strtolower(explode('/', $_SERVER['SERVER_PROTOCOL'])[0]);
    if (preg_match('/^https$/i', $protocol)) {
        return true;
    }
    return false;
}

function getFullUrl($queryStr = false)
{
    $request = $_SERVER;
    $host = (isset($request['HTTP_HOST'])) ? $request['HTTP_HOST'] : $request['SERVER_NAME'];
    $isSecure = (isset($request['HTTPS']) and $request['HTTPS'] == "on") ? true : false;
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $queryString = (isset($_SERVER['QUERY_STRING']) and $_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : null;
    $scheme = (isHttpSecure()) ? "https://" : "http://";
    $fullUrl = $scheme . $host . $uri;
    return $fullUrl = ($queryStr) ? $fullUrl . $queryString : $fullUrl;
}
