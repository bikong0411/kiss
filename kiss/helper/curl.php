<?php
/**
 * Created by PhpStorm.
 * User: sky
 * Date: 14-9-30
 * Time: 9:17
 */

namespace kiss\helper;

class Curl
{

    /**
     * curl handle instances
     * @var static array("$url" => object(curl instance))
     */
    private static $_curlHandles = array();

    /**
     * default conf
     * @var static array
     */
    private static $_defaultConf = array(
        "timeout" => 15,
        'persistent' => true,
        'rtnheader' => false, // 是否返回 header
    );

    /**
     * 单组/简单模式
     * @example
     * $curl = Curl::prepare("http://127.1:80"[, array('foo' => , )[, array('foo' => ,)]]]);
     *
     * @param string $url
     * @param array optional $params 提交字段参数
     * @param array optional $config 目前包括 timeout: 超时时间; persistent: 是否保持持久连接; rtnheader: 是否返回 response 头信息;
     *
     * @return object Http_Curl
     *
     * 多组并发/更丰富的配置模式
     * @example
     * $curl = Curl::prepare(array(
     *     array('dest' => 'http://..', 'params' => array('foo' => , ), 'conf' => array('foo' => )),
     *     array('dest' => ..),
     * ));
     *
     * @param array $queries 请求列表列表
     * 参数包括：
     * 目标地址：dest string eg. http://example.com/?foo=bar
     * 提交字段参数：params array (optional)
     * 配置参数：conf array (optional) 目前包括 timeout: 超时时间; persistent: 是否保持持久连接; rtnheader: 是否返回 response 头信息;
     *
     * @return object Http_Curl
     */
    public static function prepare()
    {
        // basic parameter chk
        if (($args_num = func_num_args()) < 1)
            throw new Exception("incorrect parameter(s)!");

        // instance
        static $_instance = null;
        (is_null($_instance)) && $_instance = new self();

        // multi q
        if ($args_num === 1 && is_array($p0 = func_get_arg(0)))
        {
            foreach ($p0 as &$o)
            {
                if (isset($o['conf']) && is_array($o['conf']))
                    $o['conf'] += self::$_defaultConf;
                else
                    $o['conf'] = self::$_defaultConf;

                if (!isset($o['params']) || !is_array($o['params']))
                    $o['params'] = array();
            }
            $_instance->_query = $p0;
            return $_instance;
        }

        // single q
        if (is_string(func_get_arg(0)))
        {
            $params = ($args_num > 1 && is_array($p1 = func_get_arg(1))) ? $p1 : array();
            $conf = ($args_num > 2 && is_array($p2 = func_get_arg(2))) ? $p2 + self::$_defaultConf : self::$_defaultConf;
            $_instance->_query = array(array('dest' => func_get_arg(0), 'params' => $params, 'conf' => $conf));
            return $_instance;
        }

        throw new Exception("incorrect parameter(s)!");
    }

    /**
     * POST
     * @return string $content # 单组模式
     * @return array array('index' => $content) # 多组模式
     */
    public function post()
    {
        if (count($this->_query) > 1)
            return $this->_mexec('POST', $this->_query);
        return $this->_exec('POST', $this->_query[0]);
    }

    /**
     * PUT
     * @return string $content # 单组模式
     * @return array array('index' => $content) # 多组模式
     */
    public function put()
    {
        if (count($this->_query) > 1)
            return $this->_mexec('PUT', $this->_query);
        return $this->_exec('PUT', $this->_query[0]);
    }

    /**
     * DELETE
     * @return string $content # 单组模式
     * @return array array('index' => $content) # 多组模式
     */
    public function delete()
    {
        if (count($this->_query) > 1)
            return $this->_mexec('DELETE', $this->_query);
        return $this->_exec('DELETE', $this->_query[0]);
    }

    /**
     * GET
     * @return string $content # 单组模式
     * @return array array('index' => $content) # 多组模式
     */
    public function get()
    {
        if (count($this->_query) > 1)
            return $this->_mexec('GET', $this->_query);
        return $this->_exec('GET', $this->_query[0]);
    }

    /**
     * exec
     * @param string $method GET|POST
     * @param array $query
     * @return $res
     */
    protected function _exec($method = 'GET', array $query = null)
    {
        $query['method'] = $method;

        $curl = $this->_getCurl($query['dest'], $method);
        $this->_curlSetOpt($curl, $query);

        $result = curl_exec($curl);

        if (isset($_GET['man'])) {
            $curl_info = curl_getinfo($curl);
            pre_printf($curl_info);
        }


//         	$total_time = $curl_info['total_time'];
//         	if ($total_time > 0.05) {
//         	    $file = date('Ymd',time());
//         	    $string = '';
//         	    $string .= "{@}".$curl_info['total_time'];
//         	    $string .= "{@}".$curl_info['connect_time'];
//         	    $string .= "{@}".$curl_info['http_code'];
//         	    $string .= "{@}".$curl_info['url'];
//         	    $log = new Clog();
//         	    $log->write_log($file,$string);
//        	}
        return $result;

    }

