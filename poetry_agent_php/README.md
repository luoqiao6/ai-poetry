# AI诗词推荐Agent - PHP版本

基于PHP的AI诗词推荐命令行程序，支持通过提示词或图片推荐古诗词。

## 功能特性

- ✅ 支持正向提示词和负向提示词
- ✅ 支持图片输入和图像识别（多模态AI）
- ✅ 支持多种AI模型（OpenAI GPT系列等）
- ✅ 完整的数据库存储
- ✅ 详细的日志记录
- ✅ 命令行参数支持
- ✅ 配置文件支持

## 系统要求

- PHP >= 7.4
- Composer
- MySQL 5.7+ 或 MySQL 8.0+
- GD扩展（用于图片处理）

## 安装

1. 克隆或下载项目到本地

2. 安装依赖：
```bash
cd poetry_agent_php
composer install
```

3. 配置环境变量：
```bash
cp .env.example .env
# 编辑 .env 文件，配置数据库和AI API密钥
```

4. 创建数据库表：
```bash
# 执行SQL脚本创建表
mysql -u root -p poetry_db < database/migrations/create_recommendations_table.php
# 或者直接在MySQL中执行 database/migrations/create_recommendations_table.php 中的SQL
```

5. 设置执行权限：
```bash
chmod +x bin/poetry-agent
```

## 配置说明

### 环境变量配置（.env）

```env
# 数据库配置
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=poetry_db

# AI模型配置
DEFAULT_MODEL=gpt-4
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_BASE_URL=https://api.openai.com/v1

# 图片配置
IMAGE_UPLOAD_DIR=./uploads/images
MAX_IMAGE_SIZE=10485760  # 10MB

# API配置
API_TIMEOUT=60
API_RETRY_TIMES=3

# 日志配置
LOG_LEVEL=INFO
LOG_FILE=./logs/poetry_agent.log
```

### JSON配置文件（可选）

也可以使用JSON配置文件，通过 `--config` 参数指定：

```json
{
    "database": {
        "host": "localhost",
        "port": 3306,
        "user": "root",
        "password": "",
        "name": "poetry_db"
    },
    "ai": {
        "default_model": "gpt-4",
        "openai_api_key": "your_api_key"
    }
}
```

## 使用方法

### 基本使用

```bash
# 仅正向提示词
php bin/poetry-agent --prompt "推荐一首关于春天的诗"

# 正向提示词 + 负向提示词
php bin/poetry-agent \
  --prompt "推荐一首关于春天的诗" \
  --negative-prompt "不要包含悲伤情绪，不要现代诗"

# 仅图片输入
php bin/poetry-agent --image /path/to/image.jpg

# 图片 + 正向提示词
php bin/poetry-agent \
  --image /path/to/image.jpg \
  --prompt "推荐一首与图片意境相符的诗词"

# 图片 + 正向提示词 + 负向提示词
php bin/poetry-agent \
  --image /path/to/image.jpg \
  --prompt "推荐一首与图片意境相符的诗词" \
  --negative-prompt "不要现代诗"
```

### 完整参数示例

```bash
php bin/poetry-agent \
  --user-id 1001 \
  --prompt "推荐一首关于春天的诗" \
  --negative-prompt "不要包含悲伤情绪" \
  --image /path/to/image.jpg \
  --context "用户喜欢唐诗" \
  --model "gpt-4" \
  --count 3 \
  --type "推荐" \
  --verbose
```

### 使用配置文件

```bash
php bin/poetry-agent --config config.json --prompt "推荐一首诗"
```

## 命令行参数

| 参数 | 简写 | 说明 | 必填 |
|------|------|------|------|
| `--prompt` | `-p` | 正向提示词（至少提供正向提示词或图片之一） | 是* |
| `--negative-prompt` | `-np` | 负向提示词 | 否 |
| `--image` | `-i` | 图片文件路径（至少提供正向提示词或图片之一） | 是* |
| `--user-id` | `-u` | 用户ID | 否 |
| `--context` | `-c` | 上下文信息 | 否 |
| `--model` | `-m` | 指定使用的AI模型 | 否 |
| `--count` | `-n` | 推荐诗词数量（默认1首） | 否 |
| `--type` | `-t` | 推荐类型（推荐/赏析/创作，默认：推荐） | 否 |
| `--config` | `-f` | 配置文件路径 | 否 |
| `--verbose` | `-v` | 详细输出模式 | 否 |

*注：`--prompt` 和 `--image` 至少需要提供一个

## 返回状态码

- `0`: 执行成功
- `1`: 参数错误
- `2`: API调用失败
- `3`: 数据库操作失败
- `4`: 其他错误

## 项目结构

```
poetry_agent_php/
├── bin/
│   └── poetry-agent          # 命令行入口
├── src/
│   ├── Command/              # 命令类
│   │   └── PoetryAgentCommand.php
│   ├── Config/               # 配置类
│   │   └── Settings.php
│   ├── Models/               # 数据模型
│   │   ├── Database.php
│   │   └── Recommendation.php
│   └── Utils/                # 工具类
│       ├── Logger.php
│       ├── ImageProcessor.php
│       ├── AIClient.php
│       ├── OpenAIClient.php
│       └── AIClientFactory.php
├── config/                   # 配置文件目录
├── database/                 # 数据库脚本
│   └── migrations/
├── logs/                     # 日志目录
├── uploads/                  # 上传文件目录
│   └── images/
├── composer.json
├── .env.example
├── config.example.json
└── README.md
```

## 开发说明

### 添加新的AI客户端

1. 在 `src/Utils/` 目录下创建新的客户端类，继承 `AIClient`
2. 实现 `generatePoetryRecommendation` 方法
3. 在 `AIClientFactory` 中注册新客户端

### 数据库迁移

数据库表结构定义在 `database/migrations/create_recommendations_table.php` 中。

## 注意事项

1. 确保PHP已安装GD扩展用于图片处理
2. 确保数据库连接配置正确
3. 确保AI API密钥有效且有足够的配额
4. 图片上传目录需要有写入权限
5. 日志目录需要有写入权限

## 许可证

MIT License

## 作者

AI Poetry Project

