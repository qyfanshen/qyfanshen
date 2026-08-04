# Architecture

## 概述

- **项目**：梵燊集团主站
- **类型**：PHP 全栈
- **技术栈**：PHP 8+ · MySQL 5.7+ / SQLite · jQuery · Nginx/Apache

## 模块划分


- **API 层**：`api/*.php` 提供 RESTful 接口，含 CSRF、限流、统一错误处理。
- **后台管理**：`admin/` 提供登录、消息、支付、商品等管理能力。





## 数据流

```
[Browser]
   │
   ├─── 静态资源（Nginx / CDN）
   │
   ├─── /api/*.php ──► [MySQL]


   │
   └─── /admin/*（如适用）
```

## 安全设计

- HTTPS 强制（301 跳转）
- 安全响应头：CSP / X-Frame-Options / Referrer-Policy / Permissions-Policy
- 敏感文件（`.env`、`*.bak.*`、`storage/`、`.user.ini`）通过 `.gitignore` + Nginx deny 双重保护
- 接口限流（PHP 站 `api/rate_limit.php`）
- CSRF token 校验（PHP 站 `includes/csrf.php`）
