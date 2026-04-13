#!/bin/bash
# Discuz! X3.5 自动安装脚本

cd /var/www/bbs

# 导入数据库
mysql -u bbs -pbbs123456 bbs < /root/code/code6/bbs/utility/backup/bbs.sql 2>/dev/null

# 如果没有sql文件，创建基础表结构
if [ $? -ne 0 ]; then
    mysql -u bbs -pbbs123456 bbs -e "
    -- 用户表
    CREATE TABLE IF NOT EXISTS pre_common_member (
        uid INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(32) NOT NULL,
        email VARCHAR(100),
        regdate INT,
        lastvisit INT,
        lastpost INT,
        adminid INT DEFAULT 0,
        groupid INT DEFAULT 10,
        credits INT DEFAULT 0,
        emailstatus INT DEFAULT 0,
        avatarstatus INT DEFAULT 0,
        videophotostatus INT DEFAULT 0,
        adminid INT DEFAULT 0
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS pre_common_member_count (
        uid INT PRIMARY KEY,
        extcredits1 INT DEFAULT 0,
        extcredits2 INT DEFAULT 0,
        extcredits3 INT DEFAULT 0,
        extcredits4 INT DEFAULT 0,
        extcredits5 INT DEFAULT 0,
        extcredits6 INT DEFAULT 0,
        extcredits7 INT DEFAULT 0,
        extcredits8 INT DEFAULT 0,
        posts INT DEFAULT 0,
        threads INT DEFAULT 0,
        digestposts INT DEFAULT 0,
        onlineall INT DEFAULT 0,
        attachsize INT DEFAULT 0,
        views INT DEFAULT 0,
        replies INT DEFAULT 0
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    -- 论坛板块表
    CREATE TABLE IF NOT EXISTS pre_forum_forum (
        fid INT PRIMARY KEY AUTO_INCREMENT,
        fup INT DEFAULT 0,
        type VARCHAR(20) DEFAULT 'forum',
        name VARCHAR(50) NOT NULL,
        status INT DEFAULT 1,
        displayorder INT DEFAULT 0,
        threads INT DEFAULT 0,
        posts INT DEFAULT 0,
        todayposts INT DEFAULT 0,
        yesterdayposts INT DEFAULT 0
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    -- 帖子表
    CREATE TABLE IF NOT EXISTS pre_forum_thread (
        tid INT PRIMARY KEY AUTO_INCREMENT,
        fid INT NOT NULL,
        posttableid INT DEFAULT 0,
        typeid INT DEFAULT 0,
        sortid INT DEFAULT 0,
        readperm INT DEFAULT 0,
        price INT DEFAULT 0,
        author VARCHAR(50) NOT NULL,
        authorid INT NOT NULL,
        subject VARCHAR(255) NOT NULL,
        dateline INT NOT NULL,
        lastpost INT NOT NULL,
        lastposter VARCHAR(50) DEFAULT '',
        views INT DEFAULT 0,
        replies INT DEFAULT 0,
        displayorder INT DEFAULT 0,
        highlight INT DEFAULT 0,
        digest INT DEFAULT 0,
        rate INT DEFAULT 0,
        icon INT DEFAULT 0,
        attachstatus INT DEFAULT 0,
        moderated INT DEFAULT 0,
        closed INT DEFAULT 0
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    -- 帖子内容表
    CREATE TABLE IF NOT EXISTS pre_forum_post (
        pid INT PRIMARY KEY AUTO_INCREMENT,
        fid INT NOT NULL,
        tid INT NOT NULL,
        first INT DEFAULT 0,
        author VARCHAR(50) NOT NULL,
        authorid INT NOT NULL,
        subject VARCHAR(255),
        message TEXT,
        dateline INT NOT NULL,
        useip VARCHAR(50),
        port INT DEFAULT 0,
        invisible INT DEFAULT 0,
        anonymous INT DEFAULT 0,
        usesig INT DEFAULT 1,
        htmlon INT DEFAULT 0,
        smileyon INT DEFAULT 0,
        parseurloff INT DEFAULT 0,
        attachstatus INT DEFAULT 0,
        rate INT DEFAULT 0,
        ratetimes INT DEFAULT 0,
        status INT DEFAULT 0,
        modposts INT DEFAULT 0
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    -- 敏感词表
    CREATE TABLE IF NOT EXISTS pre_common_sensitive (
        id INT PRIMARY KEY AUTO_INCREMENT,
        word VARCHAR(100) NOT NULL,
        replacement VARCHAR(50) DEFAULT '***',
        level INT DEFAULT 1,
        addtime INT DEFAULT 0
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    -- 审核队列表
    CREATE TABLE IF NOT EXISTS pre_common_audit (
        id INT PRIMARY KEY AUTO_INCREMENT,
        uid INT NOT NULL,
        username VARCHAR(50),
        fid INT DEFAULT 0,
        tid INT DEFAULT 0,
        pid INT DEFAULT 0,
        content TEXT,
        type VARCHAR(20) DEFAULT 'post',
        status INT DEFAULT 0,
        createtime INT DEFAULT 0,
        audittime INT DEFAULT 0,
        auditor INT DEFAULT 0,
        reason VARCHAR(255)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    -- 白名单用户表
    CREATE TABLE IF NOT EXISTS pre_common_whitelist (
        id INT PRIMARY KEY AUTO_INCREMENT,
        uid INT NOT NULL,
        username VARCHAR(50),
        addtime INT DEFAULT 0,
        admin VARCHAR(50),
        note VARCHAR(255)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    -- IP白名单表
    CREATE TABLE IF NOT EXISTS pre_common_ip_whitelist (
        id INT PRIMARY KEY AUTO_INCREMENT,
        ip VARCHAR(50) NOT NULL,
        type VARCHAR(20) DEFAULT 'allow',
        addtime INT DEFAULT 0,
        admin VARCHAR(50),
        note VARCHAR(255)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    -- UCenter用户表
    CREATE TABLE IF NOT EXISTS pre_ucenter_members (
        uid INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(32) NOT NULL,
        email VARCHAR(100),
        regip VARCHAR(50),
        regdate INT,
        lastloginip VARCHAR(50),
        lastlogintime INT,
        salt VARCHAR(6)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

    -- 管理员账号 admin/admin888
    INSERT INTO pre_common_member (uid, username, password, email, regdate, groupid, adminid) VALUES 
    (1, 'admin', 'd3c2d3b4f8a8d5e6c7b8a9f0e1d2c3b4', 'admin@example.com', ".time().", 1, 1);
    INSERT INTO pre_common_member_count (uid) VALUES (1);
    INSERT INTO pre_ucenter_members (uid, username, password, email, regdate, salt) VALUES 
    (1, 'admin', 'd3c2d3b4f8a8d5e6c7b8a9f0e1d2c3b4', 'admin@example.com', ".time().", '123456');

    -- 默认板块
    INSERT INTO pre_forum_forum (fid, fup, type, name, status, displayorder) VALUES 
    (1, 0, 'group', '默认分类', 1, 0),
    (2, 1, 'forum', '默认版块', 1, 0),
    (3, 1, 'forum', '公告区', 1, 1),
    (4, 1, 'forum', '建议反馈', 1, 2);

    -- 添加默认IP白名单
    INSERT INTO pre_common_ip_whitelist (ip, type, addtime, admin, note) VALUES 
    ('127.0.0.1', 'allow', ".time().", 'system', '本机访问'),
    ('172.20.', 'allow', ".time().", 'system', '办公网');
    "
fi

echo "数据库初始化完成"