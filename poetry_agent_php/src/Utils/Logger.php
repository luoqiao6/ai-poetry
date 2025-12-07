<?php

namespace PoetryAgent\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use PoetryAgent\Config\Settings;

/**
 * 日志工具类
 */
class Logger
{
    private static ?MonologLogger $instance = null;

    public static function getInstance(?Settings $settings = null): MonologLogger
    {
        if (self::$instance === null) {
            $settings = $settings ?? new Settings();
            
            $logger = new MonologLogger('poetry_agent');
            
            // 控制台输出
            $consoleHandler = new StreamHandler('php://stdout', self::parseLogLevel($settings->getLogLevel()));
            $consoleHandler->setFormatter(new LineFormatter(
                "[%datetime%] %level_name%: %message% %context% %extra%\n",
                'Y-m-d H:i:s'
            ));
            $logger->pushHandler($consoleHandler);
            
            // 文件输出
            $logFile = $settings->getLogFile();
            if ($logFile) {
                $dir = dirname($logFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                $fileHandler = new RotatingFileHandler($logFile, 7, self::parseLogLevel($settings->getLogLevel()));
                $fileHandler->setFormatter(new LineFormatter(
                    "[%datetime%] %level_name%: %message% %context% %extra%\n",
                    'Y-m-d H:i:s'
                ));
                $logger->pushHandler($fileHandler);
            }
            
            self::$instance = $logger;
        }
        
        return self::$instance;
    }

    private static function parseLogLevel(string $level): int
    {
        $level = strtoupper($level);
        switch ($level) {
            case 'DEBUG':
                return MonologLogger::DEBUG;
            case 'INFO':
                return MonologLogger::INFO;
            case 'NOTICE':
                return MonologLogger::NOTICE;
            case 'WARNING':
                return MonologLogger::WARNING;
            case 'ERROR':
                return MonologLogger::ERROR;
            case 'CRITICAL':
                return MonologLogger::CRITICAL;
            case 'ALERT':
                return MonologLogger::ALERT;
            case 'EMERGENCY':
                return MonologLogger::EMERGENCY;
            default:
                return MonologLogger::INFO;
        }
    }
}

