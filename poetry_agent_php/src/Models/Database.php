<?php

namespace PoetryAgent\Models;

use Illuminate\Database\Capsule\Manager as Capsule;
use PoetryAgent\Config\Settings;

/**
 * 数据库连接管理
 */
class Database
{
    private static ?Capsule $capsule = null;

    /**
     * 初始化数据库连接
     *
     * @param Settings|null $settings 配置
     * @return Capsule
     */
    public static function init(?Settings $settings = null): Capsule
    {
        if (self::$capsule === null) {
            $settings = $settings ?? new Settings();
            
            self::$capsule = new Capsule();
            
            self::$capsule->addConnection([
                'driver' => 'mysql',
                'host' => $settings->getDbHost(),
                'port' => $settings->getDbPort(),
                'database' => $settings->getDbName(),
                'username' => $settings->getDbUser(),
                'password' => $settings->getDbPassword(),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]);
            
            self::$capsule->setAsGlobal();
            self::$capsule->bootEloquent();
        }
        
        return self::$capsule;
    }

    /**
     * 获取数据库连接
     *
     * @return Capsule
     */
    public static function getInstance(): Capsule
    {
        if (self::$capsule === null) {
            self::init();
        }
        return self::$capsule;
    }
}

