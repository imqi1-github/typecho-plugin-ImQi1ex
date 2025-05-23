<?php
/**
 * 为ImQi1添加音乐支持。
 */

use JetBrains\PhpStorm\NoReturn;
use Metowolf\Meting;
use Utils\Helper;

// 允许跨站
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
// 设置API路径
define('API_URI', api_uri());
// 设置中文歌词
const TLYRIC = true;
// 设置歌单文件缓存及时间
const CACHE = true;
const CACHE_TIME = 9999999;
// 设置短期缓存-需要安装apcu
const APCU_CACHE = false;
// 设置AUTH密钥-更改'meting-secret'
const AUTH = true;
const AUTH_SECRET = 'meting-imqi1';

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!str_starts_with($referer, Helper::options()->siteUrl)) {
    throw new \Typecho\Exception("本 Meting API 只支持 ImQi1 使用。", 403);
}

// 获取type，默认值为'playlist'
$type = $_GET['type'] ?? 'playlist';

// 获取id和server，如果$_GET中有值则使用$_GET中的，否则从配置中获取
$id = $_GET['id'] ?? null;
$server = $_GET['server'] ?? null;

$musicId = Helper::options()->musicId;

// 如果type为playlist且$_GET中没有id和server，使用配置中的值
if ($type === 'playlist' && (!$id || !$server)) {
  // 解析musicId，判断是否含有'||'
  if (str_contains($musicId, '||')) {
    list($id, $server) = explode('||', $musicId);
    $id = trim($id);
    $server = trim($server);
  } else {
    // 如果没有'||'，则id由musicId获取，server使用默认值
    $id = $musicId;
    $server = $musicId ?: 'netease';
  }
}

// 如果type不是playlist，id和server不从配置中获取，改为从$_GET中获取
if ($type !== 'playlist') {
  if (!$id || !$server) {
    // 如果$_GET中没有id和server，抛出异常
    echo '{"error":"无效id"}';
    exit;
  }
} else {
  // 如果是playlist且$_GET中没有id和server，默认从配置中获取并解析
  if (!$id || !$server) {
    if (str_contains($musicId, '||')) {
      list($id, $server) = explode('||', $musicId);
    } else {
      $id = $musicId;
      $server = 'netease';
    }
  }
}

// 如果$_GET中的id和server能够提供，则直接赋值
if (isset($_GET['id'])) {
  $id = $_GET['id'];
}
if (isset($_GET['server'])) {
  $server = $_GET['server'];
}

if (AUTH) {
  $auth = $_GET['auth'] ?? '';
  if (in_array($type, ['url', 'pic', 'lrc'])) {
    if ($auth == '' || $auth != auth($server . $type . $id)) {
      http_response_code(403);
      exit;
    }
  }
}

// 数据格式
if (in_array($type, ['song', 'playlist'])) {
  header('content-type: application/json; charset=utf-8;');
} else if (in_array($type, ['name', 'lrc', 'artist'])) {
  header('content-type: text/plain; charset=utf-8;');
}


// include __DIR__ . '/vendor/autoload.php';
// you can use 'Meting.php' instead of 'autoload.php'
include __DIR__ . '/Meting/Meting.php';

$api = new Meting($server);
$api->format();

// 设置cookie
// if ($server == 'tencent') {
//   $api->cookie('pgv_pvid=7807423827; fqm_pvqid=b1ee36b2-d16f-4f54-974b-cf5d468bed8f; fqm_sessionid=7bf4eda1-f5c7-4682-a97c-62a0026f88fd; pgv_info=ssid=s7375315374; ts_uid=5013941096; ts_last=y.qq.com/n/ryqq/playlist/9356434139; RK=HbtQS14P/l; ptcz=50abb02397e80c7bf547e150e491ceea4daa63ea1953dd8c064aee3c85d626ee; login_type=1; wxopenid=; wxrefresh_token=; tmeLoginType=2; uin=2318110891; psrf_qqaccess_token=6695CC99821B371FF5C2823D5F9CFEB8; psrf_qqrefresh_token=0BEFA52CAF0C9A71C453D59FEF5D9262; qqmusic_key=Q_H_L_63k3NR2eZz4-0lnANmZEJ5fNC4Y80iZaSltA2jMiV10u2L8RoF9qfnqO1MN5OZKp06v4LH81D7sRrc9ljE8Cl5qgiYVY; psrf_musickey_createtime=1737131414; euin=owo5Ne65oecqov**; music_ignore_pskey=202306271436Hn@vBj; psrf_qqunionid=35D8283A4AE6B9008801FCE476B8BA5E; psrf_access_token_expiresAt=1737736214; qm_keyst=Q_H_L_63k3NR2eZz4-0lnANmZEJ5fNC4Y80iZaSltA2jMiV10u2L8RoF9qfnqO1MN5OZKp06v4LH81D7sRrc9ljE8Cl5qgiYVY; psrf_qqopenid=F673B7C8279FC8B5C0CCA67ABCD5CC00; wxunionid=');
// }

