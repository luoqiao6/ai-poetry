#!/usr/bin/env python3
"""
数据库初始化脚本
"""
import sys
from pathlib import Path

# 添加项目根目录到路径
sys.path.insert(0, str(Path(__file__).parent))

from models.database import init_db, engine
from models.recommendation import Recommendation
from utils.logger import setup_logger

logger = setup_logger()


def main():
    """初始化数据库表"""
    try:
        logger.info("开始初始化数据库...")
        init_db()
        logger.info("数据库初始化完成！")
        print("数据库表创建成功！")
    except Exception as e:
        logger.error(f"数据库初始化失败: {e}")
        print(f"错误: {e}")
        sys.exit(1)


if __name__ == '__main__':
    main()

