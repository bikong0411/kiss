<?php
/**
 * Created by PhpStorm.
 * User: sky
 * Date: 14-10-4
 * Time: 10:39
 */

namespace kiss\helper;


class Log
{

    // 日志文件
    protected $_f = '/dev/shm/log.pipe';
    // 日志前缀(分组)
    protected $_log_prefix = 'kiss.BL'; # Business Logic
    // 日期格式
    protected $_date_format = 'Y-m-d H:i:s';
    // 用以保存对象周期内的日志
    protected $_log = array();
    // 已缓存长度
    protected $_cached_size = 0;
    // 缓存块大小
    protected $_cache_chunk_size = 32000;

    //
    // 日志级别定义

    const FATAL = 'fatal';
    const ERR = 'err';
    const WARN = 'warn';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * 致命信息
     * @param string $msg
     * @return
     */
    public static function fatal($msg)
    {
        return self::_hanlde($msg, self::FATAL);
    }

    /**
     * 常规错误信息
     * @param string $msg
     * @return
     */
    public static function err($msg)
    {
        return self::_hanlde($msg, self::ERR);
    }

    /**
     * 严重警告信息
     * @param string $msg
     * @return
     */
    public static function warn($msg)
    {
        return self::_hanlde($msg, self::WARN);
    }

    /**
     * 警告信息
     * @param string $msg
     * @return
     */
    public static function notice($msg)
    {
        return self::_hanlde($msg, self::NOTICE);
    }

    /**
     * 常规信息
     * @param string $msg
     * @return
     */
    public static function info($msg)
    {
        return self::_hanlde($msg, self::INFO);
    }

    /**
     * 常规调试信息
     * @param string $msg
     * @return
     */
    public static function debug($msg)
    {
        return self::_hanlde($msg, self::DEBUG);
    }

    /**
     * 创建实例(optional)
     * Log::instance('foo')->debug('bar');
     *
     * @staticvar self $instance
     * @param string $prefix
     * @return string
     */
    public static function instance($prefix = null)
    {
        static $instance;
        if (is_null($instance)) {
            $instance = new self();
        }
        if ($prefix) {
            $instance->_log_prefix = $prefix;
        }
        return $instance;
    }

    public function setLogPath($log_path) {
        if(!is_dir($log_path)) {
           @mkdir($log_path, 0777, true);
        }
        $this->_f = $log_path;
    }

    /**
     * 将已缓存日志输出并清空现有缓存
     * 默认为写入文件
     * @return
     */
    public function flush()
    {
        if (!empty($this->_log)) {
            // 默认输出写入日志
            $string = '';
            foreach ($this->_log as $item) {
                list($time, $type, $msg) = $item;
                $string .= "{$this->_log_prefix} " . date($this->_date_format, $time) . " {$type}: {$msg}\n";
            }
            if ($string) {
                $filename = $this->_f.".". date("Y_m_d");
                $fp = @fopen($filename, 'a') or die("Log file:'$filename' open failed!");
                if ($fp && flock($fp, LOCK_EX)) {
                    fwrite($fp, $string);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                }
            }

            // 重置现有缓存
            $this->_log = array();
            $this->_cached_size = 0;
        }
    }

    /**
     * 日志实际处理逻辑
     * @staticvar <object> $instance
     * @param string $msg
     * @param string $level
     */
    private static function _hanlde($msg, $level)
    {
        static $instance;
        if (is_null($instance)) {
            $instance = new self();
        }

        $instance->_log[] = array(date("Y-m-d H:i:s"), $level, $msg);
        $instance->_cached_size += strlen($msg);

        if ($instance->_cached_size >= $instance->_cache_chunk_size) {
            $instance->flush();
        }
    }

    // 析构函数
    public function __destruct()
    {
        $this->flush();
    }

}
