# AI诗词推荐Agent

AI诗词推荐和赏析应用的命令行程序，通过调用AI大模型接口，根据用户提示词或图片推荐相关的古诗词。

## 功能特性

- ✅ 支持正向提示词和负向提示词
- ✅ 支持图片输入和图像识别
- ✅ 支持多种AI模型（OpenAI GPT系列等）
- ✅ 自动保存推荐结果到数据库
- ✅ 完整的错误处理和日志记录
- ✅ 支持用户个性化推荐

## 安装

### 1. 克隆项目

```bash
cd poetry_agent
```

### 2. 创建虚拟环境（推荐）

```bash
python3 -m venv venv
source venv/bin/activate  # Linux/Mac
# 或
venv\Scripts\activate  # Windows
```

### 3. 安装依赖

```bash
pip install -r requirements.txt
```

### 4. 配置环境变量

复制 `.env.example` 为 `.env` 并修改配置：

```bash
cp .env.example .env
```

编辑 `.env` 文件，设置数据库连接和AI API密钥：

```env
# 数据库配置
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=poetry_db

# AI模型配置
OPENAI_API_KEY=your_openai_api_key
```

### 5. 初始化数据库

确保MySQL数据库已创建，然后运行：

```python
from models.database import init_db
init_db()
```

或使用SQL脚本创建表（参考需求文档中的表结构）。

## 使用方法

### 基本使用

```bash
# 仅使用正向提示词
python poetry_agent.py --prompt "推荐一首关于春天的诗"

# 正向提示词 + 负向提示词
python poetry_agent.py \
  --prompt "推荐一首关于春天的诗" \
  --negative-prompt "不要包含悲伤情绪，不要现代诗"

# 仅使用图片
python poetry_agent.py --image /path/to/image.jpg

# 图片 + 正向提示词
python poetry_agent.py \
  --image /path/to/image.jpg \
  --prompt "推荐一首与图片意境相符的诗词"

# 完整参数示例
python poetry_agent.py \
  --user-id 1001 \
  --prompt "推荐一首关于春天的诗" \
  --negative-prompt "不要包含悲伤情绪" \
  --image /path/to/image.jpg \
  --context "用户喜欢唐诗" \
  --model "gpt-4" \
  --count 1 \
  --verbose
```

### 参数说明

- `-p, --prompt`: 正向提示词（必填，至少提供正向提示词或图片之一）
- `-np, --negative-prompt`: 负向提示词（可选）
- `-i, --image`: 图片文件路径（可选，至少提供正向提示词或图片之一）
- `-u, --user-id`: 用户ID（可选）
- `-c, --context`: 上下文信息（可选）
- `-m, --model`: 指定使用的AI模型（可选，有默认值）
- `-n, --count`: 推荐诗词数量（可选，默认1首）
- `-t, --type`: 推荐类型（推荐/赏析/创作，可选）
- `-f, --config`: 配置文件路径（可选）
- `-v, --verbose`: 详细输出模式（可选）

### 使用配置文件

创建 `config.json` 文件：

```json
{
  "database": {
    "host": "localhost",
    "port": 3306,
    "user": "root",
    "password": "your_password",
    "name": "poetry_db"
  },
  "ai": {
    "default_model": "gpt-4",
    "openai_api_key": "your_api_key"
  }
}
```

然后使用：

```bash
python poetry_agent.py --config config.json --prompt "推荐一首诗"
```

## 项目结构

```
poetry_agent/
├── poetry_agent.py      # 主程序入口
├── config/              # 配置模块
│   ├── __init__.py
│   └── settings.py      # 配置管理
├── models/              # 数据模型
│   ├── __init__.py
│   ├── database.py      # 数据库连接
│   └── recommendation.py # 推荐记录模型
├── utils/               # 工具模块
│   ├── __init__.py
│   ├── ai_client.py     # AI客户端
│   ├── image_processor.py # 图片处理
│   └── logger.py        # 日志配置
├── requirements.txt     # 依赖包
├── .env.example        # 环境变量示例
├── .gitignore          # Git忽略文件
└── README.md           # 说明文档
```

## 返回状态码

- `0`: 执行成功
- `1`: 参数错误
- `2`: API调用失败
- `3`: 数据库操作失败
- `4`: 其他错误

## 注意事项

1. **API密钥**: 确保已正确配置AI模型的API密钥
2. **数据库**: 确保MySQL数据库已创建并配置正确
3. **图片格式**: 支持的图片格式包括 JPG、PNG、WEBP
4. **图片大小**: 默认最大图片大小为10MB
5. **网络连接**: 需要能够访问AI模型的API服务

## 开发计划

- [x] 基础命令行接口
- [x] OpenAI GPT模型支持
- [x] 图片处理和识别
- [x] 数据库存储
- [ ] 百度文心一言支持
- [ ] 阿里通义千问支持
- [ ] 更多AI模型支持

## 许可证

MIT License

