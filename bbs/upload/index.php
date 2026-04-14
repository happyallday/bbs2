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
    case 'upload_file':
        uploadFile($link);
        break;
    case 'admin':
        adminPanel($link);
        break;
    case 'audit':
        auditPost($link);
        break;
    case 'add_word':
        addSensitiveWord($link);
        break;
    case 'del_word':
        deleteSensitiveWord($link);
        break;
    case 'add_whitelist':
        addToWhitelist($link);
        break;
    case 'del_whitelist':
        removeFromWhitelist($link);
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
    
    $attachments = mysqli_query($link, "SELECT * FROM pre_forum_attachment WHERE tid=$tid");
    
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
        .attachments { margin-top: 15px; padding: 10px; background: #f9f9f9; border-radius: 5px; }
        .attachments h4 { margin: 0 0 10px 0; font-size: 14px; }
        .attachment-item { display: inline-block; margin: 5px; padding: 8px 12px; background: #fff; border: 1px solid #ddd; border-radius: 3px; }
        .attachment-item img { max-width: 200px; max-height: 150px; }
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
            <div class="post-content">' . $post['message'] . '</div>';
        
        $attachList = mysqli_query($link, "SELECT * FROM pre_forum_attachment WHERE tid=$tid AND pid=" . $post['pid']);
        if (mysqli_num_rows($attachList) > 0) {
            echo '<div class="attachments"><h4>附件:</h4>';
            while ($att = mysqli_fetch_assoc($attachList)) {
                if ($att['isimage']) {
                    echo '<a href="' . $att['filepath'] . '" target="_blank"><img src="' . $att['filepath'] . '" alt="' . htmlspecialchars($att['filename']) . '"></a>';
                } else {
                    echo '<a class="attachment-item" href="' . $att['filepath'] . '" download>' . htmlspecialchars($att['filename']) . ' (' . round($att['filesize']/1024) . 'KB)</a>';
                }
            }
            echo '</div>';
        }
        
        echo '</div>';
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
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 5px; }
        h2 { margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"] { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 3px; }
        .btn { padding: 12px 30px; background: #3498db; color: #fff; border: none; border-radius: 3px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn:hover { background: #2980b9; }
        .upload-section { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .upload-section h4 { margin-top: 0; }
        .file-list { margin-top: 10px; }
        .file-item { display: inline-block; margin: 5px; padding: 8px 12px; background: #fff; border: 1px solid #ddd; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>发布新帖</h2>
        <form action="?action=submit_post" method="post" enctype="multipart/form-data">
            <input type="hidden" name="fid" value="' . $fid . '">
            <div class="form-group">
                <label>标题</label>
                <input type="text" name="subject" required style="width:100%;padding:12px;font-size:16px;">
            </div>
            <div class="form-group">
                <label>内容</label>
                <textarea name="message" id="editor" required></textarea>
            </div>
            <div class="upload-section">
                <h4>上传附件/图片</h4>
                <input type="file" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar">
                <p style="color:#999;font-size:12px;margin:5px 0 0 0;">支持jpg、png、gif、pdf、doc、xls、zip、rar格式，单个文件最大10MB</p>
            </div>
            <button type="submit" class="btn">发布帖子</button>
        </form>
    </div>
    <script>
        CKEDITOR.replace("editor", {
            height: 300,
            filebrowserUploadUrl: "?action=upload_file",
            toolbar: [
                { name: "document", items: ["Source"] },
                { name: "clipboard", items: ["Cut", "Copy", "Paste", "PasteText", "PasteFromWord", "Undo", "Redo"] },
                { name: "editing", items: ["Find", "Replace", "SelectAll"] },
                { name: "basicstyles", items: ["Bold", "Italic", "Underline", "Strike", "Subscript", "Superscript"] },
                { name: "paragraph", items: ["NumberedList", "BulletedList", "Outdent", "Indent", "Blockquote"] },
                { name: "links", items: ["Link", "Unlink", "Anchor"] },
                { name: "insert", items: ["Image", "Table", "HorizontalRule", "Smiley", "SpecialChar"] },
                { name: "styles", items: ["Styles", "Format", "Font", "FontSize"] },
                { name: "colors", items: ["TextColor", "BGColor"] },
                { name: "tools", items: ["Maximize"] }
            ]
        });
    </script>
</body>
</html>';
}

function submitPost($link) {
    if (!isset($_SESSION)) { session_start(); }
    if (!isset($_SESSION['uid'])) {
        die('请先登录');
    }
    
    $fid = intval($_POST['fid']);
    $subject_raw = trim($_POST['subject'] ?? '');
    $message_raw = trim($_POST['message'] ?? '');
    $uid = $_SESSION['uid'];
    $username = mysqli_real_escape_string($link, $_SESSION['username']);
    $now = time();
    
    if (empty($subject_raw) || empty($message_raw)) {
        die('标题和内容不能为空');
    }
    
    require_once __DIR__ . '/source/class/sensitive_filter.php';
    $filter = new SensitiveWordFilter($link);
    $filter->init();
    
    $subjectCheck = $filter->filter($subject_raw);
    $messageCheck = $filter->filter($message_raw);
    
    $isWhitelist = $filter->isWhitelistUser($link, $uid);
    
    if ($isWhitelist) {
        $subject_esc = mysqli_real_escape_string($link, $subject_raw);
        $message_esc = mysqli_real_escape_string($link, $message_raw);
        mysqli_query($link, "INSERT INTO pre_forum_thread (fid, author, authorid, subject, dateline, lastpost, lastposter) 
            VALUES ($fid, '$username', $uid, '$subject_esc', $now, $now, '$username')");
        $tid = mysqli_insert_id($link);
        
        mysqli_query($link, "INSERT INTO pre_forum_post (fid, tid, first, author, authorid, subject, message, dateline) 
            VALUES ($fid, $tid, 1, '$username', $uid, '$subject_esc', '$message_esc', $now)");
        
        echo '发布成功！<a href="?action=view&tid=' . $tid . '">查看帖子</a>';
    } elseif ($subjectCheck['blocked'] || $messageCheck['blocked']) {
        $fullContent = $subject_raw . "\n" . $message_raw;
        $fullContent_esc = mysqli_real_escape_string($link, $fullContent);
        
        mysqli_query($link, "INSERT INTO pre_common_audit 
            (uid, username, fid, tid, pid, content, type, status, createtime) 
            VALUES ($uid, '$username', $fid, 0, 0, '$fullContent_esc', 'post', 0, $now)");
        
        echo '内容包含敏感词，已提交人工审核。耐心等待审核通过后即可显示。<br><a href="?">返回首页</a>';
    } else {
        $subject_esc = mysqli_real_escape_string($link, $subject_raw);
        $message_esc = mysqli_real_escape_string($link, $message_raw);
        mysqli_query($link, "INSERT INTO pre_forum_thread (fid, author, authorid, subject, dateline, lastpost, lastposter) 
            VALUES ($fid, '$username', $uid, '$subject_esc', $now, $now, '$username')");
        $tid = mysqli_insert_id($link);
        
        mysqli_query($link, "INSERT INTO pre_forum_post (fid, tid, first, author, authorid, subject, message, dateline) 
            VALUES ($fid, $tid, 1, '$username', $uid, '$subject_esc', '$message_esc', $now)");
        
        $pid = mysqli_insert_id($link);
        
        handleAttachments($link, $tid, $pid, $uid);
        
        echo '发布成功！<a href="?action=view&tid=' . $tid . '">查看帖子</a>';
    }
}

function handleAttachments($link, $tid, $pid, $uid) {
    if (!isset($_FILES['attachments']) || empty($_FILES['attachments']['name'][0])) {
        return;
    }
    
    $uploadDir = __DIR__ . '/data/attachment/forum/' . date('Ym') . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed'];
    
    $maxSize = 10 * 1024 * 1024;
    
    foreach ($_FILES['attachments']['name'] as $i => $name) {
        if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
        
        $size = $_FILES['attachments']['size'][$i];
        if ($size > $maxSize) continue;
        
        $tmpName = $_FILES['attachments']['tmp_name'][$i];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) continue;
        
        $isImage = in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']) ? 1 : 0;
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $newName = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $newName;
        
        if (move_uploaded_file($tmpName, $targetPath)) {
            $filepath = '/data/attachment/forum/' . date('Ym') . '/' . $newName;
            $filename_esc = mysqli_real_escape_string($link, $name);
            $filepath_esc = mysqli_real_escape_string($link, $filepath);
            $filetype_esc = mysqli_real_escape_string($link, $mimeType);
            
            mysqli_query($link, "INSERT INTO pre_forum_attachment 
                (tid, pid, uid, filename, filetype, filesize, filepath, dateline, isimage) 
                VALUES ($tid, $pid, $uid, '$filename_esc', '$filetype_esc', $size, '$filepath_esc', " . time() . ", $isImage)");
        }
    }
}

function uploadFile($link) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION)) { session_start(); }
    if (!isset($_SESSION['uid'])) {
        echo json_encode(['error' => '未登录']);
        exit;
    }
    
    $uploadDir = __DIR__ . '/data/attachment/forum/' . date('Ym') . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed'];
    
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (isset($_FILES['upload'])) {
        $file = $_FILES['upload'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => '上传失败']);
            exit;
        }
        
        if ($file['size'] > $maxSize) {
            echo json_encode(['error' => '文件超过10MB限制']);
            exit;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            echo json_encode(['error' => '不支持的文件类型']);
            exit;
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newName = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $newName;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $fileUrl = '/data/attachment/forum/' . date('Ym') . '/' . $newName;
            echo json_encode([
                'url' => $fileUrl,
                'filename' => $file['name'],
                'filesize' => $file['size']
            ]);
        } else {
            echo json_encode(['error' => '保存文件失败']);
        }
    } elseif (isset($_FILES['attachments'])) {
        $results = [];
        foreach ($_FILES['attachments']['name'] as $i => $name) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['attachments']['tmp_name'][$i];
                $size = $_FILES['attachments']['size'][$i];
                
                if ($size > $maxSize) {
                    $results[] = ['name' => $name, 'error' => '文件过大'];
                    continue;
                }
                
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    $results[] = ['name' => $name, 'error' => '不支持的类型'];
                    continue;
                }
                
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $newName = uniqid() . '.' . $ext;
                $targetPath = $uploadDir . $newName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $fileUrl = '/data/attachment/forum/' . date('Ym') . '/' . $newName;
                    $results[] = [
                        'name' => $name,
                        'url' => $fileUrl,
                        'size' => $size,
                        'success' => true
                    ];
                } else {
                    $results[] = ['name' => $name, 'error' => '上传失败'];
                }
            }
        }
        echo json_encode($results);
    } else {
        echo json_encode(['error' => '没有文件']);
    }
}

