# ububs
php7 + swoole常驻内存框架
## 前言 ##
- 本项目属业余时间开发，纯属为了交流与学习，代码质量与性能等优化持续进行，如果这个项目对你有帮助，请给个star，谢谢！（5/14/2018 08:21:33 AM ）
- QQ：**292304400**，微信：**Ruizhenger**，邮箱：**linlm1994@gmail.com**，欢迎交流
- 持续更新ing

## 项目简介 ##
> 本项目使用的是swoole2.0 + php7.0 开发的常驻内存的mvc框架，主要目的是使swoole更加简单和易用
> 本项目支持composer安装

## 项目计划和已完成部分 ##
- **[完成]** composer 安装
- **[完成]** MVC部分
- **[完成]** 路由部分，路由唯一控制器唯一地址，支持get post put delete，header等方法，支持命名空间、前缀、中间件过滤，路由缓存
- **[完成]** DB类封装，增删改查，pdo连接方式，尚未接入mysqli
- **[完成]** 日志记录，报错日志记录，支持配置方式
- **[完成]** 异步投递任务封装
- **[完成]** JWT验证，CSRF保护
- **[完成]** 通过swoole，封装数据库连接池，通过配置实现，提高易用性
- **[完成]** websocket 和 http_server 服务支持，支持配置切换
- **[完成]** 命令行：一键部署框架，数据库迁移，数据填充，服务端启动、停止、重启，创建controller，model类等
- RPC 服务支持
- 集群部署
- 广播系统
- Process 进程监控

## 用法实例 ##
<pre>
 // 框架安装，一件部署
 php bin/ububs.php install
 
 // server 相关命令
 php bin/ububs.php server:start
 php bin/ububs.php server:stop
 php bin/ububs.php server:restart
 
 // 数据库迁移
 php bin/ububs.php db:migration
 php bin/ububs.php db:migration refresh
 php bin/ububs.php db:migration --目录名
 
 // 填充数据
 php bin/ububs.php db:seed
 php bin/ububs.php db:seed refresh
 php bin/ububs.php db:seed --目录名
</pre>
