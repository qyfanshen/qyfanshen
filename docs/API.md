# API Reference

> 梵燊集团主站 的接口文档。模块：ai_chat, payment_create, payment_notify, payment_status, user_auth, user, contact, wechat_pay

## 通用约定

- 基础路径：`/api/`
- 请求/响应：JSON
- 鉴权：除登录接口外，所有接口需要带 `Authorization: Bearer <token>` 或 Cookie 会话
- 限流：默认每 IP 每分钟 60 次（可由 `api/rate_limit.php` 或中间件调整）
- 错误格式：
  ```json
  { "code": 400, "message": "Invalid parameter", "data": null }
  ```


## AI 对话

```
POST /api/ai_chat.php
```

请求：
```json
{
  "session_id": "string",
  "messages": [{"role": "user", "content": "..."}],
  "model": "gpt-4.1-mini"
}
```

响应：
```json
{
  "code": 0,
  "message": "ok",
  "data": {
    "reply": "...",
    "usage": {"prompt_tokens": 0, "completion_tokens": 0, "total_tokens": 0}
  }
}
```

## 支付

```
POST /api/payment_create.php   # 创建订单
POST /api/payment_notify.php   # 异步回调（WeChat Pay）
GET  /api/payment_status.php   # 查询订单状态
POST /api/wechat_pay.php       # 微信支付统一下单
```

## 用户

```
POST /api/user_auth.php        # 登录 / 注册
GET  /api/user.php             # 获取当前用户信息
POST /api/user.php             # 更新资料
```

## 联系

```
POST /api/contact.php          # 留言提交
```
