"""
图片处理工具模块
"""
import os
import base64
from pathlib import Path
from typing import Optional, Tuple
from PIL import Image
import logging

from config.settings import settings

logger = logging.getLogger(__name__)


class ImageProcessor:
    """图片处理器"""
    
    def __init__(self):
        self.max_size = settings.max_image_size
        self.allowed_formats = settings.allowed_formats
        self.upload_dir = Path(settings.image_upload_dir)
        self.upload_dir.mkdir(parents=True, exist_ok=True)
    
    def validate_image(self, image_path: str) -> Tuple[bool, Optional[str]]:
        """
        验证图片文件
        
        Args:
            image_path: 图片文件路径
            
        Returns:
            (是否有效, 错误信息)
        """
        if not os.path.exists(image_path):
            return False, f"图片文件不存在: {image_path}"
        
        # 检查文件大小
        file_size = os.path.getsize(image_path)
        if file_size > self.max_size:
            return False, f"图片文件过大: {file_size} bytes (最大: {self.max_size} bytes)"
        
        # 检查文件格式
        file_ext = Path(image_path).suffix[1:].lower()
        if file_ext not in self.allowed_formats:
            return False, f"不支持的图片格式: {file_ext} (支持的格式: {', '.join(self.allowed_formats)})"
        
        # 尝试打开图片验证
        try:
            with Image.open(image_path) as img:
                img.verify()
        except Exception as e:
            return False, f"图片文件损坏或格式错误: {str(e)}"
        
        return True, None
    
    def save_image(self, image_path: str, user_id: Optional[int] = None) -> str:
        """
        保存图片到指定目录
        
        Args:
            image_path: 原始图片路径
            user_id: 用户ID（可选，用于组织目录结构）
            
        Returns:
            保存后的图片路径
        """
        # 验证图片
        is_valid, error = self.validate_image(image_path)
        if not is_valid:
            raise ValueError(error)
        
        # 创建保存目录
        if user_id:
            save_dir = self.upload_dir / str(user_id)
        else:
            save_dir = self.upload_dir / 'default'
        save_dir.mkdir(parents=True, exist_ok=True)
        
        # 生成新文件名
        original_name = Path(image_path).name
        timestamp = int(os.path.getmtime(image_path))
        new_name = f"{timestamp}_{original_name}"
        save_path = save_dir / new_name
        
        # 复制文件
        import shutil
        shutil.copy2(image_path, save_path)
        
        logger.info(f"图片已保存: {save_path}")
        return str(save_path)
    
    def encode_image_to_base64(self, image_path: str) -> str:
        """
        将图片编码为base64字符串
        
        Args:
            image_path: 图片文件路径
            
        Returns:
            base64编码的图片字符串
        """
        with open(image_path, 'rb') as f:
            image_data = f.read()
            base64_str = base64.b64encode(image_data).decode('utf-8')
            return base64_str
    
    def get_image_info(self, image_path: str) -> dict:
        """
        获取图片信息
        
        Args:
            image_path: 图片文件路径
            
        Returns:
            图片信息字典
        """
        with Image.open(image_path) as img:
            return {
                'format': img.format,
                'size': img.size,
                'mode': img.mode,
                'file_size': os.path.getsize(image_path)
            }

