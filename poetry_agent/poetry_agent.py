#!/usr/bin/env python3
"""
AI诗词推荐Agent主程序
"""
import argparse
import sys
import logging
from pathlib import Path
from typing import Optional

from utils.logger import setup_logger
from utils.ai_client import AIClientFactory
from utils.image_processor import ImageProcessor
from models.database import get_db, init_db
from models.recommendation import Recommendation
from config.settings import Settings

logger = setup_logger()


class PoetryAgent:
    """诗词推荐Agent"""
    
    def __init__(self, config_file: Optional[str] = None):
        """
        初始化Agent
        
        Args:
            config_file: 配置文件路径
        """
        self.settings = Settings(config_file)
        self.image_processor = ImageProcessor()
    
    def run(
        self,
        positive_prompt: Optional[str] = None,
        negative_prompt: Optional[str] = None,
        image_path: Optional[str] = None,
        user_id: Optional[int] = None,
        context: Optional[str] = None,
        model: str = None,
        count: int = 1,
        type: str = '推荐',
        verbose: bool = False
    ) -> int:
        """
        执行推荐任务
        
        Returns:
            状态码：0-成功，1-参数错误，2-API调用失败，3-数据库操作失败，4-其他错误
        """
        try:
            # 参数验证
            if not positive_prompt and not image_path:
                logger.error("错误：至少需要提供正向提示词(--prompt)或图片(--image)")
                return 1
            
            # 设置日志级别
            if verbose:
                logger.setLevel(logging.DEBUG)
            
            logger.info("开始执行诗词推荐任务...")
            logger.debug(f"参数: prompt={positive_prompt}, negative_prompt={negative_prompt}, "
                        f"image={image_path}, user_id={user_id}, model={model}, count={count}")
            
            # 处理图片
            saved_image_path = None
            image_description = None
            
            if image_path:
                # 验证图片
                is_valid, error = self.image_processor.validate_image(image_path)
                if not is_valid:
                    logger.error(f"图片验证失败: {error}")
                    return 1
                
                # 保存图片
                saved_image_path = self.image_processor.save_image(image_path, user_id)
                logger.info(f"图片已保存: {saved_image_path}")
            
            # 创建AI客户端
            model_name = model or self.settings.default_model
            logger.info(f"使用AI模型: {model_name}")
            
            try:
                ai_client = AIClientFactory.create_client(model_name)
            except Exception as e:
                logger.error(f"创建AI客户端失败: {e}")
                return 2
            
            # 调用AI生成推荐
            try:
                logger.info("正在调用AI生成推荐...")
                result = ai_client.generate_poetry_recommendation(
                    positive_prompt=positive_prompt,
                    negative_prompt=negative_prompt,
                    image_path=image_path,
                    image_description=image_description,
                    context=context,
                    count=count
                )
                logger.info("AI推荐生成成功")
            except Exception as e:
                logger.error(f"AI API调用失败: {e}")
                # 保存失败记录到数据库
                self._save_failed_record(
                    user_id=user_id,
                    positive_prompt=positive_prompt,
                    negative_prompt=negative_prompt,
                    image_path=saved_image_path,
                    error_message=str(e),
                    model_name=model_name
                )
                return 2
            
            # 保存结果到数据库
            try:
                if count == 1:
                    # 单个推荐
                    record_id = self._save_recommendation(
                        user_id=user_id,
                        positive_prompt=positive_prompt,
                        negative_prompt=negative_prompt,
                        image_path=saved_image_path,
                        image_description=result.get('image_description'),
                        context=context,
                        poem_title=result.get('poem_title'),
                        poem_content=result.get('poem_content'),
                        author=result.get('author'),
                        dynasty=result.get('dynasty'),
                        appreciation=result.get('appreciation'),
                        model_name=model_name,
                        status=1
                    )
                    logger.info(f"推荐记录已保存，ID: {record_id}")
                    
                    # 输出结果
                    if verbose:
                        print("\n" + "="*50)
                        print("推荐结果:")
                        print("="*50)
                        print(f"标题: {result.get('poem_title')}")
                        print(f"作者: {result.get('author')} ({result.get('dynasty')})")
                        print(f"\n内容:\n{result.get('poem_content')}")
                        print(f"\n赏析:\n{result.get('appreciation')}")
                        print("="*50)
                    else:
                        print(f"成功！推荐记录ID: {record_id}")
                        print(f"标题: {result.get('poem_title')} - {result.get('author')} ({result.get('dynasty')})")
                else:
                    # 多个推荐
                    poems = result.get('poems', [])
                    record_ids = []
                    for poem in poems:
                        record_id = self._save_recommendation(
                            user_id=user_id,
                            positive_prompt=positive_prompt,
                            negative_prompt=negative_prompt,
                            image_path=saved_image_path,
                            image_description=result.get('image_description'),
                            context=context,
                            poem_title=poem.get('title'),
                            poem_content=poem.get('content'),
                            author=poem.get('author'),
                            dynasty=poem.get('dynasty'),
                            appreciation=poem.get('appreciation'),
                            model_name=model_name,
                            status=1
                        )
                        record_ids.append(record_id)
                    logger.info(f"已保存 {len(record_ids)} 条推荐记录，IDs: {record_ids}")
                    print(f"成功！已保存 {len(record_ids)} 条推荐记录")
                
                return 0
            except Exception as e:
                logger.error(f"数据库操作失败: {e}")
                return 3
        
        except Exception as e:
            logger.error(f"执行失败: {e}", exc_info=True)
            return 4
    
    def _save_recommendation(
        self,
        user_id: Optional[int],
        positive_prompt: Optional[str],
        negative_prompt: Optional[str],
        image_path: Optional[str],
        image_description: Optional[str],
        context: Optional[str],
        poem_title: Optional[str],
        poem_content: Optional[str],
        author: Optional[str],
        dynasty: Optional[str],
        appreciation: Optional[str],
        model_name: str,
        status: int
    ) -> int:
        """保存推荐记录到数据库"""
        with get_db() as db:
            recommendation = Recommendation(
                user_id=user_id,
                positive_prompt=positive_prompt,
                negative_prompt=negative_prompt,
                image_path=image_path,
                image_description=image_description,
                context=context,
                poem_title=poem_title,
                poem_content=poem_content,
                author=author,
                dynasty=dynasty,
                appreciation=appreciation,
                model_name=model_name,
                status=status
            )
            db.add(recommendation)
            db.flush()
            record_id = recommendation.id
            return record_id
    
    def _save_failed_record(
        self,
        user_id: Optional[int],
        positive_prompt: Optional[str],
        negative_prompt: Optional[str],
        image_path: Optional[str],
        error_message: str,
        model_name: str
    ):
        """保存失败记录到数据库"""
        try:
            with get_db() as db:
                recommendation = Recommendation(
                    user_id=user_id,
                    positive_prompt=positive_prompt,
                    negative_prompt=negative_prompt,
                    image_path=image_path,
                    model_name=model_name,
                    status=0,
                    error_message=error_message
                )
                db.add(recommendation)
        except Exception as e:
            logger.error(f"保存失败记录时出错: {e}")


