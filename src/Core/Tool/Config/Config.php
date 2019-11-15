<?php

namespace Ububs\Core\Tool\Config;

class Config
{

    public static $config;

    private function __construct()
    {}

    /**
     * 获取配置
     * @param  string  $data    键app.SERVER_TYPE
     * @param  string  $default 默认值
     * @param  boolean $strict  是否严格匹配
     * @return string | array
     */
    public static function get($data, $default = null)
    {
        $fileName = $key = '';
        if (strpos($data, '.') > -1) {
            list($fileName, $key) = \explode('.', $data);
        } else {
            $fileName = $data;
        }
        $result = isset(self::$config[$fileName]) ? self::$config[$fileName] : $default;
        if ($key !== '' && isset(self::$config[$fileName])) {
            $result = isset($result[$key]) ? $result[$key] : $default;
        }
        return $result;
    }

    /**
     * 设置参数值
     * @param  string  $data   键app.SERVER_TYPE
     * @param  string  $value  值
     * @param  boolean $strict 是否覆盖
     * @return bool
     */
    public static function set($data, $value, $cover = true)
    {
        $fileName = $key = '';
        if (strpos($data, '.') > -1) {
            list($fileName, $key) = explode('.', $data);
        } else {
            $fileName = $data;
        }
        if ($key === '') {
            if (isset(self::$config[$fileName])) {
                return false;
            }

            self::$config[$fileName] = $value;
        } else {
            $fileExist = isset(self::$config[$fileName]);
            if (!$fileExist) {
                self::$config[$fileName] = [];
            }
            $keyExist = isset(self::$config[$fileName][$key]);
            if (!$keyExist || $cover) {
                self::$config[$fileName][$key] = $value;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * 加载配置文件
     * @param  array $configPath 目录
     * @return bool
     */
    public static function load($configDirArr): void
    {
        $config = [];
        foreach ($configDirArr as $configDir) {
            if (!is_dir($configDir)) {
                continue;
            }
            $fileArr = dir_tree($configDir, "/.php$/");
            if (!empty($fileArr)) {
                foreach ($fileArr as $dir => $files) {
                    array_map(function ($filename) use (&$config, $dir) {
                        $filePath = $dir . DS . $filename;
                        if (function_exists("opcache_invalidate")) {
                            \opcache_invalidate($filePath);
                        }
                        $name = basename($filename, ".php");
                        $cs   = include "{$filePath}";
                        if (isset($config[$name])) {
                            foreach ($config[$name] as $key => $value) {
                                if (!isset($cs[$key])) {
                                    continue;
                                }
                                if (is_array($config[$name][$key])) {
                                    $config[$name][$key] = array_merge($config[$name][$key], $cs[$key]);
                                } else {
                                    $config[$name][$key] = $cs[$key];
                                }
                            }
                        } else {
                            $config[$name] = $cs;
                        }
                    }, $files);
                }
            }
        }
        self::$config = $config;
    }
}
