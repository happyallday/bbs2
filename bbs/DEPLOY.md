# 企业员工论坛 - 生产部署文档

## 系统概述
基于Discuz! X3.5二次开发的企业内部论坛系统，支持：
- 用户名密码登录
- 企业微信OAuth2扫码登录
- 敏感词过滤 + 人工审核
- 白名单用户免审核
- IP访问控制 (办公网 + 本机)

## 环境要求
- Ubuntu 20.04+ / CentOS 7+
- PHP 7.4+
- MySQL 5.7+
- Nginx 1.18+

## 目录结构
```
/var/www/bbs/           # Web根目录
├── index.php           # 主入口
├── wechat_oauth.php    # 企业微信登录
├── config/
│   └── wechat_config.php  # 企业微信配置
├── source/class/
│   └── sensitive_filter.php  # 敏感词过滤
└── upload/             # Discuz源码
```

## 安装步骤

### 1. 环境配置
```bash
# 安装PHP
apt-get update
apt-get install -y php php-mysql php-fpm nginx mysql-server

# 启动服务
service mysql start
service php7.4-fpm start
service nginx start
```

### 2. 数据库配置
```bash
# 创建数据库和用户
mysql -u root -p
CREATE DATABASE bbs DEFAULT CHARACTER SET utf8mb4;
CREATE USER 'bbs'@'localhost' IDENTIFIED BY 'bbs123456';
GRANT ALL PRIVILEGES ON bbs.* TO 'bbs'@'localhost';
FLUSH PRIVILEGES;
```

### 3. 部署代码
```bash
# 复制代码
cp -r upload/* /var/www/bbs/
chown -R www-data:www-data /var/www/bbs
```

### 4. Nginx配置
复制 `nginx.conf` 到 `/etc/nginx/sites-available/bbs`
```bash
ln -sf /etc/nginx/sites-available/bbs /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
service nginx reload
```

### 5. 企业微信配置
编辑 `/var/www/bbs/config/wechat_config.php`:
```php
<?php
return [
    'corp_id' => 'YOUR_CORP_ID',        // 企业ID
    'agent_id' => 'YOUR_AGENT_ID',      // 应用AgentId
    'corp_secret' => 'YOUR_CORP_SECRET', // 应用Secret
    'callback_url' => 'http://your-domain/wechat_oauth.php',
];
```

## IP访问控制
- 本机: 127.0.0.1 ✅ 允许
- 办公网: 172.20.0.0/16 ✅ 允许  
- 其他IP: ❌ 403 Forbidden

## 功能说明

### 登录方式
1. 用户名密码登录
2. 企业微信扫码登录

### 发帖流程
1. 用户发帖 → 敏感词检测
2. 白名单用户 → 直接发布
3. 敏感词命中 → 进入审核队列
4. 管理员审核 → 通过/拒绝

### 管理后台
访问: `/index.php?action=admin`
- 待审核内容
- 敏感词管理
- 白名单用户
- 板块管理

## 备份策略
```bash
# 备份数据库
mysqldump -u bbs -pbbs123456 bbs > bbs_$(date +%Y%m%d).sql

# 备份代码
tar -czf bbs_code_$(date +%Y%m%d).tar.gz /var/www/bbs
```

## 安全建议
1. 定期更新敏感词库
2. 定期备份数据库
3. 限制管理后台IP访问
4. 启用HTTPS
5. 定期查看审核日志

## 常见问题
1. 企业微信登录失败: 检查配置的企业ID/Secret/AgentId
2. 403错误: 确认访问IP在白名单中
3. 数据库连接失败: 检查config中的数据库凭据