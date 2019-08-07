<?php

    function Qiniu_Encode($str)
    {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($str));
    }

    function isList($p_array)
    {
        return is_array($p_array) && (array_keys($p_array) !== array_keys(array_keys($p_array)));
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

    /**
     * 创建一个随机字符
     * @param integer 字符长度
     * @param string 字符集
     * @return string 加密字符串
     */
    function buildRandCharacters($p_length=6, $chars='23456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ')
    {
        $verifyCode = '';
        for ($i = 0; $i < $p_length; $i++) {
            $verifyCode .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $verifyCode;
    }

    function captchaImageUrl($verifyCode, $color='#B95B09', $image_width=100, $image_height=40)
    {
        $verifyCodes = str_split($verifyCode);
        $count = count($verifyCodes);
        // 背景图
        $bg = 'https://ojpbly1un.qnssl.com/gogopher.jpg?imageView2/1/w/'.$image_width.'/h/'.$image_height.'';
        //
        $fontsize = intval(min(($image_width/$count), $image_height) * 1440 / 72);
        //
        $params = [];
        foreach ($verifyCodes as $index => $code) {
            $param = [];
            $param['text'] = $code;
            $param['dx'] = intval(($image_width/$count) * $index + ($image_width/$count/2) - ($image_width/2));
            $param['dy'] = 0;
            $param['fontsize'] = $fontsize;
            $param['font'] ='微软雅黑';
            $param['fill'] = $color;
            $param['gravity'] = 'Center';
            // 位置随机位移
            $param['dx'] += intval((-($image_width/$count/2) + rand(0, (($image_width/$count))))/2);
            $param['dy'] += intval((-($image_height/2) + rand(0, (($image_height))))/2);
            // 添加重影
            $params[] = array_merge($param, ['dx'=>$param['dx']+1,'dy'=>$param['dy']+1,'fill'=>'#ffffff']);
            $params[] = $param;
        }
        return watermarkUrl($bg, $params);
    }

    function captchaImageData($verifyCode, $color='#B95B09', $image_width=200, $image_height=80)
    {
        $url = captchaImageUrl($verifyCode, $color, $image_width, $image_height, $random_dots, $random_lines);
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

    
