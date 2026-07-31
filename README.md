# zy系统

qyfanshen.com

## 部署要求
- PHP >= 8.0 / Node.js >= 18
- MySQL 5.7+ 或 SQLite
- Nginx / Apache

## 安装
```bash
git clone https://github.com/qyfanshen/zy.qyfanshen.git
cp .env.example .env    # 编辑 .env 填入真实配置
# npm install           # 如有 Node 依赖
# composer install      # 如有 PHP 依赖
```

## 安全须知
- 切勿提交 .env、数据库文件、密钥
- 生产环境必须开启 HTTPS
- 确保 storage 与 uploads/products 目录可写
- 微信支付需要启用 curl、openssl、mbstring、pdo_mysql 扩展

## 许可证
[MIT](LICENSE)
