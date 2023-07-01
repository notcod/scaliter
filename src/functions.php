<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(~0);

setlocale(LC_ALL, 'en_US.UTF-8');

define('IP', ip());
define('BROWSER', getBrowserInfo('content'));

if (getCons('PRODUCTION')) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', getCons('COOKIE_LIFETIME'));
    ini_set('session.name', getCons('COOKIE_NAME')); #can't be same as domain
    ini_set('session.sid_length', getCons('COOKIE_LENGTH'));
    session_start();
}



##################################
function indicum($indicum = '', $length = 32)
{
    $indicum = preg_replace('/[^a-zA-Z0-9]+/', '', $indicum);
    if ($indicum != '' && strlen($indicum) >= $length) return substr($indicum, 0, $length);
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < $length; $i++)
        $indicum .= $characters[random_int(0, 61)];
    return $indicum;
}
function clean($i)
{
    return preg_replace('/[^a-zA-Z0-9_]+/', '', str_replace('-', '_', $i));
}
function ints($i)
{
    return (int)clean($i);
}
function external($url)
{
    $parse = parse_url($url);
    return !empty($parse['host']) && strcasecmp($parse['host'], isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
}
function isReadable($FILE)
{
    return create($FILE) && is_file($FILE) && is_readable($FILE) && filesize($FILE) != 0;
}
function cache($url)
{
    $FILE = getCons('SERVER')  .  '/public'  . $url;
    if (isReadable($FILE))
        return $url . '?' . md6(filemtime($FILE));
    return false;
}
function section($data)
{
    if (empty($data['view']) || empty($data['page'])) return;

    $FILE = getCons('SERVER') . "/views/$data[view]/$data[page].php";
    if (isReadable($FILE))
        require_once($FILE);
}
function create($FILE)
{
    if (file_exists($FILE)) return true;
    if (getCons('PRODUCTION')) return false;
    $path = explode('/', $FILE);
    array_pop($path);
    $path = implode('/', $path);
    if (!file_exists($path)) mkdir($path, 0777, true);
    $f = fopen($FILE, "a") or die("Unable to open file! -> " . $FILE);
    fclose($f);
    chmod($FILE, 0777);
    return file_exists($FILE);
}
function exist($url)
{
    $get = substr($url, 0, 4) != 'http' ? url() . $url : $url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $get);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result !== FALSE ? $url : false;
}
function server()
{
    return gethostbyname(gethostname());
}
function is_cli()
{
    return empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0 && defined('STDIN');
}
function HTTPStatus($num)
{
    $http = array(
        100 => 'HTTP/1.1 100 Continue',
        101 => 'HTTP/1.1 101 Switching Protocols',
        200 => 'HTTP/1.1 200 OK',
        201 => 'HTTP/1.1 201 Created',
        202 => 'HTTP/1.1 202 Accepted',
        203 => 'HTTP/1.1 203 Non-Authoritative Information',
        204 => 'HTTP/1.1 204 No Content',
        205 => 'HTTP/1.1 205 Reset Content',
        206 => 'HTTP/1.1 206 Partial Content',
        300 => 'HTTP/1.1 300 Multiple Choices',
        301 => 'HTTP/1.1 301 Moved Permanently',
        302 => 'HTTP/1.1 302 Found',
        303 => 'HTTP/1.1 303 See Other',
        304 => 'HTTP/1.1 304 Not Modified',
        305 => 'HTTP/1.1 305 Use Proxy',
        307 => 'HTTP/1.1 307 Temporary Redirect',
        400 => 'HTTP/1.1 400 Bad Request',
        401 => 'HTTP/1.1 401 Unauthorized',
        402 => 'HTTP/1.1 402 Payment Required',
        403 => 'HTTP/1.1 403 Forbidden',
        404 => 'HTTP/1.1 404 Not Found',
        405 => 'HTTP/1.1 405 Method Not Allowed',
        406 => 'HTTP/1.1 406 Not Acceptable',
        407 => 'HTTP/1.1 407 Proxy Authentication Required',
        408 => 'HTTP/1.1 408 Request Time-out',
        409 => 'HTTP/1.1 409 Conflict',
        410 => 'HTTP/1.1 410 Gone',
        411 => 'HTTP/1.1 411 Length Required',
        412 => 'HTTP/1.1 412 Precondition Failed',
        413 => 'HTTP/1.1 413 Request Entity Too Large',
        414 => 'HTTP/1.1 414 Request-URI Too Large',
        415 => 'HTTP/1.1 415 Unsupported Media Type',
        416 => 'HTTP/1.1 416 Requested Range Not Satisfiable',
        417 => 'HTTP/1.1 417 Expectation Failed',
        500 => 'HTTP/1.1 500 Internal Server Error',
        501 => 'HTTP/1.1 501 Not Implemented',
        502 => 'HTTP/1.1 502 Bad Gateway',
        503 => 'HTTP/1.1 503 Service Unavailable',
        504 => 'HTTP/1.1 504 Gateway Time-out',
        505 => 'HTTP/1.1 505 HTTP Version Not Supported',
    );

    header($http[$num]);

    return [
        'code' => $num,
        'error' => $http[$num],
    ];
}
function sanitize($buffer)
{
    if (isset($_GET['sanitize'])) return $buffer;
    $buffer = preg_replace(['/\>[^\S]+/s', '/[^\S]+\</s', '/(\s)+/s', '/<!--(.|\s)*?-->/'], ['>', '<', '\\1', ''], $buffer);
    return $buffer;
    // return str_replace(PHP_EOL, '', $buffer);
}
function md6($q)
{
    return strlen($q) > 0 ? substr(clean(base64_encode(md5($q))), 6, 6) : '';
}
function url()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . "://" . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 0) . "/";
}
function ip()
{
    if (is_cli()) return server();

    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];
    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }
    $ip = explode(' ', $ip);
    return $ip[0];
}
function max_len($string, $max = 30)
{
    $length = mb_strlen($string);
    if ($length > $max) return false;
    return $string;
}
function _errors($req)
{
    return [$req->_errors[0], $req->_fields[0]];
}
function req_errors($req)
{
    return $req['errors'][0];
}
function load_section($s, $init = null)
{
    include getCons('SERVER') . '/section/' . $s . '.php';
}

