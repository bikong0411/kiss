<?php
/**
 * Created by PhpStorm.
 * User: sky
 * Date: 14-9-23
 * Time: 22:51
 * autoload
 */
namespace kiss\helper;

use kiss\helper\Curl;

class Util {
    private static $_sms_user = 'tsilin';
    private static $_sms_passwd = 'tsilin001';
    private static  $_sms_url = 'http://www.smsbao.com/sms';

    /*
     * send sms
     * @param $phone
     * @param $content
     * @return boolean
     */
    public static function sendSMS($phone, $content) {
        $params = array('u' => self::$_sms_user, 'p' => md5(self::$_sms_passwd), 'm' => $phone, 'c' => urlencode($content));
        $res = Curl::prepare(self::$_sms_url,$params,array('timeout' => 5))->get();
        return $res === "0";
    }
}