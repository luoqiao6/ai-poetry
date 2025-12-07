"""
配置文件管理模块
"""
import os
import json
from pathlib import Path
from typing import Dict, Any, Optional
from dotenv import load_dotenv

# 加载环境变量
load_dotenv()


class Settings:
    """应用配置类"""
    
    def __init__(self, config_file: Optional[str] = None):
        """
        初始化配置
        
        Args:
            config_file: 配置文件路径（可选）
        """
        self.config_file = config_file
        self.config_data = {}
        
        if config_file and os.path.exists(config_file):
            with open(config_file, 'r', encoding='utf-8') as f:
                self.config_data = json.load(f)
    
    # 数据库配置
    @property
    def db_host(self) -> str:
        return self.config_data.get('database', {}).get('host') or os.getenv('DB_HOST', 'localhost')
    
    @property
    def db_port(self) -> int:
        return self.config_data.get('database', {}).get('port') or int(os.getenv('DB_PORT', '3306'))
    
    @property
    def db_user(self) -> str:
        return self.config_data.get('database', {}).get('user') or os.getenv('DB_USER', 'root')
    
    @property
    def db_password(self) -> str:
        return self.config_data.get('database', {}).get('password') or os.getenv('DB_PASSWORD', '')
    
    @property
    def db_name(self) -> str:
        return self.config_data.get('database', {}).get('name') or os.getenv('DB_NAME', 'poetry_db')
    
    @property
    def db_url(self) -> str:
        """构建数据库连接URL"""
        return f"mysql+pymysql://{self.db_user}:{self.db_password}@{self.db_host}:{self.db_port}/{self.db_name}?charset=utf8mb4"
    
    # AI模型配置
    @property
    def default_model(self) -> str:
        return self.config_data.get('ai', {}).get('default_model') or os.getenv('DEFAULT_MODEL', 'gpt-4')
    
    @property
    def openai_api_key(self) -> Optional[str]:
        return self.config_data.get('ai', {}).get('openai_api_key') or os.getenv('OPENAI_API_KEY')
    
    @property
    def openai_base_url(self) -> Optional[str]:
        return self.config_data.get('ai', {}).get('openai_base_url') or os.getenv('OPENAI_BASE_URL')
    
    @property
    def baidu_api_key(self) -> Optional[str]:
        return self.config_data.get('ai', {}).get('baidu_api_key') or os.getenv('BAIDU_API_KEY')
    
    @property
    def baidu_secret_key(self) -> Optional[str]:
        return self.config_data.get('ai', {}).get('baidu_secret_key') or os.getenv('BAIDU_SECRET_KEY')
    
    @property
    def ali_api_key(self) -> Optional[str]:
        return self.config_data.get('ai', {}).get('ali_api_key') or os.getenv('ALI_API_KEY')
    
    # 图片配置
    @property
    def image_upload_dir(self) -> str:
        return self.config_data.get('image', {}).get('upload_dir') or os.getenv('IMAGE_UPLOAD_DIR', './uploads/images')
    
    @property
    def max_image_size(self) -> int:
        """最大图片大小（字节）"""
        return self.config_data.get('image', {}).get('max_size') or int(os.getenv('MAX_IMAGE_SIZE', '10485760'))  # 10MB
    
    @property
    def allowed_image_formats(self) -> list:
        return self.config_data.get('image', {}).get('allowed_formats') or ['jpg', 'jpeg', 'png', 'webp']
    
    # API配置
    @property
    def api_timeout(self) -> int:
        """API请求超时时间（秒）"""
        return self.config_data.get('api', {}).get('timeout') or int(os.getenv('API_TIMEOUT', '60'))
    
    @property
    def api_retry_times(self) -> int:
        """API重试次数"""
        return self.config_data.get('api', {}).get('retry_times') or int(os.getenv('API_RETRY_TIMES', '3'))
    
    # 日志配置
    @property
    def log_level(self) -> str:
        return self.config_data.get('log', {}).get('level') or os.getenv('LOG_LEVEL', 'INFO')
    
    @property
    def log_file(self) -> Optional[str]:
        return self.config_data.get('log', {}).get('file') or os.getenv('LOG_FILE')


# 全局配置实例
settings = Settings()

