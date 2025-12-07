<?php

namespace PoetryAgent\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PoetryAgent\Config\Settings;
use PoetryAgent\Models\Database;
use PoetryAgent\Models\Recommendation;
use PoetryAgent\Utils\Logger;
use PoetryAgent\Utils\ImageProcessor;
use PoetryAgent\Utils\AIClientFactory;

/**
 * AI诗词推荐Agent命令
 */
class PoetryAgentCommand extends Command
{
    protected static $defaultName = 'poetry:recommend';
    protected static $defaultDescription = 'AI诗词推荐Agent - 根据提示词或图片推荐古诗词';

    private Settings $settings;
    private $logger;
    private ImageProcessor $imageProcessor;

    public function __construct(?Settings $settings = null)
    {
        parent::__construct();
        $this->settings = $settings ?? new Settings();
        $this->logger = Logger::getInstance($this->settings);
        $this->imageProcessor = new ImageProcessor($this->settings);
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->setHelp($this->getHelpText())
            ->addOption('prompt', 'p', InputOption::VALUE_REQUIRED, '正向提示词（必填，至少提供正向提示词或图片之一）')
            ->addOption('negative-prompt', 'np', InputOption::VALUE_OPTIONAL, '负向提示词（可选）')
            ->addOption('image', 'i', InputOption::VALUE_OPTIONAL, '图片文件路径（可选，至少提供正向提示词或图片之一）')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, '用户ID（可选）')
            ->addOption('context', 'c', InputOption::VALUE_OPTIONAL, '上下文信息（可选）')
            ->addOption('model', 'm', InputOption::VALUE_OPTIONAL, '指定使用的AI模型（可选，有默认值）')
            ->addOption('count', 'n', InputOption::VALUE_OPTIONAL, '推荐诗词数量（可选，默认1首）', 1)
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, '推荐类型（推荐/赏析/创作，可选）', '推荐')
            ->addOption('config', 'f', InputOption::VALUE_OPTIONAL, '配置文件路径（可选）')
            ->addOption('verbose', 'v', InputOption::VALUE_NONE, '详细输出模式（可选）');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // 如果指定了配置文件，重新加载配置
            $configFile = $input->getOption('config');
            if ($configFile) {
                $this->settings = new Settings($configFile);
                $this->logger = Logger::getInstance($this->settings);
                $this->imageProcessor = new ImageProcessor($this->settings);
            }

            // 参数验证
            $positivePrompt = $input->getOption('prompt');
            $imagePath = $input->getOption('image');
            
            if (!$positivePrompt && !$imagePath) {
                $output->writeln('<error>错误：至少需要提供正向提示词(--prompt)或图片(--image)</error>');
                return 1;
            }

            // 设置详细输出
            $verbose = $input->getOption('verbose');
            if ($verbose) {
                $this->logger->setHandlers([
                    new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG),
                ]);
            }

            $this->logger->info("开始执行诗词推荐任务...");
            $this->logger->debug("参数: prompt={$positivePrompt}, image={$imagePath}");

            // 初始化数据库
            Database::init($this->settings);

            // 处理图片
            $savedImagePath = null;
            $imageDescription = null;

            if ($imagePath) {
                [$isValid, $error] = $this->imageProcessor->validateImage($imagePath);
                if (!$isValid) {
                    $output->writeln("<error>图片验证失败: {$error}</error>");
                    $this->logger->error("图片验证失败: {$error}");
                    return 1;
                }

                $userId = $input->getOption('user-id') ? (int)$input->getOption('user-id') : null;
                $savedImagePath = $this->imageProcessor->saveImage($imagePath, $userId);
                $this->logger->info("图片已保存: {$savedImagePath}");
            }

            // 创建AI客户端
            $modelName = $input->getOption('model') ?: $this->settings->getDefaultModel();
            $this->logger->info("使用AI模型: {$modelName}");

            try {
                $aiClient = AIClientFactory::createClient($modelName, $this->settings);
            } catch (\Exception $e) {
                $output->writeln("<error>创建AI客户端失败: {$e->getMessage()}</error>");
                $this->logger->error("创建AI客户端失败: {$e->getMessage()}");
                return 2;
            }

            // 调用AI生成推荐
            try {
                $this->logger->info("正在调用AI生成推荐...");
                $result = $aiClient->generatePoetryRecommendation(
                    $positivePrompt,
                    $input->getOption('negative-prompt'),
                    $imagePath,
                    $imageDescription,
                    $input->getOption('context'),
                    (int)$input->getOption('count')
                );
                $this->logger->info("AI推荐生成成功");
            } catch (\Exception $e) {
                $output->writeln("<error>AI API调用失败: {$e->getMessage()}</error>");
                $this->logger->error("AI API调用失败: {$e->getMessage()}");
                
                // 保存失败记录
                $this->saveFailedRecord(
                    $input->getOption('user-id') ? (int)$input->getOption('user-id') : null,
                    $positivePrompt,
                    $input->getOption('negative-prompt'),
                    $savedImagePath,
                    $e->getMessage(),
                    $modelName
                );
                return 2;
            }

            // 保存结果到数据库
            try {
                $count = (int)$input->getOption('count');
                if ($count == 1) {
                    $recordId = $this->saveRecommendation(
                        $input->getOption('user-id') ? (int)$input->getOption('user-id') : null,
                        $positivePrompt,
                        $input->getOption('negative-prompt'),
                        $savedImagePath,
                        $result['image_description'] ?? null,
                        $input->getOption('context'),
                        $result['poem_title'] ?? '',
                        $result['poem_content'] ?? '',
                        $result['author'] ?? '',
                        $result['dynasty'] ?? '',
                        $result['appreciation'] ?? '',
                        $modelName,
                        1
                    );
                    $this->logger->info("推荐记录已保存，ID: {$recordId}");

                    // 输出结果
                    if ($verbose) {
                        $output->writeln("\n" . str_repeat('=', 50));
                        $output->writeln("推荐结果:");
                        $output->writeln(str_repeat('=', 50));
                        $output->writeln("标题: " . ($result['poem_title'] ?? ''));
                        $output->writeln("作者: " . ($result['author'] ?? '') . " (" . ($result['dynasty'] ?? '') . ")");
                        $output->writeln("\n内容:\n" . ($result['poem_content'] ?? ''));
                        $output->writeln("\n赏析:\n" . ($result['appreciation'] ?? ''));
                        $output->writeln(str_repeat('=', 50));
                    } else {
                        $output->writeln("<info>成功！推荐记录ID: {$recordId}</info>");
                        $output->writeln("标题: " . ($result['poem_title'] ?? '') . " - " . ($result['author'] ?? '') . " (" . ($result['dynasty'] ?? '') . ")");
                    }
                } else {
                    $poems = $result['poems'] ?? [];
                    $recordIds = [];
                    foreach ($poems as $poem) {
                        $recordId = $this->saveRecommendation(
                            $input->getOption('user-id') ? (int)$input->getOption('user-id') : null,
                            $positivePrompt,
                            $input->getOption('negative-prompt'),
                            $savedImagePath,
                            $result['image_description'] ?? null,
                            $input->getOption('context'),
                            $poem['title'] ?? '',
                            $poem['content'] ?? '',
                            $poem['author'] ?? '',
                            $poem['dynasty'] ?? '',
                            $poem['appreciation'] ?? '',
                            $modelName,
                            1
                        );
                        $recordIds[] = $recordId;
                    }
                    $this->logger->info("已保存 " . count($recordIds) . " 条推荐记录，IDs: " . implode(', ', $recordIds));
                    $output->writeln("<info>成功！已保存 " . count($recordIds) . " 条推荐记录</info>");
                }

                return 0;
            } catch (\Exception $e) {
                $output->writeln("<error>数据库操作失败: {$e->getMessage()}</error>");
                $this->logger->error("数据库操作失败: {$e->getMessage()}");
                return 3;
            }
        } catch (\Exception $e) {
            $output->writeln("<error>执行失败: {$e->getMessage()}</error>");
            $this->logger->error("执行失败: {$e->getMessage()}", ['exception' => $e]);
            return 4;
        }
    }

    private function saveRecommendation(
        ?int $userId,
        ?string $positivePrompt,
        ?string $negativePrompt,
        ?string $imagePath,
        ?string $imageDescription,
        ?string $context,
        string $poemTitle,
        string $poemContent,
        string $author,
        string $dynasty,
        string $appreciation,
        string $modelName,
        int $status
    ): int {
        $recommendation = new Recommendation();
        $recommendation->user_id = $userId;
        $recommendation->positive_prompt = $positivePrompt;
        $recommendation->negative_prompt = $negativePrompt;
        $recommendation->image_path = $imagePath;
        $recommendation->image_description = $imageDescription;
        $recommendation->context = $context;
        $recommendation->poem_title = $poemTitle;
        $recommendation->poem_content = $poemContent;
        $recommendation->author = $author;
        $recommendation->dynasty = $dynasty;
        $recommendation->appreciation = $appreciation;
        $recommendation->model_name = $modelName;
        $recommendation->status = $status;
        $recommendation->save();

        return $recommendation->id;
    }

    private function saveFailedRecord(
        ?int $userId,
        ?string $positivePrompt,
        ?string $negativePrompt,
        ?string $imagePath,
        string $errorMessage,
        string $modelName
    ): void {
        try {
            $recommendation = new Recommendation();
            $recommendation->user_id = $userId;
            $recommendation->positive_prompt = $positivePrompt;
            $recommendation->negative_prompt = $negativePrompt;
            $recommendation->image_path = $imagePath;
            $recommendation->model_name = $modelName;
            $recommendation->status = 0;
            $recommendation->error_message = $errorMessage;
            $recommendation->save();
        } catch (\Exception $e) {
            $this->logger->error("保存失败记录时出错: {$e->getMessage()}");
        }
    }

    private function getHelpText(): string
    {
        return <<<'HELP'
使用示例:

  # 基本使用 - 仅正向提示词
  php bin/poetry-agent --prompt "推荐一首关于春天的诗"
  
  # 正向提示词 + 负向提示词
  php bin/poetry-agent --prompt "推荐一首关于春天的诗" --negative-prompt "不要包含悲伤情绪"
  
  # 仅图片输入
  php bin/poetry-agent --image /path/to/image.jpg
  
  # 图片 + 正向提示词
  php bin/poetry-agent --image /path/to/image.jpg --prompt "推荐一首与图片意境相符的诗词"
  
  # 完整参数示例
  php bin/poetry-agent --user-id 1001 --prompt "推荐一首关于春天的诗" \
      --negative-prompt "不要包含悲伤情绪" --image /path/to/image.jpg \
      --context "用户喜欢唐诗" --model "gpt-4" --count 1 --verbose
HELP;
    }
}

