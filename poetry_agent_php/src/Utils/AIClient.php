<?php

namespace PoetryAgent\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PoetryAgent\Config\Settings;

/**
 * AI客户端基类
 */
abstract class AIClient
{
    protected ?string $apiKey;
    protected ?string $baseUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected Client $httpClient;
    protected Settings $settings;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null, ?Settings $settings = null)
    {
        $this->settings = $settings ?? new Settings();
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->timeout = $this->settings->getApiTimeout();
        $this->retryTimes = $this->settings->getApiRetryTimes();
        $this->httpClient = new Client([
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * 生成诗词推荐
     *
     * @param string|null $positivePrompt 正向提示词
     * @param string|null $negativePrompt 负向提示词
     * @param string|null $imagePath 图片路径
     * @param string|null $imageDescription 图片描述
     * @param string|null $context 上下文信息
     * @param int $count 推荐数量
     * @return array 推荐结果
     */
    abstract public function generatePoetryRecommendation(
        ?string $positivePrompt = null,
        ?string $negativePrompt = null,
        ?string $imagePath = null,
        ?string $imageDescription = null,
        ?string $context = null,
        int $count = 1
    ): array;

    /**
     * 重试请求
     *
     * @param callable $func 请求函数
     * @param mixed ...$args 参数
     * @return mixed
     * @throws \Exception
     */
    protected function retryRequest(callable $func, ...$args)
    {
        $lastError = null;
        for ($attempt = 0; $attempt < $this->retryTimes; $attempt++) {
            try {
                return $func(...$args);
            } catch (\Exception $e) {
                $lastError = $e;
                $logger = Logger::getInstance($this->settings);
                $logger->warning("请求失败 (尝试 " . ($attempt + 1) . "/{$this->retryTimes}): " . $e->getMessage());
                if ($attempt < $this->retryTimes - 1) {
                    sleep(2 ** $attempt); // 指数退避
                }
            }
        }
        throw $lastError;
    }
}

