<?php
namespace Think\Log\Driver;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\LogstashFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Think\Log;

/**
 * monoLog日志处理类
 */
class ThinkMonoLog
{
    //日志类实例
    protected static $log = null;

    //日志处理方式实例
    protected static $handle = [];

    /**
     * 初始化
     *
     * MonoLog constructor.
     */
    public function __construct()
    {
        // 单一实例
        if (!self::$log) {
            // logger实例名
            $monoLogName = C('MONOLOG_NAME') ? C('MONOLOG_NAME') : gethostname();
            self::$log   = new Logger($monoLogName);
            // 是否在远程服务器保存日志
            if (true === C('MONOLOG_RSYSLOG_FLAG')) {
                $rsysLogTagName       = C('RSYSLOG_TAGNAME') ? C('RSYSLOG_TAGNAME') : $monoLogName;
                $logLevel             = C('RSYSLOG_LEVEL') ? C('RSYSLOG_LEVEL') : LOG_LOCAL5;
                self::$handle['rsys'] = new SyslogHandler($rsysLogTagName, $logLevel);
                self::$handle['rsys']->setFormatter(new LogstashFormatter($rsysLogTagName));
                self::$log->pushHandler(self::$handle['rsys']);
            }
        }
    }

    /**
     * 日志写入接口
     *
     * @access public
     *
     * @param string $log         日志信息
     * @param string $destination 写入目标
     *
     * @return void
     */
    public function write($log, $destination = '')
    {
        // 本地保存
        if (true === C('MONOLOG_LOCAL_FLAG') && $destination && !array_key_exists($destination, self::$handle)) {
            self::$handle[$destination] = new StreamHandler($destination, Logger::DEBUG);
            if (false !== APP_DEBUG) {
                self::$handle[$destination]->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n\n"));
                self::$handle[$destination]->getFormatter()->allowInlineLineBreaks(true);
            }
            self::$handle[$destination]->getFormatter()->ignoreEmptyContextAndExtra(true);

            self::$log->pushHandler(self::$handle[$destination]);
        }

        if (self::$log->getHandlers()) {
            $context = [];
            array_key_exists('REMOTE_ADDR', $_SERVER) && $context['ip'] = $_SERVER['REMOTE_ADDR'];
            array_key_exists('REQUEST_URI', $_SERVER) && $context['uri'] = $_SERVER['REQUEST_URI'];
            if (false !== APP_DEBUG && false !== strpos($log, 'INFO: [ app_begin ] --START--')) {
                self::$log->addRecord(Logger::EMERGENCY, "\r\n" . $log, $context);

                return;
            }

            $log = trim($log);
            $log = explode("\r\n", $log);
            foreach ($log AS $key => $item) {
                $level         = strstr($item, ':', true);
                $msg           = ltrim(strstr($item, ':'), ':');
                $context['no'] = $key + 1;
                switch ($level) {
                    case Log::ERR:
                        $level = Logger::ERROR;
                        break;
                    case Log::EMERG:
                        $level = Logger::EMERGENCY;
                        break;
                    case Log::INFO:
                        $level = Logger::INFO;
                        break;
                    case Log::WARN:
                        $level = Logger::WARNING;
                        break;
                    case Log::NOTICE:
                        $level = Logger::NOTICE;
                        break;
                    case Log::ALERT:
                        $level = Logger::ALERT;
                        break;
                    case Log::CRIT:
                        $level = Logger::CRITICAL;
                        break;
                    default:
                        $level = Logger::DEBUG;
                }

                self::$log->addRecord($level, $msg, $context);
            }
        }
    }
}