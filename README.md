# AI诗词推荐和赏析应用

基于AI大模型的诗词推荐和赏析应用，通过AI技术为用户提供个性化的诗词推荐和深度赏析服务。

## 项目结构

```
ai-poetry/
├── poetry_agent/          # 第一部分：AI诗词推荐Agent程序
│   ├── poetry_agent.py    # 主程序入口
│   ├── config/            # 配置模块
│   ├── models/            # 数据模型
│   ├── utils/             # 工具模块
│   └── README.md          # Agent使用说明
├── 需求设计文档.md        # 完整的需求设计文档
└── README.md              # 项目总览（本文件）
```

## 项目组成部分

### 第一部分：AI诗词推荐Agent（已完成）

命令行程序，通过调用AI大模型接口获取诗词推荐和赏析内容，并将结果保存到数据库。

**功能特性：**
- ✅ 支持正向提示词和负向提示词
- ✅ 支持图片输入和图像识别
- ✅ 支持多种AI模型（OpenAI GPT系列等）
- ✅ 自动保存推荐结果到数据库
- ✅ 完整的错误处理和日志记录
- ✅ 支持用户个性化推荐

**详细文档：** 请查看 [poetry_agent/README.md](poetry_agent/README.md)

### 第二部分：应用API接口（待开发）

提供RESTful API接口，供客户端调用，返回数据库中存储的AI推荐诗词内容。

### 第三部分：Web后台管理系统（待开发）

提供Web管理界面，用于管理系统配置、查看和管理AI推荐内容、管理用户提示词等。

### 第四部分：客户端应用（待开发）

多平台客户端应用，为用户提供诗词推荐和赏析服务。

## 快速开始

### 1. 克隆项目

```bash
git clone https://github.com/your-username/ai-poetry.git
cd ai-poetry
```

### 2. 设置第一部分（AI诗词推荐Agent）

```bash
cd poetry_agent
pip install -r requirements.txt
cp .env.example .env
# 编辑 .env 文件，设置数据库和API密钥
python init_db.py
```

详细使用说明请参考 [poetry_agent/README.md](poetry_agent/README.md)

## 开发计划

- [x] 第一部分：AI诗词推荐Agent程序
- [ ] 第二部分：应用API接口
- [ ] 第三部分：Web后台管理系统
- [ ] 第四部分：客户端应用

## 技术栈

- **后端语言**: Python 3.8+
- **数据库**: MySQL 8.0+ / PostgreSQL
- **AI模型**: OpenAI GPT系列、百度文心一言、阿里通义千问等
- **ORM**: SQLAlchemy

## 文档

- [需求设计文档](需求设计文档.md) - 完整的需求和设计文档

## 许可证

MIT License

## 贡献

欢迎提交 Issue 和 Pull Request！