if ($type == 'playlist') {
  if (CACHE) {
    $file_path = __DIR__ . '/cache/playlist/' . $server . '_' . $id . '.json';
    if (file_exists($file_path)) {
      if ($_SERVER['REQUEST_TIME'] - filectime($file_path) < CACHE_TIME) {
        echo file_get_contents($file_path);
        exit;
      }
    }
  }

  $data = $api->playlist($id);
  if ($data == '[]') {
    echo '{"error":"音乐列表未找到"}';
    exit;
  }
  $data = json_decode($data);
  $playlist = array();
  foreach ($data as $song) {
    $playlist[] = array(
      'name' => $song->name,
      'artist' => implode('/', $song->artist),
      'url' => API_URI . '?meting&server=' . $song->source . '&type=url&id=' . $song->url_id . (AUTH ? '&auth=' . auth($song->source . 'url' . $song->url_id) : ''),
      'pic' => API_URI . '?meting&server=' . $song->source . '&type=pic&id=' . $song->pic_id . (AUTH ? '&auth=' . auth($song->source . 'pic' . $song->pic_id) : ''),
      'lrc' => API_URI . '?meting&server=' . $song->source . '&type=lrc&id=' . $song->lyric_id . (AUTH ? '&auth=' . auth($song->source . 'lrc' . $song->lyric_id) : '')
    );
  }
  $playlist = json_encode($playlist);

  if (CACHE) {
    // ! mkdir /cache/playlist
    file_put_contents($file_path, $playlist);
  }

  echo $playlist;
} else {
  $need_song = !in_array($type, ['url', 'pic', 'lrc']);
  if ($need_song && !in_array($type, ['name', 'artist', 'song'])) {
    echo '{"error":"type 字段无效"}';
    exit;
  }

  if (APCU_CACHE) {
    $apcu_time = $type == 'url' ? 600 : 36000;
    $apcu_type_key = $server . $type . $id;
    if (apcu_exists($apcu_type_key)) {
      $data = apcu_fetch($apcu_type_key);
      return_data($type, $data);
    }
    if ($need_song) {
      $apcu_song_id_key = $server . 'song_id' . $id;
      if (apcu_exists($apcu_song_id_key)) {
        $song = apcu_fetch($apcu_song_id_key);
      }
    }
  }

  if (!$need_song) {
    $data = song2data($api, null, $type, $id);
  } else {
    if (!isset($song)) $song = $api->song($id);
    if ($song == '[]') {
      echo '{"error":"歌曲未找到"}';
      exit;
    }
    if (APCU_CACHE) {
      apcu_store($apcu_song_id_key, $song, $apcu_time);
    }
    $data = song2data($api, json_decode($song)[0], $type, $id);
  }

  if (APCU_CACHE) {
    apcu_store($apcu_type_key, $data, $apcu_time);
  }

  return_data($type, $data);
}

function api_uri(): string // static
{
//    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
  return "https://" . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
}

function auth($name): string
{
  return hash_hmac('sha1', $name, AUTH_SECRET);
}

function song2data($api, $song, $type, $id)
{
  $data = '';
  switch ($type) {
    case 'name':
      $data = $song->name;
      break;

    case 'artist':
      $data = implode('/', $song->artist);
      break;

    case 'url':
      $m_url = json_decode($api->url($id, 320))->url;
      if ($m_url == '') break;
      // url
      $m_url = str_replace('http:', 'https:', $m_url);


      $data = $m_url;
      break;

    case 'pic':
      $data = json_decode($api->pic($id, 90))->url;
      break;

    case 'lrc':
      $lrc_data = json_decode($api->lyric($id));
      if ($lrc_data->lyric == '') {
        $lrc = '纯音乐，请欣赏';
      } else if ($lrc_data->tlyric == '') {
        $lrc = $lrc_data->lyric;
      } else if (TLYRIC) { // lyric_cn
        $lrc_arr = explode("\n", $lrc_data->lyric);
        $lrc_cn_arr = explode("\n", $lrc_data->tlyric);
        $lrc_cn_map = array();
        foreach ($lrc_cn_arr as $i => $v) {
          if ($v == '') continue;
          $line = explode(']', $v, 2);
          // 格式化处理
          $line[1] = trim(preg_replace('/\s\s+/', ' ', $line[1]));
          $lrc_cn_map[$line[0]] = $line[1];
          unset($lrc_cn_arr[$i]);
        }
        foreach ($lrc_arr as $i => $v) {
          if ($v == '') continue;
          $key = explode(']', $v, 2)[0];
          if (!empty($lrc_cn_map[$key]) && $lrc_cn_map[$key] != '//') {
            $lrc_arr[$i] .= ' (' . $lrc_cn_map[$key] . ')';
            unset($lrc_cn_map[$key]);
          }
        }
        $lrc = implode("\n", $lrc_arr);
      } else {
        $lrc = $lrc_data->lyric;
      }
      $data = $lrc;
      break;

    case 'song':
      $data = json_encode(array(array(
        'name' => $song->name,
        'artist' => implode('/', $song->artist),
        'url' => API_URI . '?meting&server=' . $song->source . '&type=url&id=' . $song->url_id . (AUTH ? '&auth=' . auth($song->source . 'url' . $song->url_id) : ''),
        'pic' => API_URI . '?meting&server=' . $song->source . '&type=pic&id=' . $song->pic_id . (AUTH ? '&auth=' . auth($song->source . 'pic' . $song->pic_id) : ''),
        'lrc' => API_URI . '?meting&server=' . $song->source . '&type=lrc&id=' . $song->lyric_id . (AUTH ? '&auth=' . auth($song->source . 'lrc' . $song->lyric_id) : '')
      )));
      break;
  }
  if ($data == '') exit;
  return $data;
}

#[NoReturn] function return_data($type, $data): void
{
  if (in_array($type, ['url', 'pic'])) {
    header('Location: ' . $data);
  } else {
    echo $data;
  }
  exit;
}
