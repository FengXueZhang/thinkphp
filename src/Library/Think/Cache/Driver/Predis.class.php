<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Cache\Driver;

use Predis\Client;
use Think\Cache;

defined('THINK_PATH') or exit();

/**
 * Predis缓存驱动
 * 依赖于 : https://github.com/nrk/phpiredis
 */
class Predis extends Cache
{
    /**
     * 架构函数
     *
     * @param array $options 缓存参数
     *
     * @access public
     */
    public function __construct($options = [])
    {
        $options = array_merge([
            'host'       => C('REDIS_HOST') ?: '127.0.0.1',
            'port'       => C('REDIS_PORT') ?: 6379,
            'timeout'    => C('DATA_CACHE_TIMEOUT') ?: false,
            'persistent' => false,
            'auth'       => C('REDIS_AUTH') ? C('REDIS_AUTH') : false,
            'db'         => C('REDIS_DB') ? C('REDIS_DB') : false,
            'scheme'     => C('REDIS_SCHEME') ? C('REDIS_SCHEME') : 'tcp',
        ], $options);

        $this->options           = $options;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $func                    = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler           = new Client($options, $options);
        $options['timeout'] === false ?
            $this->handler->$func($options['host'], $options['port']) :
            $this->handler->$func($options['host'], $options['port'], $options['timeout']);
        $options['auth'] && $this->handler->auth($options['auth']);
        $options['db'] && $this->handler->select($options['db']);
    }

    /**
     * 读取缓存
     *
     * @access public
     *
     * @param string $name 缓存变量名
     *
     * @return mixed
     */
    public function get($name)
    {
        N('cache_read', 1);
        $value    = $this->handler->get($this->options['prefix'] . $name);
        $jsonData = json_decode($value, true);

        return ($jsonData === null) ? $value : $jsonData; //检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
    }

    /**
     * 写入缓存
     *
     * @access public
     *
     * @param string  $name   缓存变量名
     * @param mixed   $value  存储数据
     * @param integer $expire 有效时间（秒）
     *
     * @return boolean
     */
    public function set($name, $value, $expire = null)
    {
        N('cache_write', 1);
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $name = $this->options['prefix'] . $name;
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        if (is_int($expire) && $expire) {
            $result = $this->handler->setex($name, $expire, $value);
        } else {
            $result = $this->handler->set($name, $value);
        }
        if ($result && $this->options['length'] > 0) {
            // 记录缓存队列
            $this->queue($name);
        }

        return $result;
    }

    /**
     * 删除缓存
     *
     * @access public
     *
     * @param string $name 缓存变量名
     *
     * @return boolean
     */
    public function rm($name)
    {
        return $this->handler->del($this->options['prefix'] . $name);
    }

    /**
     * 清除缓存
     *
     * @access public
     * @return boolean
     */
    public function clear()
    {
        return $this->handler->flushdb();
    }

}