function getBrowserInfo($return)
{
    if (!isset($_SERVER['HTTP_USER_AGENT'])) return null;

    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version = "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'LINUX';
    } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'MAC';
    } elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'WINDOWS';
    }

    // Next get the name of the useragent yes seperately and for good reason
    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    } elseif (preg_match('/Firefox/i', $u_agent)) {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    } elseif (preg_match('/Chrome/i', $u_agent)) {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    } elseif (preg_match('/Safari/i', $u_agent)) {
        $bname = 'Apple Safari';
        $ub = "Safari";
    } elseif (preg_match('/Opera/i', $u_agent)) {
        $bname = 'Opera';
        $ub = "Opera";
    } elseif (preg_match('/Netscape/i', $u_agent)) {
        $bname = 'Netscape';
        $ub = "Netscape";
    }

    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
        ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
            $version = $matches['version'][0];
        } else {
            $version = $matches['version'][1];
        }
    } else {
        $version = $matches['version'][0];
    }

    // check if we have a number
    if ($version == null || $version == "") {
        $version = "?";
    }


    $LANG = [
        "aa" => "Afar",
        "ab" => "Abkhazian",
        "ae" => "Avestan",
        "af" => "Afrikaans",
        "ak" => "Akan",
        "am" => "Amharic",
        "an" => "Aragonese",
        "ar" => "Arabic",
        "as" => "Assamese",
        "av" => "Avaric",
        "ay" => "Aymara",
        "az" => "Azerbaijani",
        "ba" => "Bashkir",
        "be" => "Belarusian",
        "bg" => "Bulgarian",
        "bh" => "Bihari languages",
        "bi" => "Bislama",
        "bm" => "Bambara",
        "bn" => "Bengali",
        "bo" => "Tibetan",
        "br" => "Breton",
        "bs" => "Bosnian",
        "ca" => "Catalan; Valencian",
        "ce" => "Chechen",
        "ch" => "Chamorro",
        "co" => "Corsican",
        "cr" => "Cree",
        "cs" => "Czech",
        "cu" => "Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic",
        "cv" => "Chuvash",
        "cy" => "Welsh",
        "da" => "Danish",
        "de" => "German",
        "dv" => "Divehi; Dhivehi; Maldivian",
        "dz" => "Dzongkha",
        "ee" => "Ewe",
        "el" => "Greek, Modern (1453-)",
        "en" => "English",
        "eo" => "Esperanto",
        "es" => "Spanish; Castilian",
        "et" => "Estonian",
        "eu" => "Basque",
        "fa" => "Persian",
        "ff" => "Fulah",
        "fi" => "Finnish",
        "fj" => "Fijian",
        "fo" => "Faroese",
        "fr" => "French",
        "fy" => "Western Frisian",
        "ga" => "Irish",
        "gd" => "Gaelic; Scottish Gaelic",
        "gl" => "Galician",
        "gn" => "Guarani",
        "gu" => "Gujarati",
        "gv" => "Manx",
        "ha" => "Hausa",
        "he" => "Hebrew",
        "hi" => "Hindi",
        "ho" => "Hiri Motu",
        "hr" => "Croatian",
        "ht" => "Haitian; Haitian Creole",
        "hu" => "Hungarian",
        "hy" => "Armenian",
        "hz" => "Herero",
        "ia" => "Interlingua (International Auxiliary Language Association)",
        "id" => "Indonesian",
        "ie" => "Interlingue; Occidental",
        "ig" => "Igbo",
        "ii" => "Sichuan Yi; Nuosu",
        "ik" => "Inupiaq",
        "io" => "Ido",
        "is" => "Icelandic",
        "it" => "Italian",
        "iu" => "Inuktitut",
        "ja" => "Japanese",
        "jv" => "Javanese",
        "ka" => "Georgian",
        "kg" => "Kongo",
        "ki" => "Kikuyu; Gikuyu",
        "kj" => "Kuanyama; Kwanyama",
        "kk" => "Kazakh",
        "kl" => "Kalaallisut; Greenlandic",
        "km" => "Central Khmer",
        "kn" => "Kannada",
        "ko" => "Korean",
        "kr" => "Kanuri",
        "ks" => "Kashmiri",
        "ku" => "Kurdish",
        "kv" => "Komi",
        "kw" => "Cornish",
        "ky" => "Kirghiz; Kyrgyz",
        "la" => "Latin",
        "lb" => "Luxembourgish; Letzeburgesch",
        "lg" => "Ganda",
        "li" => "Limburgan; Limburger; Limburgish",
        "ln" => "Lingala",
        "lo" => "Lao",
        "lt" => "Lithuanian",
        "lu" => "Luba-Katanga",
        "lv" => "Latvian",
        "mg" => "Malagasy",
        "mh" => "Marshallese",
        "mi" => "Maori",
        "mk" => "Macedonian",
        "ml" => "Malayalam",
        "mn" => "Mongolian",
        "mr" => "Marathi",
        "ms" => "Malay",
        "mt" => "Maltese",
        "my" => "Burmese",
        "na" => "Nauru",
        "nb" => "Bokmål, Norwegian; Norwegian Bokmål",
        "nd" => "Ndebele, North; North Ndebele",
        "ne" => "Nepali",
        "ng" => "Ndonga",
        "nl" => "Dutch; Flemish",
        "nn" => "Norwegian Nynorsk; Nynorsk, Norwegian",
        "no" => "Norwegian",
        "nr" => "Ndebele, South; South Ndebele",
        "nv" => "Navajo; Navaho",
        "ny" => "Chichewa; Chewa; Nyanja",
        "oc" => "Occitan (post 1500)",
        "oj" => "Ojibwa",
        "om" => "Oromo",
        "or" => "Oriya",
        "os" => "Ossetian; Ossetic",
        "pa" => "Panjabi; Punjabi",
        "pi" => "Pali",
        "pl" => "Polish",
        "ps" => "Pushto; Pashto",
        "pt" => "Portuguese",
        "qu" => "Quechua",
        "rm" => "Romansh",
        "rn" => "Rundi",
        "ro" => "Romanian; Moldavian; Moldovan",
        "ru" => "Russian",
        "rw" => "Kinyarwanda",
        "sa" => "Sanskrit",
        "sc" => "Sardinian",
        "sd" => "Sindhi",
        "se" => "Northern Sami",
        "sg" => "Sango",
        "si" => "Sinhala; Sinhalese",
        "sk" => "Slovak",
        "sl" => "Slovenian",
        "sm" => "Samoan",
        "sn" => "Shona",
        "so" => "Somali",
        "sq" => "Albanian",
        "sr" => "Serbian",
        "ss" => "Swati",
        "st" => "Sotho, Southern",
        "su" => "Sundanese",
        "sv" => "Swedish",
        "sw" => "Swahili",
        "ta" => "Tamil",
        "te" => "Telugu",
        "tg" => "Tajik",
        "th" => "Thai",
        "ti" => "Tigrinya",
        "tk" => "Turkmen",
        "tl" => "Tagalog",
        "tn" => "Tswana",
        "to" => "Tonga (Tonga Islands)",
        "tr" => "Turkish",
        "ts" => "Tsonga",
        "tt" => "Tatar",
        "tw" => "Twi",
        "ty" => "Tahitian",
        "ug" => "Uighur; Uyghur",
        "uk" => "Ukrainian",
        "ur" => "Urdu",
        "uz" => "Uzbek",
        "ve" => "Venda",
        "vi" => "Vietnamese",
        "vo" => "Volapük",
        "wa" => "Walloon",
        "wo" => "Wolof",
        "xh" => "Xhosa",
        "yi" => "Yiddish",
        "yo" => "Yoruba",
        "za" => "Zhuang; Chuang",
        "zh" => "Chinese",
        "zu" => "Zulu"
    ];
    $languages = [];
    $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $language = explode(';', $accept_language);
    foreach ($language as $lan) {
        $lang = explode(",", $lan);
        $lang = end($lang);
        if (key_exists($lang, $LANG)) $languages[$lang] = $LANG[$lang];
    }

    $output = array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'languages'    => $languages,
        'content' => $platform . ' - ' . $bname . ' ' . $version,
        'content_full' => $platform . ' - ' . $bname . ' ' . $version . ' - Lang: ' . implode(',', array_keys($languages))
    );
    return $output[$return];
}





