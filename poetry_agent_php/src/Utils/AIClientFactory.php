<?php

namespace PoetryAgent\Utils;

use PoetryAgent\Config\Settings;

/**
 * AI客户端工厂
 */
class AIClientFactory
{
    /**
     * 创建AI客户端
     *
     * @param string $modelName 模型名称
     * @param Settings|null $settings 配置
     * @return AIClient
     * @throws \RuntimeException
     */
    public static function createClient(string $modelName, ?Settings $settings = null): AIClient
    {
        $settings = $settings ?? new Settings();
        
        if (strpos($modelName, 'gpt') === 0) {
            return new OpenAIClient($settings);
        }
        
        // 可以在这里添加其他AI客户端的支持
        // if (strpos($modelName, 'baidu') === 0) {
        //     return new BaiduClient($settings);
        // }
        
        throw new \RuntimeException("不支持的模型: {$modelName}");
    }
}

