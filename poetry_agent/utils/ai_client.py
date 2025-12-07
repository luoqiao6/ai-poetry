"""
AI大模型客户端模块
"""
import json
import time
import logging
from typing import Optional, Dict, Any, List
from abc import ABC, abstractmethod

from config.settings import settings

logger = logging.getLogger(__name__)


class AIClient(ABC):
    """AI客户端基类"""
    
    def __init__(self, api_key: Optional[str] = None, base_url: Optional[str] = None):
        self.api_key = api_key
        self.base_url = base_url
        self.timeout = settings.api_timeout
        self.retry_times = settings.api_retry_times
    
    @abstractmethod
    def generate_poetry_recommendation(
        self,
        positive_prompt: Optional[str] = None,
        negative_prompt: Optional[str] = None,
        image_path: Optional[str] = None,
        image_description: Optional[str] = None,
        context: Optional[str] = None,
        count: int = 1
    ) -> Dict[str, Any]:
        """
        生成诗词推荐
        
        Args:
            positive_prompt: 正向提示词
            negative_prompt: 负向提示词
            image_path: 图片路径
            image_description: 图片描述
            context: 上下文信息
            count: 推荐数量
            
        Returns:
            推荐结果字典
        """
        pass
    
    def _retry_request(self, func, *args, **kwargs):
        """重试请求"""
        last_error = None
        for attempt in range(self.retry_times):
            try:
                return func(*args, **kwargs)
            except Exception as e:
                last_error = e
                logger.warning(f"请求失败 (尝试 {attempt + 1}/{self.retry_times}): {e}")
                if attempt < self.retry_times - 1:
                    time.sleep(2 ** attempt)  # 指数退避
        raise last_error


class OpenAIClient(AIClient):
    """OpenAI客户端"""
    
    def __init__(self, api_key: Optional[str] = None, base_url: Optional[str] = None):
        super().__init__(api_key or settings.openai_api_key, base_url or settings.openai_base_url)
        try:
            import openai
            self.client = openai.OpenAI(api_key=self.api_key, base_url=self.base_url)
        except ImportError:
            raise ImportError("请安装openai库: pip install openai")
    
    def generate_poetry_recommendation(
        self,
        positive_prompt: Optional[str] = None,
        negative_prompt: Optional[str] = None,
        image_path: Optional[str] = None,
        image_description: Optional[str] = None,
        context: Optional[str] = None,
        count: int = 1
    ) -> Dict[str, Any]:
        """生成诗词推荐"""
        from utils.image_processor import ImageProcessor
        
        # 构建提示词
        system_prompt = """你是一个专业的诗词推荐助手。请根据用户的需求推荐合适的古诗词，并提供详细的赏析。

请按照以下JSON格式返回结果：
{
    "poems": [
        {
            "title": "诗词标题",
            "content": "诗词内容（完整）",
            "author": "作者",
            "dynasty": "朝代",
            "appreciation": "赏析内容"
        }
    ]
}"""
        
        user_prompt_parts = []
        
        # 如果有图片但没有描述，先识别图片
        if image_path and not image_description:
            image_description = self._describe_image(image_path)
        
        # 添加图片描述
        if image_description:
            user_prompt_parts.append(f"图片描述：{image_description}")
        
        # 添加正向提示词
        if positive_prompt:
            user_prompt_parts.append(f"推荐要求：{positive_prompt}")
        
        # 添加负向提示词
        if negative_prompt:
            user_prompt_parts.append(f"排除要求：{negative_prompt}")
        
        # 添加上下文
        if context:
            user_prompt_parts.append(f"上下文信息：{context}")
        
        user_prompt = "\n".join(user_prompt_parts)
        if not user_prompt and not image_path:
            raise ValueError("至少需要提供正向提示词或图片")
        
        # 构建消息
        messages = [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt if user_prompt else "请根据图片推荐相关的古诗词"}
        ]
        
        # 如果有图片，添加图片到消息中
        if image_path:
            from utils.image_processor import ImageProcessor
            processor = ImageProcessor()
            base64_image = processor.encode_image_to_base64(image_path)
            messages[-1]["content"] = [
                {"type": "text", "text": user_prompt if user_prompt else "请根据图片推荐相关的古诗词"},
                {
                    "type": "image_url",
                    "image_url": {
                        "url": f"data:image/jpeg;base64,{base64_image}"
                    }
                }
            ]
        
        # 调用API
        def _call_api():
            response = self.client.chat.completions.create(
                model="gpt-4-vision-preview" if image_path else "gpt-4",
                messages=messages,
                temperature=0.7,
                max_tokens=2000
            )
            return response.choices[0].message.content
        
        content = self._retry_request(_call_api)
        
        # 解析响应
        try:
            # 尝试提取JSON
            import re
            json_match = re.search(r'\{.*\}', content, re.DOTALL)
            if json_match:
                result = json.loads(json_match.group())
            else:
                # 如果无法解析JSON，尝试手动解析
                result = self._parse_text_response(content)
        except Exception as e:
            logger.warning(f"JSON解析失败，使用文本解析: {e}")
            result = self._parse_text_response(content)
        
        # 返回结果
        poems = result.get('poems', [])
        if not poems:
            raise ValueError("AI返回结果中没有找到诗词")
        
        # 只返回第一个（如果count=1）或全部
        if count == 1:
            poem = poems[0]
            return {
                'poem_title': poem.get('title', ''),
                'poem_content': poem.get('content', ''),
                'author': poem.get('author', ''),
                'dynasty': poem.get('dynasty', ''),
                'appreciation': poem.get('appreciation', ''),
                'image_description': image_description
            }
        else:
            return {
                'poems': poems[:count],
                'image_description': image_description
            }
    
    def _describe_image(self, image_path: str) -> str:
        """描述图片内容"""
        from utils.image_processor import ImageProcessor
        processor = ImageProcessor()
        base64_image = processor.encode_image_to_base64(image_path)
        
        messages = [
            {
                "role": "user",
                "content": [
                    {"type": "text", "text": "请详细描述这张图片的内容、意境和情感，用于推荐相关的古诗词。"},
                    {
                        "type": "image_url",
                        "image_url": {
                            "url": f"data:image/jpeg;base64,{base64_image}"
                        }
                    }
                ]
            }
        ]
        
        response = self.client.chat.completions.create(
            model="gpt-4-vision-preview",
            messages=messages,
            max_tokens=500
        )
        
        return response.choices[0].message.content
    
    def _parse_text_response(self, content: str) -> Dict[str, Any]:
        """解析文本响应"""
        # 简单的文本解析逻辑
        # 这里可以根据实际返回格式进行优化
        return {
            'poems': [{
                'title': '未知',
                'content': content,
                'author': '未知',
                'dynasty': '未知',
                'appreciation': ''
            }]
        }


class AIClientFactory:
    """AI客户端工厂"""
    
    @staticmethod
    def create_client(model_name: str) -> AIClient:
        """
        创建AI客户端
        
        Args:
            model_name: 模型名称（如 'gpt-4', 'gpt-3.5-turbo' 等）
            
        Returns:
            AI客户端实例
        """
        if model_name.startswith('gpt'):
            return OpenAIClient()
        else:
            raise ValueError(f"不支持的模型: {model_name}")