    /**
     * multi-exec
     * @param string $method GET|POST
     * @param array $query
     * @return array
     */
    protected function _mexec($method = 'GET', array $query = array())
    {
        $mhande = curl_multi_init();
        $mcurl = array();
        foreach ($query as $i => $one)
        {
            $mcurl[$i] = $this->_getCurl($one['dest'], $method);
            $one['method'] = $method;
            $this->_curlSetOpt($mcurl[$i], $one);
            curl_multi_add_handle($mhande, $mcurl[$i]);
        }

        $active = null;
        do
        {
            $mrc = curl_multi_exec($mhande, $running);
        } while (CURLM_CALL_MULTI_PERFORM == $mrc);

        while ($running && $mrc == CURLM_OK)
        {
            // wait for network
            if (curl_multi_select($mhande, 0.5) > -1)
            {
                // pull in new data;
                do
                {
                    $mrc = curl_multi_exec($mhande, $running);
                } while (CURLM_CALL_MULTI_PERFORM == $mrc);
            }
        }

        $out = array();
        foreach ($query as $i => $one)
        {
            $status = curl_getinfo($mcurl[$i], CURLINFO_HTTP_CODE);
            // curl_close($mcurl[$i]);

            switch ($status)
            {
                case 202:
                case 201:
                case 200:
                    $out[$i] = curl_multi_getcontent($mcurl[$i]);
                    break;

                default :
                    $out[$i] = "NULL";
            }
            curl_multi_remove_handle($mhande, $mcurl[$i]);
        }

        curl_multi_close($mhande);
        return $out;
    }

    /**
     * 配置参数
     * @param object $curl
     * @param array $opts
     * @return object $curl
     */
    protected function _curlSetOpt(&$curl, $opts)
    {
        if (isset($opts["conf"]["user"]) && isset($opts["conf"]["pass"]))
        {
            curl_setopt($curl, CURLOPT_USERPWD, $opts["conf"]["user"] . ":" . $opts["conf"]["pass"]);
        }
        if (isset($opts["conf"]["proxy"]))
        {
            curl_setopt($curl, CURLOPT_PROXY, "http://192.168.103.97:3128");
        }
        if (substr($opts['dest'], 0, 5) === 'https')
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            // this line makes it work under https
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        }

        if ($opts["method"] === 'GET')
        {
            $dest_url = $opts['dest'];
            if (strpos($opts['dest'], "?"))
                $dest_url = empty($opts['params']) ? $opts['dest'] : $opts['dest'] . '&' . http_build_query($opts['params']);

            if (isset($_GET['man'])) echo http_build_query($opts['params']);
            curl_setopt($curl, CURLOPT_URL, $dest_url);
            // clean!!
            curl_setopt($curl, CURLOPT_POST, false);
        }
        elseif ($opts["method"] === 'POST')
        {
            curl_setopt($curl, CURLOPT_URL, $opts['dest']);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($opts['params']));
        }
        elseif (in_array($opts['method'], array('PUT', 'DELETE')))
        {
            curl_setopt($curl, CURLOPT_URL, $opts['dest']);
            $fields = http_build_query($opts['params']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($fields)));
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $opts['method']);
        }

        curl_setopt($curl, CURLOPT_TIMEOUT, $opts["conf"]["timeout"]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);

        // boolean!!
        if (isset($opts["conf"]["rtnheader"]))
            curl_setopt($curl, CURLOPT_HEADER, $opts["conf"]["rtnheader"]);

        // keepalive set
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Connection: ' . $opts["conf"]["persistent"] ? "keep-alive" : "close",
        ));
    }

    /**
     * 获取并返回 url 实例
     * @param string $url
     * @return object
     */
    protected function _getCurl($url, $method = "GET")
    {
        $o = parse_url($url);
        $k = join(array($o['scheme'], $o['host'], isset($o['port']) ? $o['port'] : 80, strtolower($method), $o['path']), '.');
        if (!isset(self::$_curlHandles[$k]) || get_resource_type(self::$_curlHandles[$k]) !== 'curl')
            self::$_curlHandles[$k] = curl_init();
        return self::$_curlHandles[$k];
    }

    /**
     * 构造函数
     * @access protected
     */
    protected function __construct()
    {
        if (!extension_loaded("curl"))
            throw new Exception("curl extension required!");
    }

}