// function setCons($name, $value)
// {
//     if (defined($name))
//         die("Constant $name can't be defined!");

//     if ($value === 'false' || $value == 0) $value = false;
//     if ($value === 'true'  || $value == 1) $value = true;

//     define($name, $value);

//     return $value;
// }
// function getCons($name, $ignore_if_not_defined = true)
// {
//     $isDefined = defined($name);

//     if(!$ignore_if_not_defined && !$isDefined) die("Constant $name isn't defined!");

//     return $isDefined ? constant($name) : false;
// }

function setCons($name, $value)
{
    if (isset($_ENV[$name]))
        die("Constant $name can't be defined!");

    if ($value === 'false' || $value == 0) $value = false;
    if ($value === 'true'  || $value == 1) $value = true;

    $_ENV[$name] = $value;

    return $value;
}
function getCons($name, $ignore_if_not_defined = true)
{
    $isDefined = isset($_ENV[$name]);

    if(!$ignore_if_not_defined && !$isDefined) die("Constant $name isn't defined!");

    return $isDefined ? $_ENV[$name] : false;
}







function dd($dump)
{
    var_dump($dump);
    die;
}





















###########REVIEW CODE BELLOW






















function checkRemoteFile($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    curl_close($ch);
    if ($result !== FALSE) {
        return true;
    } else {
        return false;
    }
}
function downloadIMG($img, $target_dir, $img_name = NULL, $AllowedFiles = ['jpg', 'png', 'jpeg', 'gif'])
{
    $img_name = $img_name ?? md5(rand());
    if (checkRemoteFile($img)) {
        $ext = explode('.', $img);
        $ext = end($ext);
        if (in_array($ext, $AllowedFiles)) {
            if (getimagesize($img) != false) {
                $image = imgName($img_name, $ext, $target_dir);
                file_put_contents($target_dir . $image, fopen($img, 'r'));
                return $image;
            }
        }
    }
    return false;
}
function seo_desc($str)
{
    $str = strip_tags($str);
    $str = htmlspecialchars_decode($str);
    $str = strip_tags($str);
    $str = str_replace('\n', ' ', $str);
    $str = str_replace('\r', '', $str);
    return mb_substr($str, 0, 155);
}
function imgName($name, $ext, $path = false)
{
    $img = format_uri($name . ' ' . rand()) . '.' . $ext;
    if ($path == false)
        return $img;
    else {
        $i = 1;
        while (file_exists($path . $img))
            $img = format_uri($name . ' ' . rand() . $i++) . '.' . $ext;
        return $img;
    }
}
function format_uri($string, $separator = '-')
{
    $unwanted_array = array(
        'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
        'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
        'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'č' => 'c', 'ć' => 'c', 'đ' => 'd'
    );
    $string = strtr(strtolower($string), $unwanted_array);
    $accents_regex = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
    $special_cases = array('&' => 'and', "'" => '');
    $string = mb_strtolower(trim($string), 'UTF-8');
    $string = str_replace(array_keys($special_cases), array_values($special_cases), $string);
    $string = preg_replace($accents_regex, '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
    $string = preg_replace("/[^a-z0-9]/u", "$separator", $string);
    $string = preg_replace("/[$separator]+/u", "$separator", $string);
    return $string;
}
function full_url()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}
function length($str, $c)
{
    return strlen($str) >= $c ? $str : '';
}
function extension($d)
{
    $ex = explode(".", $d);
    return end($ex);
}
function isEmail($q)
{
    return filter_var($q, FILTER_VALIDATE_EMAIL) ? $q : false;
}
function strip($q)
{
    return htmlspecialchars($q);
}
function int($q)
{
    $q = preg_replace('/[^0-9.]/', '', $q);
    return (empty($q)) ? 0 : $q;
}
function digs($q)
{
    $q = preg_replace('/[^0-9]/', '', $q);
    return (empty($q)) ? 0 : $q;
}
function md50($q)
{
    return strlen($q) > 0 ? substr(strtonum(md5($q)), 6, 6) : '';
}
function md9($q)
{
    return strlen($q) > 0 ? substr(strtonum(md5($q)), 0, 9) : '';
}
function strtonum($data)
{
    $new_string = "";
    $alphabet =  range("a", "z");
    $string_arr = str_split(clean($data));
    foreach ($string_arr as $str) {
        $new_string .= is_numeric($str) ? $str : array_search($str, $alphabet);
    }
    return $new_string;
}
function get($data, $value, $type = 'string')
{
    if ($type == 'string') {
        return isset($data[$value]) ? strip($data[$value]) : '';
    } elseif ($type == 'int') {
        return isset($data[$value]) ? int($data[$value]) : '';
    }
}
function post($v)
{
    return isset($_POST[$v]) ? trim(strip($_POST[$v])) : '';
}
function data($data, $value)
{
    return isset($data["post_data"][$value]) ? strip($data["post_data"][$value]) : '';
}
function isCurrent($data, $value)
{
    $page = get($data, 'page');
    return $page == $value ? 'active' : '';
}
function format_date($date)
{
    return date_format(date_create($date), "H:i\h d.m.Y.");
}
function format_time($h, $m)
{
    $h = (int)($h);
    $m = (int)($m);
    $d = substr(($h < 10 ? "0" . $h : $h) . ($m < 10 ? "0" . $m : $m), 0, 4);
    return $d < 2400 ? $d : "0000";
}
function writeFile($p, $t, $c = "")
{
    $f = fopen(pathFile($p), $t) or die('Unable to open file1!');
    fwrite($f, $c);
    fclose($f);
}
function readFiles($p, $t = 'r')
{
    $f = fopen($p, $t) or die("Unable to open file!");
    $d = fread($f, filesize($p));
    fclose($f);
    return $d;
}
function dec2($n)
{
    return number_format($n, 2, '.', ',');
}
function dc2($n)
{
    return number_format($n, 2, '.', '');
}
function pathFile($p)
{
    if (defined("CREATE_FILE")) return false;
    $p = str_replace("\\", "/", $p);
    $d = explode('/', $p);
    unset($d[count($d) - 1]);
    createPath(implode('/', $d));
    $f = fopen($p, "a") or die("Unable to open file! -> " . $p);
    fclose($f);
    return $p;
}
function createPath($path)
{
    $path = str_replace("\\", "/", $path);
    if (is_dir($path)) return true;
    echo "\n";
    $prev_path = substr($path, 0, strrpos($path, '/', -2) + 1);
    $return = createPath($prev_path);
    return ($return && is_writable($prev_path)) ? mkdir($path) : false;
}
// function time_elapsed_string($datetime, $full = false)
// {
//     $now = new DateTime;
//     $ago = new DateTime($datetime);
//     $diff = $now->diff($ago);
//     $diff->w = floor($diff->d / 7);
//     $diff->d -= $diff->w * 7;
//     $string = array(
//         'y' => 'year',
//         'm' => 'month',
//         'w' => 'week',
//         'd' => 'day',
//         'h' => 'hour',
//         'i' => 'minute',
//         's' => 'second',
//     );
//     foreach ($string as $k => &$v)
//         if ($diff->$k)
//             $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
//         else
//             unset($string[$k]);
//     if (!$full) $string = array_slice($string, 0, 1);
//     return $string ? implode(', ', $string) . ' ago' : 'just now';
// }
function content($part, $data)
{
    return getFile('/public/views/' . $part, $data);
}
function section_html($data)
{
    if (empty($data['page'])) return;

    return content($data['view'] . '/' . $data['page'] . '.html', $data);
}
function url_strip($x)
{
    return substr($x, 0, 2) == "//" || substr($x, 0, 7) == "http://" || substr($x, 0, 8) == "https://";
}
function uncache($f)
{
    $FILE_NAMES = explode('/', $f);
    $FILE_name = array_pop($FILE_NAMES);
    $FILE_NAMES = implode('/', $FILE_NAMES);

    $FILE = getCons('SERVER')  .  '/public'  . $FILE_NAMES . "/" . md5($FILE_name . ".min.js") . '.Cycler.js';
    if (is_file($FILE) && is_readable($FILE) && filesize($FILE) != 0)
        return $FILE_NAMES . "/" . md5($FILE_name . ".min.js") . '.Cycler.js' . '?' . md6(filemtime($FILE));

    $FILE = getCons('SERVER')  .  '/public'  . $FILE_NAMES . "/" . md5($FILE_name . ".min.css") . '.Cycler.css';
    if (is_file($FILE) && is_readable($FILE) && filesize($FILE) != 0)
        return $FILE_NAMES . "/" . md5($FILE_name . ".min.css") . '.Cycler.css' . '?' . md6(filemtime($FILE));

    $FILE = getCons('SERVER')  .  '/public'  . $f;
    if (is_file($FILE) && is_readable($FILE) && filesize($FILE) != 0)
        return $f . '?' . md6(filemtime($FILE));

    return false;
}
function getFile($f, $data = [])
{
    // if (!file_exists(SERVER  .  '/public'  . $f)) return false;
    if (!file_exists(getCons('SERVER')  .  '/public'  . $f)) pathFile(getCons('SERVER')  .  '/public'  . $f);
    if ($f == ".php") return false;
    if ($f == ".js") return false;
    if ($f == ".css") return false;
    //if(strlen($f) < 5) return false;
    if (extension($f) == "php")
        require_once(getCons('SERVER')  .  '/public'  . $f);
    else
        return uncache($f);
}

