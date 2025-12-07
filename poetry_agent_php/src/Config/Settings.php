<?php

namespace PoetryAgent\Config;

use Dotenv\Dotenv;

/**
 * 应用配置类
 */
class Settings
{
    private array $configData = [];
    private ?string $configFile = null;

    public function __construct(?string $configFile = null)
    {
        $this->configFile = $configFile;
        
        // 加载环境变量
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
        }
        
        // 加载配置文件
        if ($configFile && file_exists($configFile)) {
            $this->configData = json_decode(file_get_contents($configFile), true) ?? [];
        }
    }

    // 数据库配置
    public function getDbHost(): string
    {
        return $this->configData['database']['host'] ?? $_ENV['DB_HOST'] ?? 'localhost';
    }

    public function getDbPort(): int
    {
        return (int)($this->configData['database']['port'] ?? $_ENV['DB_PORT'] ?? 3306);
    }

    public function getDbUser(): string
    {
        return $this->configData['database']['user'] ?? $_ENV['DB_USER'] ?? 'root';
    }

    public function getDbPassword(): string
    {
        return $this->configData['database']['password'] ?? $_ENV['DB_PASSWORD'] ?? '';
    }

    public function getDbName(): string
    {
        return $this->configData['database']['name'] ?? $_ENV['DB_NAME'] ?? 'poetry_db';
    }

    // AI模型配置
    public function getDefaultModel(): string
    {
        return $this->configData['ai']['default_model'] ?? $_ENV['DEFAULT_MODEL'] ?? 'gpt-4';
    }

    public function getOpenAiApiKey(): ?string
    {
        return $this->configData['ai']['openai_api_key'] ?? $_ENV['OPENAI_API_KEY'] ?? null;
    }

    public function getOpenAiBaseUrl(): ?string
    {
        return $this->configData['ai']['openai_base_url'] ?? $_ENV['OPENAI_BASE_URL'] ?? null;
    }

    public function getBaiduApiKey(): ?string
    {
        return $this->configData['ai']['baidu_api_key'] ?? $_ENV['BAIDU_API_KEY'] ?? null;
    }

    public function getBaiduSecretKey(): ?string
    {
        return $this->configData['ai']['baidu_secret_key'] ?? $_ENV['BAIDU_SECRET_KEY'] ?? null;
    }

    public function getAliApiKey(): ?string
    {
        return $this->configData['ai']['ali_api_key'] ?? $_ENV['ALI_API_KEY'] ?? null;
    }

    // 图片配置
    public function getImageUploadDir(): string
    {
        return $this->configData['image']['upload_dir'] ?? $_ENV['IMAGE_UPLOAD_DIR'] ?? __DIR__ . '/../../uploads/images';
    }

    public function getMaxImageSize(): int
    {
        return (int)($this->configData['image']['max_size'] ?? $_ENV['MAX_IMAGE_SIZE'] ?? 10485760); // 10MB
    }

    public function getAllowedImageFormats(): array
    {
        return $this->configData['image']['allowed_formats'] ?? ['jpg', 'jpeg', 'png', 'webp'];
    }

    // API配置
    public function getApiTimeout(): int
    {
        return (int)($this->configData['api']['timeout'] ?? $_ENV['API_TIMEOUT'] ?? 60);
    }

    public function getApiRetryTimes(): int
    {
        return (int)($this->configData['api']['retry_times'] ?? $_ENV['API_RETRY_TIMES'] ?? 3);
    }

    // 日志配置
    public function getLogLevel(): string
    {
        return $this->configData['log']['level'] ?? $_ENV['LOG_LEVEL'] ?? 'INFO';
    }

    public function getLogFile(): ?string
    {
        return $this->configData['log']['file'] ?? $_ENV['LOG_FILE'] ?? __DIR__ . '/../../logs/poetry_agent.log';
    }
}

