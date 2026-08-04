# TODO

> 项目路线图与待办清单。完成一项后请将 `[ ]` 改为 `[x]`。

## 当前迭代

- [ ] 用真实截图替换 `screenshots/preview.svg` 占位图
- [ ] 校对 README / AGENTS / docs 三份文档与代码实际状态一致
- [ ] CI 流水线跑通（`.github/workflows/ci.yml`）

## PHP 站通用
- [ ] 完善 `api/db.php` 的数据库连接池与错误处理
- [ ] 增加 Composer 依赖管理（`composer.json` + `composer.lock`）
- [ ] 接入 PSR-12 代码规范与 PHP-CS-Fixer
- [ ] 接入 PHPUnit 单元测试
- [ ] 关键接口补 OpenAPI 文档
- [ ] 接入 Sentry / 业务日志系统

## 安全 / 合规

- [ ] 复查 `.gitignore` 是否覆盖 `*.bak.*`、`node_modules/`、`.next/`、`.env*`
- [ ] 复查 Nginx / Apache 配置文件中的安全头（CSP / X-Frame-Options / Referrer-Policy）
- [ ] 私钥、数据库连接串不出现在任何提交文件中

## 后续迭代

- [ ] 增加多语言（英文 / 繁体）支持
- [ ] 接入 Lighthouse / PageSpeed 自动监测
- [ ] 增加 LICENSE 之外的 NOTICE / 第三方依赖声明
