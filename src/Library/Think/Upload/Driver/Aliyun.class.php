<?php
namespace Think\Upload\Driver;

use OSS\Core\OssException;
use OSS\OssClient;

class aliyun
{
    private $config = [
        'accessKeyId'     => '', //OSS用户
        'accessKeySecret' => '', //OSS密码
        'endpoint'        => '', //OSS空间路径
        'bucket'          => '', //空间名称
    ];

    private $client;
    private $error;
    private $rootPath;

    /**
     * 构造函数，用于设置上传根路径
     *
     * @param array $config 配置
     */
    public function __construct($config)
    {
        /* 默认配置 */
        $this->config = array_merge($this->config, $config);

        $this->client = new OssClient($this->config['accessKeyId'], $this->config['accessKeySecret'], $this->config['endpoint'], $this->config['isCName']);
    }

    /**
     * 检测上传根目录(OSS上传时支持自动创建目录，直接返回)
     *
     * @param string $rootpath 根目录
     *
     * @return boolean true-检测通过，false-检测失败
     */
    public function checkRootPath($rootpath)
    {
        if (!$rootpath) {
            $this->error = '上传根目录不存在！ ' . $rootpath;

            return false;
        }
        /* 设置根目录 */
        $this->rootPath = trim($rootpath, '/') . '/';

        return true;
    }

    /**
     * 检测上传目录(OSS上传时支持自动创建目录，直接返回)
     *
     * @param  string $savepath 上传目录
     *
     * @return boolean          检测结果，true-通过，false-失败
     */
    public function checkSavePath($savepath)
    {
        return true;
    }

    /**
     * 创建文件夹 (OSS上传时支持自动创建目录，直接返回)
     *
     * @param  string $savepath 目录名称
     *
     * @return boolean          true-创建成功，false-创建失败
     */
    public function mkdir($savepath)
    {
        return true;
    }

    /**
     * 保存指定文件
     *
     * @param  array   $file    保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     *
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save(&$file, $replace = true)
    {
        $object = $this->rootPath . $file['savepath'] . $file['savename'];

        if (!$replace && $this->client->doesObjectExist($this->config['bucket'], $object)) {
            $this->error = '存在同名文件' . $file['savename'];

            return false;
        }

        try {
            $this->client->uploadFile($this->config['bucket'], $object, $file['tmp_name']);

            return $object;
        } catch (OssException $ossException) {
            $this->error = $ossException->getErrorMessage();

            return false;
        }
    }

    /**
     * 获取最后一次上传错误信息
     *
     * @return string 错误信息
     */
    public function getError()
    {
        return $this->error;
    }
}