function redirect($w)
{
    header('Location: ' . $w);
    die;
}
function random($l = 6)
{
    $rand = strtoupper(hash('sha256', time() . md5(microtime(true)) . rand()));
    return substr($rand, 0, $l);
}
function fatal_handler()
{
    $error = error_get_last();
    if ($error === NULL) return false;
    die(json_encode($error));
}
// register_shutdown_function("fatal_handler");
// ob_start("sanitize_output");


function sanitize_output($buffer)
{
    $search = array(
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
        '/(\s)+/s',         // shorten multiple whitespace sequences
        '/<!--(.|\s)*?-->/' // Remove HTML comments
    );
    $replace = array(
        '>',
        '<',
        '\\1',
        ''
    );
    return preg_replace($search, $replace, $buffer);
    // $buffer = preg_replace($search, $replace, $buffer);
    // $buffer = str_replace("> <", "><", $buffer);
    // $buffer = str_replace("  ", " ", $buffer);
    // $buffer = str_replace(".png", ".png?v", $buffer);
    // $buffer = str_replace(".jpg", ".jpg?v", $buffer);
    // $buffer = str_replace(".jpeg", ".jpeg?v", $buffer);
    // $buffer = str_replace(".svg", ".svg?v", $buffer);
    // $buffer = str_replace(PHP_EOL, "", $buffer);
    // return $buffer;
}
function Generate2FA($u, $new_code_seconds = 30, $i = 0)
{
    return substr(strtonum(md5($u . (((int)(time() / $new_code_seconds)) - $i))), 6, 6);
}
function Confirm2FA($u, $c, $valid_minutes = 15, $new_code_seconds = 30)
{
    $times = $valid_minutes / $new_code_seconds * 60;
    for ($i = 0; $i < $times; $i++) if ($c == Generate2FA($u, $new_code_seconds, $i)) return 1;
    return 0;
}


function shortMessage($msg, $characters)
{
    if (strlen($msg) < $characters) return $msg;
    return substr($msg, 0, $characters) . "...";
}



function GetCSRF()
{
    return GetCSRFS();

    if (isset($_COOKIE['CSRF']) && strlen($_COOKIE['CSRF']) == 64)
        $CSRF = strtolower($_COOKIE['CSRF']);
    else {
        $CSRF = hash("sha256", rand() . md5(rand()));
        setcookie("CSRF", $CSRF);
    }
    return $CSRF;
}
function GetCSRFS()
{
    if (isset($_SESSION['CSRF']) && strlen($_SESSION['CSRF']) == 64)
        return strtolower($_SESSION['CSRF']);
    else
        $_SESSION['CSRF'] = hash("sha256", rand() . md5(rand()));

    return $_SESSION['CSRF'];
}
// GetCSRFS();
