# 公司内部使用的PHP框架
1. 修改了debug方式
2. 修改了log记录方式
3. 添加了CLI模式 console.php
4. 修正model的notic错误
5. 修正的了cli模式下的notic错误
6. 对致命错误也增加了日志记录方式
7. 增加了monolog日志驱动,并更改为默认日志驱动
8. 增加了阿里云传驱动


使用:
`composer require framework/thinkphp`



### 配置文件详解

##### monolog:

```php
/* 日志设置 */
'LOG_RECORD'            =>  false,   // 默认不记录日志
'LOG_TYPE'              =>  'ThinkMonoLog', // 日志记录类型 默认为文件(File)方式
'LOG_LEVEL'             =>  'EMERG,ALERT,CRIT,ERR',// 允许记录的日志级别
'LOG_FILE_SIZE'         =>  2097152,	// 日志文件大小限制
'LOG_EXCEPTION_RECORD'  =>  false,    // 是否记录异常信息日志

/* monolog的日志设置 */
'MONOLOG_RSYSLOG_FLAG'  => false, // 是否开启rsyslog日志远程传输
'MONOLOG_LOCAL_FLAG'    => true,  // 是否将日志记录到本地文件
```



##### 阿里云上传驱动:

```php
# 上传驱动更改为阿里云及其配置
'FILE_UPLOAD_TYPE'     => 'Aliyun', // 更改上传驱动为阿里云
'UPLOAD_TYPE_CONFIG'   => [ 
  'accessKeyId'     => '', // 阿里云权限用户的accessKeyId
  'accessKeySecret' => '', // 阿里云权限用户的accessKeySecret
  'endpoint'        => '', // OSS的endpoint
  'bucket'          => '', // OSS的bucket
  'isCName'         => true, // OSS是否绑定了域名
],
```



