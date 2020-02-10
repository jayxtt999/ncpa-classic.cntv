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
            $m3u8_url = 'http://hls.cntv.baishancdnx.cn/asp/hls/2000/0303000a/3/default/' . $pid . '/2000.m3u8';//超清
			//$m3u8_url = 'http://hls.cntv.baishancdnx.cn/asp/hls/main/0303000a/3/default/' . $pid . '/main.m3u8?maxbr=2048';//高清
			
            $cmd      = 'start cmd.exe @cmd /k N_m3u8DL-CLI_v2.4.6.exe  "' . $m3u8_url . '" --saveName "' . $title . '"  --enableDelAfterDone';
            system($cmd);
        }
    }

}



$tools = new Tools();

$xml_url  = 'http://ncpa-classic.cntv.cn/2018/02/13/VIDExUHh2Sd3FWYhr9kmVd0L180213.xml';
$xml_file = file_get_contents($xml_url);
if ($xml_file && (false === strpos($xml_file, 'error.html'))) {
    $xml_data          = json_decode(json_encode(simplexml_load_string($xml_file, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    $video_album_title = $xml_data['header'];
	$video_album_title = mb_convert_encoding($video_album_title, 'GBK');
    $centerId          = isset($xml_data['centerId']) ? $xml_data['centerId'] : false;
    if ($centerId) {
        $tools->downPid($centerId, $video_album_url, $video_album_title);
    } else {
        $Feature = isset($xml_data['Features']['Feature']) ? $xml_data['Features']['Feature'] : false;
        if ($Feature) {

            if (isset($Feature[0]['Pid'])) {
                $time = $Feature[0]['Time'];
                $time = explode(':', $time);
                if ((int)$time[0] >= 1) {
                    $feature = $Feature[0];
                    $tools->downPid($feature['Pid'], $video_album_url, $video_album_title);
                } else {

                    foreach ($Feature as $item) {
                        $title = mb_convert_encoding($item['Title'], 'GBK');
                        $tools->downPid($item['Pid'], $item['ShareURL'], $title);
                    }

                }
            } elseif (isset($Feature['Pid'])) {
                $tools->downPid($Feature['Pid'], $video_album_url, $video_album_title);
            }

        }
    }


} 



