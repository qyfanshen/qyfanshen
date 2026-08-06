# 梵燊科技 · AI 产品服务平台

> 让天下没有不会用 AI 的企业——以 AI 赋能万千企业。

![预览](screenshots/preview.png)
<p align="center">
  <a href="https://github.com/qyfanshen/qyfanshen"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="许可证"></a>
  <a href="https://github.com/qyfanshen/qyfanshen/actions"><img src="https://img.shields.io/github/actions/workflow/status/qyfanshen/qyfanshen/ci.yml?branch=master&label=CI" alt="CI"></a>
  <a href="https://img.shields.io/github/languages/code-size/qyfanshen/qyfanshen"><img src="https://img.shields.io/github/languages/code-size/qyfanshen/qyfanshen" alt="代码体积"></a>
  <a href="https://github.com/qyfanshen/qyfanshen/issues"><img src="https://img.shields.io/github/issues/qyfanshen/qyfanshen" alt="Issues"></a>
  <a href="https://github.com/qyfanshen/qyfanshen/stargazers"><img src="https://img.shields.io/github/stars/qyfanshen/qyfanshen?style=social" alt="Stars"></a>
</p>

---

**梵燊科技** 是企业 AI 产品服务平台——'让天下没有不会用 AI 的企业'，以 AI 赋能企业低成本数字化、智能化转型。

[English](README.md) | [中文](README.zh.md)

## 核心使用场景

- **🤖 企业 AI 赋能** — 为企业提供开箱即用的 AI 产品服务平台。
- **🏢 集团门户** — 官网首页：产品、关于我们、商业模式与联系方式一应俱全。
- **📣 线索获取** — 「探索解决方案 / 立即试用」按钮引导访客转化为咨询。

## 特色功能

### 核心功能
- 🎯 **AI 产品服务平台**：一站式企业 AI 化赋能入口
- 🌐 **多页面导航**：首页 · AI 产品服务平台 · 关于我们 · 商业模式 · 隐私政策 · 联系我们
- 🚀 **AI 赋能承诺**：「让天下没有不会用 AI 的企业」
- 💡 **企业愿景**：企业 AI 化、人人皆可 AI 用；高智化、自动化、提员化
- 📊 **实力数据**：6 大领域 · 100+ 集成 · 8 大核心 · 10万+ 触达
- 📬 **行动入口**：Hero 区直接呈现「探索解决方案」「立即试用」按钮
- 📄 **合规完备**：隐私政策、服务条款、联系方式一应俱全

### 技术特性
- 现代化技术栈：HTML5 · CSS3 · 原生 JavaScript · Nginx/Apache
- 隐私与安全：HTTPS 强制、安全响应头、敏感文件隔离
- SEO 就绪：`sitemap.xml`、`robots.txt`、语义化标签
- 深色主题 + 渐变光圈 / 玻璃拟态 hero 设计
- 轻量纯静态，可部署于任意静态托管
- 许可证：MIT

## 截图预览

通过本地服务 + 无头浏览器渲染的真实截图：

### 首页预览

![首页预览](screenshots/preview.png)

### 隐私政策页

![隐私政策页](screenshots/page-privacy.png)

---

## 快速部署

三行命令即可启动：

```bash
git clone https://gitee.com/qyfanshen/qyfanshen.git
cd qyfanshen.com
python3 -m http.server 8080   # open http://localhost:8080
```

> 完整步骤（Nginx、环境变量、生产部署）见 [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)。
## 使用指南

1. 配置环境（PHP 站填写 `.env`，静态站配置部署参数）
2. PHP 站：导入数据库结构，修改 `config/app.php` 或 `api/db.php`
3. 静态站：直接将目录部署到 Nginx / CDN
4. 访问首页，确认落地页正常渲染
5. （如适用）登录 `/admin/` 检查数据

## 项目结构