def main():
    """主函数"""
    parser = argparse.ArgumentParser(
        description='AI诗词推荐Agent - 根据提示词或图片推荐古诗词',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
使用示例:
  # 基本使用 - 仅正向提示词
  python poetry_agent.py --prompt "推荐一首关于春天的诗"
  
  # 正向提示词 + 负向提示词
  python poetry_agent.py --prompt "推荐一首关于春天的诗" --negative-prompt "不要包含悲伤情绪"
  
  # 仅图片输入
  python poetry_agent.py --image /path/to/image.jpg
  
  # 图片 + 正向提示词
  python poetry_agent.py --image /path/to/image.jpg --prompt "推荐一首与图片意境相符的诗词"
  
  # 完整参数示例
  python poetry_agent.py --user-id 1001 --prompt "推荐一首关于春天的诗" \\
      --negative-prompt "不要包含悲伤情绪" --image /path/to/image.jpg \\
      --context "用户喜欢唐诗" --model "gpt-4" --count 1 --verbose
        """
    )
    
    parser.add_argument(
        '-p', '--prompt',
        type=str,
        help='正向提示词（必填，至少提供正向提示词或图片之一）'
    )
    
    parser.add_argument(
        '-np', '--negative-prompt',
        type=str,
        help='负向提示词（可选）'
    )
    
    parser.add_argument(
        '-i', '--image',
        type=str,
        help='图片文件路径（可选，至少提供正向提示词或图片之一）'
    )
    
    parser.add_argument(
        '-u', '--user-id',
        type=int,
        help='用户ID（可选）'
    )
    
    parser.add_argument(
        '-c', '--context',
        type=str,
        help='上下文信息（可选）'
    )
    
    parser.add_argument(
        '-m', '--model',
        type=str,
        help='指定使用的AI模型（可选，有默认值）'
    )
    
    parser.add_argument(
        '-n', '--count',
        type=int,
        default=1,
        help='推荐诗词数量（可选，默认1首）'
    )
    
    parser.add_argument(
        '-t', '--type',
        type=str,
        default='推荐',
        choices=['推荐', '赏析', '创作'],
        help='推荐类型（可选，默认：推荐）'
    )
    
    parser.add_argument(
        '-f', '--config',
        type=str,
        help='配置文件路径（可选）'
    )
    
    parser.add_argument(
        '-v', '--verbose',
        action='store_true',
        help='详细输出模式（可选）'
    )
    
    args = parser.parse_args()
    
    # 创建Agent实例
    agent = PoetryAgent(config_file=args.config)
    
    # 执行推荐任务
    exit_code = agent.run(
        positive_prompt=args.prompt,
        negative_prompt=args.negative_prompt,
        image_path=args.image,
        user_id=args.user_id,
        context=args.context,
        model=args.model,
        count=args.count,
        type=args.type,
        verbose=args.verbose
    )
    
    sys.exit(exit_code)


if __name__ == '__main__':
    import logging
    main()

