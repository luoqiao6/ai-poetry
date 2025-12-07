<?php

namespace PoetryAgent\Utils;

use PoetryAgent\Config\Settings;

/**
 * OpenAI客户端
 */
class OpenAIClient extends AIClient
{
    public function __construct(?Settings $settings = null)
    {
        $settings = $settings ?? new Settings();
        parent::__construct(
            $settings->getOpenAiApiKey(),
            $settings->getOpenAiBaseUrl() ?? 'https://api.openai.com/v1',
            $settings
        );
    }

    public function generatePoetryRecommendation(
        ?string $positivePrompt = null,
        ?string $negativePrompt = null,
        ?string $imagePath = null,
        ?string $imageDescription = null,
        ?string $context = null,
        int $count = 1
    ): array {
        if (!$this->apiKey) {
            throw new \RuntimeException("OpenAI API Key未配置");
        }

        $imageProcessor = new ImageProcessor($this->settings);
        
        // 如果有图片但没有描述，先识别图片
        if ($imagePath && !$imageDescription) {
            $imageDescription = $this->describeImage($imagePath, $imageProcessor);
        }

        // 构建提示词
        $systemPrompt = "你是一个专业的诗词推荐助手。请根据用户的需求推荐合适的古诗词，并提供详细的赏析。\n\n请按照以下JSON格式返回结果：\n{\n    \"poems\": [\n        {\n            \"title\": \"诗词标题\",\n            \"content\": \"诗词内容（完整）\",\n            \"author\": \"作者\",\n            \"dynasty\": \"朝代\",\n            \"appreciation\": \"赏析内容\"\n        }\n    ]\n}";

        $userPromptParts = [];
        
        if ($imageDescription) {
            $userPromptParts[] = "图片描述：{$imageDescription}";
        }
        
        if ($positivePrompt) {
            $userPromptParts[] = "推荐要求：{$positivePrompt}";
        }
        
        if ($negativePrompt) {
            $userPromptParts[] = "排除要求：{$negativePrompt}";
        }
        
        if ($context) {
            $userPromptParts[] = "上下文信息：{$context}";
        }

        $userPrompt = implode("\n", $userPromptParts);
        if (!$userPrompt && !$imagePath) {
            throw new \InvalidArgumentException("至少需要提供正向提示词或图片");
        }

        // 构建消息
        $messages = [
            ["role" => "system", "content" => $systemPrompt],
        ];

        // 如果有图片，使用视觉模型
        if ($imagePath) {
            $base64Image = $imageProcessor->encodeImageToBase64($imagePath);
            $mimeType = $imageProcessor->getImageMimeType($imagePath);
            
            $messages[] = [
                "role" => "user",
                "content" => [
                    ["type" => "text", "text" => $userPrompt ?: "请根据图片推荐相关的古诗词"],
                    [
                        "type" => "image_url",
                        "image_url" => [
                            "url" => "data:{$mimeType};base64,{$base64Image}"
                        ]
                    ]
                ]
            ];
            
            $model = "gpt-4-vision-preview";
        } else {
            $messages[] = [
                "role" => "user",
                "content" => $userPrompt
            ];
            $model = "gpt-4";
        }

        // 调用API
        $response = $this->retryRequest(function() use ($model, $messages) {
            return $this->httpClient->post("{$this->baseUrl}/chat/completions", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                ],
            ]);
        });

        $responseData = json_decode($response->getBody()->getContents(), true);
        $content = $responseData['choices'][0]['message']['content'] ?? '';

        // 解析响应
        try {
            // 尝试提取JSON
            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $result = json_decode($matches[0], true);
            } else {
                $result = $this->parseTextResponse($content);
            }
        } catch (\Exception $e) {
            $logger = Logger::getInstance($this->settings);
            $logger->warning("JSON解析失败，使用文本解析: " . $e->getMessage());
            $result = $this->parseTextResponse($content);
        }

        $poems = $result['poems'] ?? [];
        if (empty($poems)) {
            throw new \RuntimeException("AI返回结果中没有找到诗词");
        }

        if ($count == 1) {
            $poem = $poems[0];
            return [
                'poem_title' => $poem['title'] ?? '',
                'poem_content' => $poem['content'] ?? '',
                'author' => $poem['author'] ?? '',
                'dynasty' => $poem['dynasty'] ?? '',
                'appreciation' => $poem['appreciation'] ?? '',
                'image_description' => $imageDescription,
            ];
        } else {
            return [
                'poems' => array_slice($poems, 0, $count),
                'image_description' => $imageDescription,
            ];
        }
    }

    /**
     * 描述图片内容
     *
     * @param string $imagePath 图片路径
     * @param ImageProcessor $imageProcessor 图片处理器
     * @return string 图片描述
     */
    private function describeImage(string $imagePath, ImageProcessor $imageProcessor): string
    {
        $base64Image = $imageProcessor->encodeImageToBase64($imagePath);
        $mimeType = $imageProcessor->getImageMimeType($imagePath);

        $messages = [
            [
                "role" => "user",
                "content" => [
                    ["type" => "text", "text" => "请详细描述这张图片的内容、意境和情感，用于推荐相关的古诗词。"],
                    [
                        "type" => "image_url",
                        "image_url" => [
                            "url" => "data:{$mimeType};base64,{$base64Image}"
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->httpClient->post("{$this->baseUrl}/chat/completions", [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4-vision-preview',
                'messages' => $messages,
                'max_tokens' => 500,
            ],
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);
        return $responseData['choices'][0]['message']['content'] ?? '';
    }

    /**
     * 解析文本响应
     *
     * @param string $content 响应内容
     * @return array 解析结果
     */
    private function parseTextResponse(string $content): array
    {
        return [
            'poems' => [
                [
                    'title' => '未知',
                    'content' => $content,
                    'author' => '未知',
                    'dynasty' => '未知',
                    'appreciation' => '',
                ]
            ]
        ];
    }
}