```
qyfanshen.com/
├── README.md            # 英文说明
├── README.zh.md         # 本文件（中文说明）
├── AGENTS.md            # AI 协作说明
├── TODO.md              # 路线图与待办
├── CHANGELOG.md         # 版本历史
├── CONTRIBUTING.md      # 贡献指南
├── LICENSE              # MIT 许可证
├── index.html           # 入口页
├── privacy.html         # 隐私政策页
├── screenshots/         # 视觉素材
│   ├── README.md
│   └── preview.png
├── docs/                # 补充文档
│   ├── QUICKSTART.md
│   ├── ARCHITECTURE.md
│   ├── DEPLOYMENT.md
│   ├── API.md
└── .github/             # Issue 模板与 CI 工作流
    ├── ISSUE_TEMPLATE/
    ├── workflows/ci.yml
    └── PULL_REQUEST_TEMPLATE.md
```

## 架构说明

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

## 开发指南

- 按项目约定进行 lint / format
- 提交前运行 `git status` 自检
- 遵守 `.env.example` 中的安全约定

## API 参考

完整接口列表见 [`docs/API.md`](docs/API.md)。当前模块：

- `ai_chat`
- `payment_create`
- `payment_notify`
- `payment_status`
- `user_auth`
- `user`
- `contact`
- `wechat_pay`

## 部署

## 生产部署

### 1. Nginx 站点配置（推荐）

```nginx
server {
    listen 80;
    server_name qyfanshen.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name qyfanshen.com;

    ssl_certificate     /etc/nginx/ssl/qyfanshen.crt;
    ssl_certificate_key /etc/nginx/ssl/qyfanshen.key;

    root /var/www/qyfanshen.com;
    index index.html index.php;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;

    # 静态资源缓存
    location ~* \.(css|js|jpg|jpeg|png|gif|svg|woff2?)$ {
        expires 7d;
        add_header Cache-Control "public, max-age=604800, immutable";
    }

    # PHP 处理
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    # 禁止访问敏感文件
    location ~ /(\.env|\.user\.ini|\.htaccess|\.bak\.|composer\.json|composer\.lock|package\.json|\.git) {
        deny all;
        return 404;
    }
}
```

### 2. Apache `.htaccess`

```apache
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

<FilesMatch "\.(env|user\.ini|htaccess|bak\.|gitignore)$">
    Require all denied
</FilesMatch>
```

### 4. 部署后检查清单

- [ ] HTTPS 已生效（浏览器锁图标）
- [ ] `https://qyfanshen.com/.env` 返回 404
- [ ] 安全响应头可在 https://securityheaders.com 验证为 A 或 A+
- [ ] sitemap.xml 可访问
- [ ] robots.txt 可访问
- [ ] 隐私页 `privacy.html` 可访问

## 行为准则

请阅读我们的[行为准则](CODE_OF_CONDUCT.md)——友善待人，互相尊重。

## 安全

发现安全漏洞？💖 非常感谢你负责任地披露！

在报告之前，请先花一分钟看看 [安全政策](SECURITY.md)，这样能帮助我们更快响应，也避免遗漏重要信息。

## 贡献

我们非常欢迎你的贡献！💖

如果你愿意参与，可以先看看 [CONTRIBUTING.md](CONTRIBUTING.md)，并使用 [Issue 模板](.github/ISSUE_TEMPLATE/) 与 [PR 模板](.github/PULL_REQUEST_TEMPLATE.md)，这样我们协作起来会更顺畅。🙏

## 许可证

本项目基于 **MIT 许可证** 开源。

**允许：**
- ✅ 商业使用
- ✅ 修改
- ✅ 分发
- ✅ 再授权
- ✅ 私人使用

**条件：**
- 📄 在软件副本中必须包含原始版权声明和许可证声明

**完整条款：** 详见 [LICENSE](LICENSE) 文件。

## 致谢

- 仓库样式参考 [x007xyz/flycut-caption](https://github.com/x007xyz/flycut-caption)
- 由梵燊科技团队构建

## 支持

- 问题反馈：请使用仓库内的 issue 模板
- 站点域名：https://qyfanshen.com

## 联系我们

扫码添加企业微信，获取技术支持、商务咨询或合作洽谈：

![企业微信二维码](screenshots/wechat-qrcode.png)

其他联系方式：
- 集团主站：<https://qyfanshen.com>
- 问题反馈：请使用仓库内的 issue 模板

---

**版权所有 © 2026 [qyfanshen](https://github.com/qyfanshen)。保留所有权利。**

基于 [MIT 许可证](LICENSE) 开源。
