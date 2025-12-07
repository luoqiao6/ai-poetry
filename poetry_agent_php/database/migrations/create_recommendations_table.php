<?php

/**
 * 创建推荐记录表的SQL脚本
 * 
 * 使用方法：
 * 1. 直接在MySQL中执行此SQL
 * 2. 或使用Laravel迁移工具
 */

$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `recommendations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL COMMENT '用户ID（外键，关联users表）',
    `positive_prompt` TEXT NULL COMMENT '正向提示词（用户期望的诗词特征）',
    `negative_prompt` TEXT NULL COMMENT '负向提示词（需要排除的诗词特征）',
    `image_path` VARCHAR(500) NULL COMMENT '图片文件路径（如适用）',
    `image_description` TEXT NULL COMMENT '图片内容描述（AI识别结果）',
    `context` TEXT NULL COMMENT '上下文信息',
    `poem_title` VARCHAR(200) NULL COMMENT '诗词标题',
    `poem_content` TEXT NULL COMMENT '诗词内容',
    `author` VARCHAR(100) NULL COMMENT '作者',
    `dynasty` VARCHAR(50) NULL COMMENT '朝代',
    `appreciation` TEXT NULL COMMENT '赏析内容',
    `model_name` VARCHAR(100) NULL COMMENT '使用的AI模型',
    `model_version` VARCHAR(50) NULL COMMENT '模型版本',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态（1:成功 0:失败）',
    `error_message` TEXT NULL COMMENT '错误信息（如有）',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_author` (`author`),
    INDEX `idx_dynasty` (`dynasty`),
    INDEX `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推荐记录表';
SQL;

// 如果通过命令行执行，输出SQL
if (php_sapi_name() === 'cli') {
    echo $sql . "\n";
}