function adminPanel($link) {
    if (!isset($_SESSION)) { session_start(); }
    if (!isset($_SESSION['uid'])) {
        header('Location: ?action=login');
        exit;
    }
    
    $uid = $_SESSION['uid'];
    $user = mysqli_fetch_assoc(mysqli_query($link, "SELECT adminid FROM pre_common_member WHERE uid=$uid"));
    if (!$user || $user['adminid'] == 0) {
        die('无权限访问管理后台');
    }
    
    $tab = $_GET['tab'] ?? 'audit';
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>审核管理</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .nav { margin-bottom: 20px; }
        .nav a { display: inline-block; padding: 10px 20px; background: #fff; text-decoration: none; margin-right: 10px; border-radius: 3px; }
        .nav a.active { background: #3498db; color: #fff; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .audit-item, .word-item, .user-item { background: #fff; padding: 20px; margin-bottom: 15px; border-radius: 5px; }
        .form-inline { display: inline-block; }
        .form-inline input { padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        .form-inline button { padding: 8px 15px; background: #3498db; color: #fff; border: none; border-radius: 3px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>管理后台</h2>
            <div class="nav">
                <a href="?action=admin&tab=audit" class="' . ($tab=='audit'?'active':'') . '">待审核</a>
                <a href="?action=admin&tab=sensitive" class="' . ($tab=='sensitive'?'active':'') . '">敏感词管理</a>
                <a href="?action=admin&tab=whitelist" class="' . ($tab=='whitelist'?'active':'') . '">白名单用户</a>
                <a href="?action=admin&tab=user" class="' . ($tab=='user'?'active':'') . '">用户管理</a>
                <a href="?action=admin&tab=forum" class="' . ($tab=='forum'?'active':'') . '">板块管理</a>
                <a href="?">返回首页</a>
            </div>
        </div>';
    
    if ($tab == 'audit') {
        $result = mysqli_query($link, "SELECT * FROM pre_common_audit WHERE status=0 ORDER BY createtime DESC");
        echo '<div class="tab-content active">
            <h3>待审核内容</h3>';
        while ($item = mysqli_fetch_assoc($result)) {
            echo '<div class="audit-item">
                <p><strong>用户:</strong> ' . htmlspecialchars($item['username']) . '</p>
                <p><strong>内容:</strong></p>
                <pre style="background:#f9f9f9;padding:10px;white-space:pre-wrap;">' . htmlspecialchars($item['content']) . '</pre>
                <form action="?action=audit" method="post">
                    <input type="hidden" name="id" value="' . $item['id'] . '">
                    <input type="hidden" name="tab" value="audit">
                    <button name="status" value="1" style="background:#27ae60;padding:8px 20px;color:#fff;border:none;cursor:pointer;">通过</button>
                    <button name="status" value="2" style="background:#e74c3c;padding:8px 20px;color:#fff;border:none;cursor:pointer;">拒绝</button>
                </form>
            </div>';
        }
        echo '</div>';
    }
    
    if ($tab == 'sensitive') {
        echo '<div class="tab-content active">
            <h3>敏感词管理</h3>
            <form action="?action=add_word" method="post" class="form-inline">
                <input type="text" name="word" placeholder="敏感词" required>
                <input type="text" name="replacement" placeholder="替换词(默认***)" value="***">
                <button type="submit">添加</button>
            </form>
            <table>
            <tr><th>ID</th><th>敏感词</th><th>替换词</th><th>操作</th></tr>';
        
        $result = mysqli_query($link, "SELECT * FROM pre_common_sensitive ORDER BY id DESC");
        while ($item = mysqli_fetch_assoc($result)) {
            echo '<tr>
                <td>' . $item['id'] . '</td>
                <td>' . htmlspecialchars($item['word']) . '</td>
                <td>' . htmlspecialchars($item['replacement']) . '</td>
                <td><a href="?action=del_word&id=' . $item['id'] . '">删除</a></td>
            </tr>';
        }
        echo '</table></div>';
    }
    
    if ($tab == 'whitelist') {
        echo '<div class="tab-content active">
            <h3>白名单用户 (免审核)</h3>
            <form action="?action=add_whitelist" method="post" class="form-inline">
                <input type="text" name="uid" placeholder="用户ID" required>
                <input type="text" name="username" placeholder="用户名" required>
                <input type="text" name="note" placeholder="备注">
                <button type="submit">添加白名单</button>
            </form>
            <table>
            <tr><th>ID</th><th>用户ID</th><th>用户名</th><th>添加时间</th><th>备注</th><th>操作</th></tr>';
        
        $result = mysqli_query($link, "SELECT * FROM pre_common_whitelist ORDER BY id DESC");
        while ($item = mysqli_fetch_assoc($result)) {
            echo '<tr>
                <td>' . $item['id'] . '</td>
                <td>' . $item['uid'] . '</td>
                <td>' . htmlspecialchars($item['username']) . '</td>
                <td>' . date('Y-m-d H:i', $item['addtime']) . '</td>
                <td>' . htmlspecialchars($item['note']) . '</td>
                <td><a href="?action=del_whitelist&id=' . $item['id'] . '">移除</a></td>
            </tr>';
        }
        echo '</table></div>';
    }
    
    if ($tab == 'user') {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
            $username = mysqli_real_escape_string($link, $_POST['username']);
            $password = md5($_POST['password']);
            $email = mysqli_real_escape_string($link, $_POST['email'] ?? '');
            $groupid = intval($_POST['groupid'] ?? 10);
            $adminid = intval($_POST['adminid'] ?? 0);
            $now = time();
            
            $check = mysqli_query($link, "SELECT uid FROM pre_common_member WHERE username='$username'");
            if (mysqli_num_rows($check) == 0) {
                mysqli_query($link, "INSERT INTO pre_common_member (username, password, email, regdate, groupid, adminid) 
                    VALUES ('$username', '$password', '$email', $now, $groupid, $adminid)");
                $uid = mysqli_insert_id($link);
                mysqli_query($link, "INSERT INTO pre_common_member_count (uid) VALUES ($uid)");
            }
        }
        
        if (isset($_GET['del_user'])) {
            $uid = intval($_GET['del_user']);
            if ($uid > 1) {
                mysqli_query($link, "DELETE FROM pre_common_member WHERE uid=$uid");
                mysqli_query($link, "DELETE FROM pre_common_member_count WHERE uid=$uid");
            }
        }
        
        if (isset($_GET['set_admin'])) {
            $uid = intval($_GET['set_admin']);
            $adminid = intval($_GET['set_admin']);
            mysqli_query($link, "UPDATE pre_common_member SET adminid=$adminid WHERE uid=$uid");
        }
        
        echo '<div class="tab-content active">
            <h3>用户管理</h3>
            <form action="?action=admin&tab=user" method="post" class="form-inline">
                <input type="hidden" name="add_user" value="1">
                <input type="text" name="username" placeholder="用户名" required>
                <input type="password" name="password" placeholder="密码" required>
                <input type="email" name="email" placeholder="邮箱">
                <select name="groupid">
                    <option value="10">普通用户</option>
                    <option value="1">超级管理员</option>
                    <option value="2">管理员</option>
                    <option value="3">版主</option>
                </select>
                <select name="adminid">
                    <option value="0">普通用户</option>
                    <option value="1">超级管理员</option>
                    <option value="2">管理员</option>
                </select>
                <button type="submit">添加用户</button>
            </form>
            <table>
            <tr><th>ID</th><th>用户名</th><th>邮箱</th><th>注册时间</th><th>用户组</th><th>管理权限</th><th>操作</th></tr>';
        
        $result = mysqli_query($link, "SELECT uid, username, email, regdate, groupid, adminid 
            FROM pre_common_member ORDER BY uid");
        
        while ($item = mysqli_fetch_assoc($result)) {
            $groupTitle = $item['groupid'] == 1 ? '超级管理员' : ($item['groupid'] == 2 ? '管理员' : ($item['groupid'] == 3 ? '版主' : '普通用户'));
            $adminLabel = $item['adminid'] == 1 ? '超管' : ($item['adminid'] == 2 ? '管理员' : '普通');
            $setAdminLink = $item['adminid'] == 0 ? "<a href=\"?action=admin&tab=user&set_admin=1&uid={$item['uid']}\">设为管理员</a>" : "<a href=\"?action=admin&tab=user&set_admin=0&uid={$item['uid']}\">取消管理员</a>";
            
            echo '<tr>
                <td>' . $item['uid'] . '</td>
                <td>' . htmlspecialchars($item['username']) . '</td>
                <td>' . htmlspecialchars($item['email'] ?? '-') . '</td>
                <td>' . date('Y-m-d', $item['regdate']) . '</td>
                <td>' . $groupTitle . '</td>
                <td>' . $adminLabel . '</td>
                <td>' . ($item['uid'] > 1 ? $setAdminLink . ' | <a href="?action=admin&tab=user&del_user=' . $item['uid'] . '">删除</a>' : '-') . '</td>
            </tr>';
        }
        echo '</table></div>';
    }
    
    if ($tab == 'forum') {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_forum'])) {
            $name = mysqli_real_escape_string($link, $_POST['name']);
            $fup = intval($_POST['fup'] ?? 1);
            $now = time();
            mysqli_query($link, "INSERT INTO pre_forum_forum (fup, type, name, status, displayorder) VALUES ($fup, 'forum', '$name', 1, 0)");
        }
        
        if (isset($_GET['del_forum'])) {
            $fid = intval($_GET['del_forum']);
            mysqli_query($link, "DELETE FROM pre_forum_forum WHERE fid=$fid AND type='forum'");
        }
        
        echo '<div class="tab-content active">
            <h3>板块管理</h3>
            <form action="?action=admin&tab=forum" method="post" class="form-inline">
                <input type="hidden" name="add_forum" value="1">
                <input type="text" name="name" placeholder="板块名称" required>
                <select name="fup">';
        
        $categories = mysqli_query($link, "SELECT * FROM pre_forum_forum WHERE type='group' ORDER BY displayorder");
        while ($cat = mysqli_fetch_assoc($categories)) {
            echo '<option value="' . $cat['fid'] . '">' . htmlspecialchars($cat['name']) . '</option>';
        }
        
        echo '</select>
                <button type="submit">添加板块</button>
            </form>
            <table>
            <tr><th>ID</th><th>板块名称</th><th>所属分类</th><th>帖子数</th><th>操作</th></tr>';
        
        $result = mysqli_query($link, "SELECT f.*, p.name as parent_name FROM pre_forum_forum f 
            LEFT JOIN pre_forum_forum p ON f.fup=p.fid 
            WHERE f.type='forum' ORDER BY f.displayorder");
        while ($item = mysqli_fetch_assoc($result)) {
            echo '<tr>
                <td>' . $item['fid'] . '</td>
                <td>' . htmlspecialchars($item['name']) . '</td>
                <td>' . htmlspecialchars($item['parent_name'] ?? '顶级') . '</td>
                <td>' . ($item['threads'] ?? 0) . '</td>
                <td><a href="?action=admin&tab=forum&del_forum=' . $item['fid'] . '">删除</a></td>
            </tr>';
        }
        echo '</table></div>';
    }
    
    echo '</div>
</body>
</html>';
}

function auditPost($link) {
    $id = intval($_POST['id']);
    $status = intval($_POST['status']);
    $tab = $_POST['tab'] ?? 'audit';
    
    $audit = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM pre_common_audit WHERE id=$id"));
    
    if ($status == 1 && $audit) {
        $content = $audit['content'];
        $lines = explode("\n", $content, 2);
        $subject = mysqli_real_escape_string($link, trim($lines[0]));
        $message = mysqli_real_escape_string($link, trim($lines[1] ?? ''));
        
        $fid = $audit['fid'];
        $uid = $audit['uid'];
        $username = mysqli_real_escape_string($link, $audit['username']);
        $now = time();
        
        mysqli_query($link, "INSERT INTO pre_forum_thread (fid, author, authorid, subject, dateline, lastpost, lastposter) 
            VALUES ($fid, '$username', $uid, '$subject', $now, $now, '$username')");
        $tid = mysqli_insert_id($link);
        
        mysqli_query($link, "INSERT INTO pre_forum_post (fid, tid, first, author, authorid, subject, message, dateline) 
            VALUES ($fid, $tid, 1, '$username', $uid, '$subject', '$message', $now)");
    }
    
    mysqli_query($link, "UPDATE pre_common_audit SET status=$status, audittime=" . time() . " WHERE id=$id");
    
    header("Location: ?action=admin&tab=" . $tab);
}

function addSensitiveWord($link) {
    $word = mysqli_real_escape_string($link, trim($_POST['word']));
    $replacement = mysqli_real_escape_string($link, $_POST['replacement'] ?? '***');
    $now = time();
    
    mysqli_query($link, "INSERT INTO pre_common_sensitive (word, replacement, level, addtime) 
        VALUES ('$word', '$replacement', 1, $now)");
    
    header('Location: ?action=admin&tab=sensitive');
}

function deleteSensitiveWord($link) {
    $id = intval($_GET['id']);
    mysqli_query($link, "DELETE FROM pre_common_sensitive WHERE id=$id");
    header('Location: ?action=admin&tab=sensitive');
}

function addToWhitelist($link) {
    $uid = intval($_POST['uid']);
    $username = mysqli_real_escape_string($link, trim($_POST['username']));
    $note = mysqli_real_escape_string($link, $_POST['note'] ?? '');
    $now = time();
    
    mysqli_query($link, "INSERT INTO pre_common_whitelist (uid, username, addtime, note) 
        VALUES ($uid, '$username', $now, '$note')");
    
    header('Location: ?action=admin&tab=whitelist');
}

function removeFromWhitelist($link) {
    $id = intval($_GET['id']);
    mysqli_query($link, "DELETE FROM pre_common_whitelist WHERE id=$id");
    header('Location: ?action=admin&tab=whitelist');
}