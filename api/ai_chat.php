<?php
require_once '../../includes/csrf.php';
declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/rate_limit.php';

// 流式输出 — 关闭所有缓冲区
if (ob_get_level()) ob_end_clean();
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: https://qyfanshen.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

checkRateLimit('ai_chat', 20, 60);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "data: " . json_encode(['error' => '仅支持 POST 请求'], JSON_UNESCAPED_UNICODE) . "\n\n";
    exit;
}

$apiKey = envValue('AI_API_KEY', '');
$apiUrl = envValue('AI_API_URL', 'https://api.openai.com/v1/chat/completions');
$model = envValue('AI_MODEL', 'gpt-4.1-mini');
if ($apiKey === '') {
    http_response_code(503);
    echo "data: " . json_encode(['error' => 'AI 服务尚未配置'], JSON_UNESCAPED_UNICODE) . "\n\n";
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) $payload = [];

$userMessage = trim((string)($payload['message'] ?? ''));
if ($userMessage === '') {
    http_response_code(422);
    echo "data: " . json_encode(['error' => '消息不能为空'], JSON_UNESCAPED_UNICODE) . "\n\n";
    exit;
}
if (mb_strlen($userMessage, 'UTF-8') > 2000) {
    http_response_code(422);
    echo "data: " . json_encode(['error' => '消息内容过长'], JSON_UNESCAPED_UNICODE) . "\n\n";
    exit;
}

$history = $payload['history'] ?? [];
if (!is_array($history)) $history = [];

$systemPrompt = <<<'PROMPT'
你是"梵燊科技"的 AI 客服助手，专门为企业客户提供咨询服务。

【公司背景】
梵燊科技是 AI 产业服务平台，主营 AI 企业操作系统（8 大核心模块：AI客服、AI营销、AI短剧/数字人、AI自动化、AI数据分析、AI知识库、AI培训），以及 AI 产业升级研究院。专注于为全国商协会及其会员企业提供 AI 解决方案。

【核心业务】
- AI 企业操作系统：一站式企业智能化平台
- AI 产业升级研究院：面向商协会的培训/咨询/项目/软件/硬件服务
- AI 产业学院：产教融合，培养 5 大 AI 核心人才
- 覆盖 6 大行业：制造业、餐饮、建材、医药、物流、跨境电商
- OEM 模式：产能工厂 + 1000 家独资公司 + 10000 名创业者
- 使命：让天下没有不会用 AI 的企业
- 愿景：成为中国商协会 AI 服务领域绝对领先企业
- 价值观：开放分享、拥抱变化

【联系方式】
- 地址：广东省清远市盈链数字经济产业园
- 电话：18924419777
- 邮箱：fanshen8888@qq.com
- 工作时间：周一至周五 09:00-18:00

【回复要求】
1. 语气亲切专业，像一个资深的 AI 顾问
2. 回答简洁有力，先给结论再给依据
3. 如果用户问价格或报价，引导填写咨询表单获取专属方案
4. 如果用户想了解详情，引导查看产品服务或成功案例板块
5. 不要编造具体价格数字、不存在的人物和案例
6. 使用中文回复，可适当使用 emoji
7. 不要使用 Markdown 格式（不要用 ** 加粗），直接纯文本回复
PROMPT;

$messages = [['role' => 'system', 'content' => $systemPrompt]];

$historySlice = array_slice($history, -20);
foreach ($historySlice as $h) {
    $role = ($h['role'] === 'user' || $h['role'] === 'assistant') ? $h['role'] : 'user';
    $content = trim((string)($h['content'] ?? ''));
    if ($content !== '') $messages[] = ['role' => $role, 'content' => mb_substr($content, 0, 2000, 'UTF-8')];
}

$messages[] = ['role' => 'user', 'content' => $userMessage];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $apiUrl,
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS     => json_encode([
        'model'       => $model,
        'messages'    => $messages,
        'temperature' => 0.7,
        'max_tokens'  => 600,
        'stream'      => true,
    ], JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_WRITEFUNCTION  => function($ch, $data) {
        echo $data;
        if (ob_get_level()) ob_flush();
        flush();
        return strlen($data);
    },
]);

curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    error_log('AI API request failed: ' . $err);
    echo "data: " . json_encode(['error' => 'AI 服务暂时不可用'], JSON_UNESCAPED_UNICODE) . "\n\n";
}
