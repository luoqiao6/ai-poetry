# 快速开始指南

## 1. 安装依赖

```bash
cd poetry_agent_php
composer install
```

## 2. 配置环境

复制环境变量文件并编辑：

```bash
cp .env.example .env
```

编辑 `.env` 文件，至少配置以下内容：

```env
# 数据库配置
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=poetry_db

# AI模型配置（必须）
OPENAI_API_KEY=your_openai_api_key_here
```

## 3. 创建数据库和表

```bash
# 创建数据库
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS poetry_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 导入表结构
mysql -u root -p poetry_db < database/init.sql
```

或者直接在MySQL中执行 `database/init.sql` 文件中的SQL。

## 4. 设置权限

```bash
chmod +x bin/poetry-agent
```

## 5. 测试运行

```bash
# 基本测试 - 仅使用提示词
php bin/poetry-agent --prompt "推荐一首关于春天的诗" --verbose

# 使用图片（需要先准备一张图片）
php bin/poetry-agent --image /path/to/your/image.jpg --prompt "推荐一首与图片意境相符的诗词" --verbose
```

## 常见问题

### 1. 找不到 composer 命令

确保已安装 Composer：
- 访问 https://getcomposer.org/download/
- 或使用 `curl -sS https://getcomposer.org/installer | php`

### 2. 数据库连接失败

- 检查 `.env` 文件中的数据库配置
- 确保MySQL服务正在运行
- 确保数据库用户有足够的权限

### 3. OpenAI API调用失败

- 检查 `OPENAI_API_KEY` 是否正确配置
- 确保API密钥有效且有足够的配额
- 检查网络连接

### 4. 图片处理失败

- 确保PHP已安装GD扩展：`php -m | grep gd`
- 确保图片上传目录有写入权限：`chmod -R 755 uploads/`

### 5. 日志文件无法创建

确保日志目录有写入权限：
```bash
chmod -R 755 logs/
```

## 下一步

查看 [README.md](README.md) 了解更多详细信息和高级用法。

