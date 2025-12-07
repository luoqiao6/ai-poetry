<?php

namespace PoetryAgent\Utils;

use Intervention\Image\ImageManager;
use PoetryAgent\Config\Settings;

/**
 * 图片处理工具类
 */
class ImageProcessor
{
    private Settings $settings;
    private ImageManager $imageManager;

    public function __construct(?Settings $settings = null)
    {
        $this->settings = $settings ?? new Settings();
        $this->imageManager = new ImageManager(['driver' => 'gd']);
    }

    /**
     * 验证图片
     *
     * @param string $imagePath 图片路径
     * @return array [isValid, errorMessage]
     */
    public function validateImage(string $imagePath): array
    {
        if (!file_exists($imagePath)) {
            return [false, "图片文件不存在: {$imagePath}"];
        }

        $fileSize = filesize($imagePath);
        $maxSize = $this->settings->getMaxImageSize();
        
        if ($fileSize > $maxSize) {
            return [false, "图片大小超过限制: " . round($fileSize / 1024 / 1024, 2) . "MB (最大: " . round($maxSize / 1024 / 1024, 2) . "MB)"];
        }

        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $allowedFormats = $this->settings->getAllowedImageFormats();
        
        if (!in_array($extension, $allowedFormats)) {
            return [false, "不支持的图片格式: {$extension} (支持的格式: " . implode(', ', $allowedFormats) . ")"];
        }

        // 尝试打开图片验证是否为有效图片
        try {
            $this->imageManager->make($imagePath);
        } catch (\Exception $e) {
            return [false, "无效的图片文件: " . $e->getMessage()];
        }

        return [true, null];
    }

    /**
     * 保存图片
     *
     * @param string $imagePath 原始图片路径
     * @param int|null $userId 用户ID
     * @return string 保存后的图片路径
     */
    public function saveImage(string $imagePath, ?int $userId = null): string
    {
        $uploadDir = $this->settings->getImageUploadDir();
        
        // 创建目录结构: uploads/images/YYYY/MM/DD/
        $datePath = date('Y/m/d');
        $fullPath = $uploadDir . '/' . $datePath;
        
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        // 生成文件名
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $filename = uniqid('img_', true) . '.' . $extension;
        if ($userId) {
            $filename = "user_{$userId}_" . $filename;
        }
        
        $targetPath = $fullPath . '/' . $filename;
        
        // 复制文件
        copy($imagePath, $targetPath);
        
        // 返回相对路径
        return $datePath . '/' . $filename;
    }

    /**
     * 将图片编码为Base64
     *
     * @param string $imagePath 图片路径
     * @return string Base64编码的图片
     */
    public function encodeImageToBase64(string $imagePath): string
    {
        $imageData = file_get_contents($imagePath);
        return base64_encode($imageData);
    }

    /**
     * 获取图片MIME类型
     *
     * @param string $imagePath 图片路径
     * @return string MIME类型
     */
    public function getImageMimeType(string $imagePath): string
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'webp':
                return 'image/webp';
            case 'gif':
                return 'image/gif';
            default:
                return 'image/jpeg';
        }
    }
}

