<?php
/**
 * 企业员工论坛 - 入口文件
 * 基于Discuz! X3.5二次开发
 * M1: 基础部署 + 本机/办公网访问
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

$dbhost = 'localhost';
$dbuser = 'bbs';
$dbpw = 'bbs123456';
$dbname = 'bbs';

$link = mysqli_connect($dbhost, $dbuser, $dbpw, $dbname);
if (!$link) {
    die('数据库连接失败: ' . mysqli_connect_error());
}
mysqli_query($link, "SET NAMES utf8mb4");

$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'forum':
        forumList($link);
        break;
    case 'view':
        viewThread($link);
        break;
    case 'post':
        postForm($link);
        break;
    case 'login':
        loginForm();
        break;
    case 'dologin':
        doLogin($link);
        break;
    case 'logout':
        logout();
        break;
    case 'submit_post':
        submitPost($link);
        break;
    case 'admin':
        adminPanel($link);
        break;
    case 'audit':
        auditPost($link);
        break;
    default:
        index($link);
}

function index($link) {
    $result = mysqli_query($link, "SELECT * FROM pre_forum_forum WHERE type='forum' AND status=1 ORDER BY displayorder");
    $forums = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $forums[] = $row;
    }
    
    if (!isset($_SESSION)) { session_start(); }
    $isLogin = isset($_SESSION['uid']);
    $username = $_SESSION['username'] ?? '';
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>企业员工论坛</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: #2c3e50; color: #fff; padding: 15px 30px; }
        .header h1 { margin: 0; display: inline-block; }
        .header .nav { float: right; }
        .header a { color: #fff; text-decoration: none; margin-left: 20px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .forum-list { background: #fff; border-radius: 5px; }
        .forum-item { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .forum-item:last-child { border-bottom: none; }
        .forum-name { font-size: 18px; color: #2c3e50; font-weight: bold; }
        .forum-stats { color: #999; font-size: 14px; }
        .welcome { background: #fff; padding: 15px 20px; margin-bottom: 20px; border-radius: 5px; }
        .btn { display: inline-block; padding: 8px 20px; background: #3498db; color: #fff; text-decoration: none; border-radius: 3px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>企业员工论坛</h1>
        <div class="nav">
            ' . ($isLogin ? '<span>欢迎, ' . htmlspecialchars($username) . '</span> <a href="?action=logout">退出</a>' : '<a href="?action=login">登录</a>') . '
        </div>
    </div>
    <div class="container">
        <div class="welcome">
            欢迎访问企业员工论坛！请遵守论坛规则，文明发言。
            <br><small>本系统仅限办公网(172.20.0.0/16)及本机访问</small>
        </div>
        <div class="forum-list">
            <h2>论坛板块</h2>';
    
    foreach ($forums as $forum) {
        echo '<div class="forum-item">
            <div>
                <div class="forum-name">' . htmlspecialchars($forum['name']) . '</div>
            </div>
            <a href="?action=forum&id=' . $forum['fid'] . '" class="btn">进入版块</a>
        </div>';
    }
    
    echo '</div>
    </div>
</body>
</html>';
}

function forumList($link) {
    $fid = intval($_GET['id'] ?? 0);
    $result = mysqli_query($link, "SELECT * FROM pre_forum_forum WHERE fid=$fid");
    $forum = mysqli_fetch_assoc($result);
    
    $threads = mysqli_query($link, "SELECT t.*, m.username FROM pre_forum_thread t 
        LEFT JOIN pre_common_member m ON t.authorid=m.uid 
        WHERE t.fid=$fid AND t.displayorder>=0 ORDER BY t.lastpost DESC LIMIT 50");
    
    if (!isset($_SESSION)) { session_start(); }
    $isLogin = isset($_SESSION['uid']);
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>' . htmlspecialchars($forum['name']) . ' - 企业论坛</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: #2c3e50; color: #fff; padding: 15px 30px; }
        .header a { color: #fff; text-decoration: none; margin-right: 20px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .thread-list { background: #fff; border-radius: 5px; }
        .thread-item { padding: 15px 20px; border-bottom: 1px solid #eee; }
        .thread-title { font-size: 16px; color: #2c3e50; }
        .thread-title a { color: #2c3e50; text-decoration: none; }
        .thread-info { color: #999; font-size: 13px; margin-top: 5px; }
        .btn { display: inline-block; padding: 8px 20px; background: #3498db; color: #fff; text-decoration: none; border-radius: 3px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="header">
        <a href="?">返回首页</a>
        <span>' . htmlspecialchars($forum['name']) . '</span>
    </div>
    <div class="container">
        ' . ($isLogin ? '<a href="?action=post&fid=' . $fid . '" class="btn">发布新帖</a>' : '<a href="?action=login" class="btn">登录后发帖</a>') . '
        <div class="thread-list">';
    
    while ($thread = mysqli_fetch_assoc($threads)) {
        echo '<div class="thread-item">
            <div class="thread-title"><a href="?action=view&tid=' . $thread['tid'] . '">' . htmlspecialchars($thread['subject']) . '</a></div>
            <div class="thread-info">作者: ' . htmlspecialchars($thread['author']) . ' | 回复: ' . $thread['replies'] . ' | 浏览: ' . $thread['views'] . '</div>
        </div>';
    }
    
    echo '</div>
    </div>
</body>
</html>';
}

function viewThread($link) {
    $tid = intval($_GET['tid'] ?? 0);
    
    $thread = mysqli_fetch_assoc(mysqli_query($link, "SELECT t.*, m.username FROM pre_forum_thread t 
        LEFT JOIN pre_common_member m ON t.authorid=m.uid WHERE t.tid=$tid"));
    
    $posts = mysqli_query($link, "SELECT p.*, m.username FROM pre_forum_post p 
        LEFT JOIN pre_common_member m ON p.authorid=m.uid WHERE p.tid=$tid ORDER BY p.dateline");
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>' . htmlspecialchars($thread['subject']) . '</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: #2c3e50; color: #fff; padding: 15px 30px; }
        .header a { color: #fff; text-decoration: none; margin-right: 20px; }
        .container { max-width: 1000px; margin: 20px auto; padding: 0 20px; }
        .thread { background: #fff; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .thread h2 { margin: 0 0 15px 0; color: #2c3e50; }
        .post { background: #fff; padding: 20px; border-radius: 5px; margin-bottom: 15px; }
        .post-author { font-weight: bold; color: #3498db; margin-bottom: 10px; }
        .post-content { line-height: 1.8; }
    </style>
</head>
<body>
    <div class="header">
        <a href="?">返回首页</a>
        <a href="?action=forum&id=' . $thread['fid'] . '">返回版块</a>
    </div>
    <div class="container">
        <div class="thread">
            <h2>' . htmlspecialchars($thread['subject']) . '</h2>
            <div class="post-info">作者: ' . htmlspecialchars($thread['author']) . ' | 发布时间: ' . date('Y-m-d H:i', $thread['dateline']) . '</div>
        </div>';
    
    while ($post = mysqli_fetch_assoc($posts)) {
        echo '<div class="post">
            <div class="post-author">' . htmlspecialchars($post['username']) . ' (' . date('Y-m-d H:i', $post['dateline']) . ')</div>
            <div class="post-content">' . nl2br(htmlspecialchars($post['message'])) . '</div>
        </div>';
    }
    
    echo '</div>
</body>
</html>';
}

function loginForm() {
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>登录 - 企业论坛</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; background: #f5f5f5; padding: 50px; }
        .login-box { max-width: 400px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 5px; }
        h2 { margin-top: 0; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 3px; }
        .btn { width: 100%; padding: 12px; background: #3498db; color: #fff; border: none; border-radius: 3px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #2980b9; }
        .wechat-btn { background: #1aad19; margin-top: 10px; }
        .wechat-btn:hover { background: #168d13; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>用户登录</h2>
        <form action="?action=dologin" method="post">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">登录</button>
        </form>
        <a href="/wechat_oauth.php?action=login"><button class="btn wechat-btn">企业微信登录</button></a>
    </div>
</body>
</html>';
}

function doLogin($link) {
    $username = trim($_POST['username'] ?? '');
    $password = md5($_POST['password'] ?? '');
    
    $username = mysqli_real_escape_string($link, $username);
    $result = mysqli_query($link, "SELECT * FROM pre_common_member WHERE username='$username' AND password='$password'");
    $user = mysqli_fetch_assoc($result);
    
    if ($user) {
        if (!isset($_SESSION)) { session_start(); }
        $_SESSION['uid'] = $user['uid'];
        $_SESSION['username'] = $user['username'];
        header('Location: ?');
        exit;
    } else {
        echo '用户名或密码错误 <a href="?action=login">返回</a>';
    }
}

function logout() {
    if (!isset($_SESSION)) { session_start(); }
    session_destroy();
    header('Location: ?');
    exit;
}

function postForm($link) {
    if (!isset($_SESSION)) { session_start(); }
    if (!isset($_SESSION['uid'])) {
        header('Location: ?action=login');
        exit;
    }
    
    $fid = intval($_GET['fid'] ?? 0);
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>发布新帖</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 5px; }
        h2 { margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 3px; }
        .form-group textarea { height: 200px; }
        .btn { padding: 12px 30px; background: #3498db; color: #fff; border: none; border-radius: 3px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>发布新帖</h2>
        <form action="?action=submit_post" method="post">
            <input type="hidden" name="fid" value="' . $fid . '">
            <div class="form-group">
                <label>标题</label>
                <input type="text" name="subject" required>
            </div>
            <div class="form-group">
                <label>内容</label>
                <textarea name="message" required></textarea>
            </div>
            <button type="submit" class="btn">提交</button>
        </form>
    </div>
</body>
</html>';
}

function submitPost($link) {
    if (!isset($_SESSION)) { session_start(); }
    if (!isset($_SESSION['uid'])) {
        die('请先登录');
    }
    
    $fid = intval($_POST['fid']);
    $subject = mysqli_real_escape_string($link, trim($_POST['subject']));
    $message = mysqli_real_escape_string($link, trim($_POST['message']));
    $uid = $_SESSION['uid'];
    $username = mysqli_real_escape_string($link, $_SESSION['username']);
    $now = time();
    
    if (empty($subject) || empty($message)) {
        die('标题和内容不能为空');
    }
    
    mysqli_query($link, "INSERT INTO pre_forum_thread (fid, author, authorid, subject, dateline, lastpost, lastposter) 
        VALUES ($fid, '$username', $uid, '$subject', $now, $now, '$username')");
    $tid = mysqli_insert_id($link);
    
    mysqli_query($link, "INSERT INTO pre_forum_post (fid, tid, first, author, authorid, subject, message, dateline) 
        VALUES ($fid, $tid, 1, '$username', $uid, '$subject', '$message', $now)");
    
    echo '发布成功！<a href="?action=view&tid=' . $tid . '">查看帖子</a>';
}

function adminPanel($link) {
    if (!isset($_SESSION)) { session_start(); }
    if (!isset($_SESSION['uid'])) {
        header('Location: ?action=login');
        exit;
    }
    
    $result = mysqli_query($link, "SELECT * FROM pre_common_audit WHERE status=0 ORDER BY createtime DESC");
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>审核管理</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .audit-item { background: #fff; padding: 20px; margin-bottom: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>待审核内容</h2>
            <a href="?">返回首页</a>
        </div>';
    
    while ($item = mysqli_fetch_assoc($result)) {
        echo '<div class="audit-item">
            <p>用户: ' . htmlspecialchars($item['username']) . '</p>
            <p>内容: ' . htmlspecialchars($item['content']) . '</p>
            <form action="?action=audit" method="post">
                <input type="hidden" name="id" value="' . $item['id'] . '">
                <button name="status" value="1">通过</button>
                <button name="status" value="2">拒绝</button>
            </form>
        </div>';
    }
    
    echo '</div>
</body>
</html>';
}

function auditPost($link) {
    $id = intval($_POST['id']);
    $status = intval($_POST['status']);
    
    mysqli_query($link, "UPDATE pre_common_audit SET status=$status, audittime=" . time() . " WHERE id=$id");
    
    header('Location: ?action=admin');
}