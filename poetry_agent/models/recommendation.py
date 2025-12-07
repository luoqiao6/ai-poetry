"""
推荐记录数据模型
"""
from sqlalchemy import Column, BigInteger, Text, String, Integer, DateTime, func
from sqlalchemy.orm import relationship
from datetime import datetime

from models.database import Base


class Recommendation(Base):
    """推荐记录表"""
    __tablename__ = 'recommendations'
    
    id = Column(BigInteger, primary_key=True, autoincrement=True, comment='主键，自增')
    user_id = Column(BigInteger, nullable=True, index=True, comment='用户ID（外键，关联users表）')
    positive_prompt = Column(Text, nullable=True, comment='正向提示词（用户期望的诗词特征）')
    negative_prompt = Column(Text, nullable=True, comment='负向提示词（需要排除的诗词特征）')
    image_path = Column(String(500), nullable=True, comment='图片文件路径（如适用）')
    image_description = Column(Text, nullable=True, comment='图片内容描述（AI识别结果）')
    context = Column(Text, nullable=True, comment='上下文信息')
    poem_title = Column(String(200), nullable=True, comment='诗词标题')
    poem_content = Column(Text, nullable=True, comment='诗词内容')
    author = Column(String(100), nullable=True, comment='作者')
    dynasty = Column(String(50), nullable=True, comment='朝代')
    appreciation = Column(Text, nullable=True, comment='赏析内容')
    model_name = Column(String(100), nullable=True, comment='使用的AI模型')
    model_version = Column(String(50), nullable=True, comment='模型版本')
    status = Column(Integer, default=0, comment='状态（1:成功 0:失败）')
    error_message = Column(Text, nullable=True, comment='错误信息（如有）')
    created_at = Column(DateTime, default=func.now(), comment='创建时间')
    updated_at = Column(DateTime, default=func.now(), onupdate=func.now(), comment='更新时间')
    
    def __repr__(self):
        return f"<Recommendation(id={self.id}, user_id={self.user_id}, status={self.status})>"
    
    def to_dict(self):
        """转换为字典"""
        return {
            'id': self.id,
            'user_id': self.user_id,
            'positive_prompt': self.positive_prompt,
            'negative_prompt': self.negative_prompt,
            'image_path': self.image_path,
            'image_description': self.image_description,
            'context': self.context,
            'poem_title': self.poem_title,
            'poem_content': self.poem_content,
            'author': self.author,
            'dynasty': self.dynasty,
            'appreciation': self.appreciation,
            'model_name': self.model_name,
            'model_version': self.model_version,
            'status': self.status,
            'error_message': self.error_message,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'updated_at': self.updated_at.isoformat() if self.updated_at else None,
        }

