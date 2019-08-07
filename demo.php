<?php

    function Qiniu_Encode($str)
    {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($str));
    }

    function isList($pArray)
    {
        return is_array($pArray) && (array_keys($pArray) !== array_keys(array_keys($pArray)));
    }

    function watermarkUrl($imageUrl, $params)
    {
        $suffix = '';
        $watermarkType = 3;
        if (isList($params)) {
            $params = array($params);
        }
        if (count($params)>1) {
            $watermarkType = 3;
        } elseif (count($params)==0) {
            return 'unknown watermarkType.';
        } elseif (array_key_exists('image', $params[0])) {
            $watermarkType = 1;
        } elseif (array_key_exists('text', $params[0])) {
            $watermarkType = 2;
        } else {
            return 'unknown watermarkType.';
        }

        foreach ($params as $param) {
            foreach ($param as $key => $value) {
                switch ($key) {
                    case 'image':
                    case 'text':
                    case 'font':
                    case 'fill':
                        $suffix .= '/'.$key.'/'.Qiniu_Encode($value);
                        break;

                    default:
                        $suffix .= '/'.$key.'/'.$value;
                        break;
                }
            }
        }
        return $imageUrl.(strpos($imageUrl, '?')?'|':'?').'watermark/'.$watermarkType.$suffix;
    }

    function buildRandCharacters($pLength=6, $chars='23456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ')
    {
        $verifyCode = '';
        for ($i = 0; $i < $pLength; $i++) {
            $verifyCode .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $verifyCode;
    }

    function captchaImageUrl($verifyCode, $color='#B95B09', $imageWidth=100, $image_height=40)
    {
        $verifyCodes = str_split($verifyCode);
        $count = count($verifyCodes);
        // 背景图
        $bg = 'https://ojpbly1un.qnssl.com/gogopher.jpg?imageView2/1/w/'.$imageWidth.'/h/'.$image_height.'';
        // 字体大小计算
        $fontsize = intval(min(($imageWidth/$count), $image_height) * 1440 / 72);
        
        $params = [];
        foreach ($verifyCodes as $index => $code) {
            // 整理水印参数
            $param = [];
            $param['text'] = $code;
            $param['dx'] = intval(($imageWidth/$count) * $index + ($imageWidth/$count/2) - ($imageWidth/2));
            $param['dy'] = 0;
            $param['fontsize'] = $fontsize;
            $param['font'] ='微软雅黑';
            $param['fill'] = $color;
            $param['gravity'] = 'Center';
            // 位置随机位移
            $param['dx'] += intval((-($imageWidth/$count/2) + rand(0, (($imageWidth/$count))))/2);
            $param['dy'] += intval((-($image_height/2) + rand(0, (($image_height))))/2);
            // 添加重影
            $params[] = array_merge($param, ['dx'=>$param['dx']+1,'dy'=>$param['dy']+1,'fill'=>'#ffffff']);

            $params[] = $param;
        }
        return watermarkUrl($bg, $params);
    }

    function captchaImageData($verifyCode, $color='#B95B09', $imageWidth=200, $image_height=80)
    {
        $url = captchaImageUrl($verifyCode, $color, $imageWidth, $image_height, $random_dots, $random_lines);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $content = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
            exit;
        }
        curl_close($ch);
        return 'data:image/jpeg;base64,'.base64_encode($content);
    }

?>
<img src="<?= captchaImageData(buildRandCharacters(4)) ?>"/>

    
