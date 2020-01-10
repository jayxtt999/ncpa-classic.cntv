<?php
/**
 * Created by PhpStorm.
 * User: xietaotao
 * Date: 2020/1/8
 * Time: 16:03
 */


class Tools
{

    public function sendRequest($url, $method = 'get', $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置请求超时时间 秒
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $result = curl_exec($ch);

        return $result;
    }


    public function execInBackground($cmd)
    {
        if (substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            exec($cmd . " > /dev/null &");
        }
    }

    public function downPid($pid, $url, $title)
    {
        if ($pid) {
            file_put_contents('success', "\n" . $url, FILE_APPEND);
            $m3u8_url = 'http://hls.cntv.baishancdnx.cn/asp/hls/2000/0303000a/3/default/' . $pid . '/2000.m3u8';
            $cmd      = 'start cmd.exe @cmd /k N_m3u8DL-CLI_v2.4.6.exe  "' . $m3u8_url . '" --saveName "' . $title . '"  --enableDelAfterDone';
            system($cmd);
        }
    }

}

$tools = new Tools();
if (file_exists('list.json')) {
    $response = file_get_contents('list.json');
} else {
    $url      = 'http://api.cntv.cn/apicommon/index?path=iphoneInterface/general/getCrossSearchAction.jsonp&page=1&pageSize=100&column=NCPA%2525E9%25259F%2525B3%2525E4%2525B9%252590%2525E5%25258E%252585&theme=&type=&year=&callback=';
    $response = $tools->sendRequest($url);
    $response = substr($response, 1);
    $response = substr($response, 0, -1);
    file_put_contents('list.json', $response);
}

$data = json_decode($response, true);
$list = $data['list'];


$success = file_get_contents('success');
$success = explode("\n", $success);

foreach ($list as $item) {

    $video_album_url = $item['video_album_url'];
    if (in_array($video_album_url, $success)) {
        continue;
    }
    $video_album_title = $item['video_album_title'];
    $video_album_title = mb_convert_encoding($video_album_title, 'GBK');
    $xml_url           = str_replace('.shtml', '.xml', $video_album_url);
    $xml_file          = file_get_contents($xml_url);
    if ($xml_file && (false === strpos($xml_file, 'error.html'))) {
        $xml_data = json_decode(json_encode(simplexml_load_string($xml_file, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $Feature  = $xml_data['Features']['Feature'];
        if (isset($Feature[0]['Pid'])) {
            $time = $Feature[0]['Time'];
            $time = explode(':', $time);
            if ((int)$time[0] >= 1) {
                $feature = $Feature[0];
                $tools->downPid($feature['Pid'],$video_album_url,$video_album_title);
            }else{

                foreach ($Feature as $item){
                    $title = mb_convert_encoding($item['Title'], 'GBK');
                    $tools->downPid($item['Pid'],$item['ShareURL'],$title);
                }

            }
        } elseif (isset($Feature['Pid'])) {
            $tools->downPid($Feature['Pid'],$video_album_url,$video_album_title);
        }


    } else {
        file_put_contents('success', "\n" . $video_album_url, FILE_APPEND);
    }
    //带宽磁盘消耗巨大 暂时只跑一个
    exit;

}




