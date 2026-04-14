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
    case 'submit_reply':
        submitReply($link);
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
    
    $hotThreads = mysqli_query($link, "SELECT t.*, f.name as forum_name FROM pre_forum_thread t 
        LEFT JOIN pre_forum_forum f ON t.fid=f.fid 
        ORDER BY t.views DESC LIMIT 5");
    
    if (!isset($_SESSION)) { session_start(); }
    $isLogin = isset($_SESSION['uid']);
    $username = $_SESSION['username'] ?? '';
    $avatar = $isLogin ? mb_substr($username, 0, 1) : '?';
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>企业员工论坛</title>
    <link rel="stylesheet" href="/template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <i class="fas fa-comments" style="font-size:32px;color:#667eea;"></i>
            <h1>企业员工论坛</h1>
        </div>
        <div class="header-nav">
            <a href="/"><i class="fas fa-home"></i> 首页</a>
            ' . ($isLogin ? '
                <a href="?action=admin"><i class="fas fa-cog"></i> 管理后台</a>
                <div class="user-info">
                    <div class="avatar">' . $avatar . '</div>
                    <span>' . htmlspecialchars($username) . '</span>
                </div>
                <a href="?action=logout" style="color:#ff4d4f;"><i class="fas fa-sign-out-alt"></i> 退出</a>
            ' : '<a href="?action=login"><i class="fas fa-sign-in-alt"></i> 登录</a>') . '
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-banner">
            <h2><i class="fas fa-bullhorn"></i> 欢迎来到企业员工论坛</h2>
            <p>分享工作经验、交流技术心得、发布通知公告，让我们共同打造和谐的企业文化！</p>
        </div>
        
        <div class="quick-actions">
            <a href="?action=post&fid=2" class="action-card">
                <div class="icon"><i class="fas fa-pen"></i></div>
                <h4>发布新帖</h4>
                <p>分享你的想法</p>
            </a>
            <a href="?action=forum&id=3" class="action-card">
                <div class="icon"><i class="fas fa-bullhorn"></i></div>
                <h4>公告区</h4>
                <p>查看公司公告</p>
            </a>
            <a href="?action=forum&id=4" class="action-card">
                <div class="icon"><i class="fas fa-lightbulb"></i></div>
                <h4>建议反馈</h4>
                <p>提出意见建议</p>
            </a>
            <a href="?action=login" class="action-card">
                <div class="icon"><i class="fas fa-user-plus"></i></div>
                <h4>' . ($isLogin ? '个人中心' : '加入我们') . '</h4>
                <p>' . ($isLogin ? '管理我的帖子' : '登录参与讨论') . '</p>
            </a>
        </div>
        
        <div class="forum-section">
            <div class="section-header">
                <div class="section-title">
                    <div class="icon"><i class="fas fa-layer-group"></i></div>
                    论坛板块
                </div>
            </div>';
    
    $icons = ['fa-comments', 'fa-bullhorn', 'fa-lightbulb', 'fa-book', 'fa-code', 'video'];
    $iconIndex = 0;
    foreach ($forums as $forum) {
        $icon = $icons[$iconIndex % count($icons)];
        $threadCount = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) as cnt FROM pre_forum_thread WHERE fid=" . $forum['fid']))['cnt'] ?? 0;
        $postCount = mysqli_fetch_assoc(mysqli_query($link, "SELECT SUM(replies) as total FROM pre_forum_thread WHERE fid=" . $forum['fid']))['total'] ?? 0;
        
        echo '<div class="forum-card">
            <div class="forum-info">
                <div class="forum-icon"><i class="fas ' . $icon . '"></i></div>
                <div class="forum-details">
                    <h3>' . htmlspecialchars($forum['name']) . '</h3>
                    <p>' . ($forum['description'] ?? '点击进入版块') . '</p>
                </div>
            </div>
            <div class="forum-stats">
                <div class="stat-item">
                    <div class="num">' . $threadCount . '</div>
                    <div class="label">主题</div>
                </div>
                <div class="stat-item">
                    <div class="num">' . $postCount . '</div>
                    <div class="label">回复</div>
                </div>
            </div>
            <a href="?action=forum&id=' . $forum['fid'] . '" class="btn-enter"><i class="fas fa-arrow-right"></i> 进入</a>
        </div>';
        $iconIndex++;
    }
    
    echo '</div>
        
        <div class="forum-section">
            <div class="section-header">
                <div class="section-title">
                    <div class="icon"><i class="fas fa-fire"></i></div>
                    热门话题
                </div>
            </div>
            <div class="forum-card" style="display:block;">';
    
    while ($thread = mysqli_fetch_assoc($hotThreads)) {
        if ($thread['tid']) {
            echo '<div style="padding:12px 0;border-bottom:1px solid #eee;">
                <a href="?action=view&tid=' . $thread['tid'] . '" style="color:var(--primary-color);text-decoration:none;font-size:15px;">' . htmlspecialchars($thread['subject']) . '</a>
                <span style="color:#999;font-size:12px;margin-left:10px;">[' . htmlspecialchars($thread['forum_name']) . ']</span>
            </div>';
        }
    }
    echo '</div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2024 企业员工论坛 · 内网系统 · 仅限办公网访问</p>
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
    $username = $_SESSION['username'] ?? '';
    $avatar = $isLogin ? mb_substr($username, 0, 1) : '?';
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . htmlspecialchars($forum['name']) . ' - 企业论坛</title>
    <link rel="stylesheet" href="/template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <a href="/" style="text-decoration:none;"><i class="fas fa-comments" style="font-size:32px;color:#667eea;"></i></a>
            <h1><a href="/" style="text-decoration:none;color:inherit;">企业员工论坛</a></h1>
        </div>
        <div class="header-nav">
            <a href="/"><i class="fas fa-home"></i> 首页</a>
            ' . ($isLogin ? '
                <div class="user-info">
                    <div class="avatar">' . $avatar . '</div>
                    <span>' . htmlspecialchars($username) . '</span>
                </div>
                <a href="?action=logout" style="color:#ff4d4f;"><i class="fas fa-sign-out-alt"></i> 退出</a>
            ' : '<a href="?action=login"><i class="fas fa-sign-in-alt"></i> 登录</a>') . '
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-banner" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
            <h2><i class="fas fa-folder-open"></i> ' . htmlspecialchars($forum['name']) . '</h2>
            <p>' . ($forum['description'] ?? '浏览' . htmlspecialchars($forum['name']) . '版块内容') . '</p>
        </div>
        
        <div style="margin-bottom:20px;">
            ' . ($isLogin ? '<a href="?action=post&fid=' . $fid . '" class="btn-enter" style="background: linear-gradient(135deg, #11998e, #38ef7d);"><i class="fas fa-pen"></i> 发布新帖</a>' : '<a href="?action=login" class="btn-enter"><i class="fas fa-lock"></i> 登录后发帖</a>') . '
        </div>
        
        <div class="forum-section">
            <div class="forum-card" style="display:block;padding:0;">
                <div style="background:linear-gradient(135deg, #667eea20, #764ba220);padding:16px 24px;border-bottom:1px solid #eee;font-weight:600;color:#303133;display:flex;align-items:center;justify-content:space-between;">
                    <span><i class="fas fa-list"></i> 帖子列表</span>
                    <span style="font-size:13px;font-weight:normal;color:var(--text-secondary);">共 ' . mysqli_num_rows($threads) . ' 篇</span>
                </div>';
    
    while ($thread = mysqli_fetch_assoc($threads)) {
        $timeAgo = time() - $thread['dateline'];
        if ($timeAgo < 3600) $timeStr = intval($timeAgo/60) . '分钟前';
        elseif ($timeAgo < 86400) $timeStr = intval($timeAgo/3600) . '小时前';
        else $timeStr = date('m-d', $thread['dateline']);
        
        $isNew = $timeAgo < 86400;
        
        echo '<div class="thread-item" style="padding:20px 24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;transition:all 0.2s;">
            <div style="flex:1;display:flex;align-items:center;gap:16px;">
                <div style="width:48px;height:48px;background:linear-gradient(135deg, var(--primary-color), #36cfc9);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;font-weight:600;">
                    ' . mb_substr($thread['author'], 0, 1) . '
                </div>
                <div style="flex:1;">
                    <a href="?action=view&tid=' . $thread['tid'] . '" style="color:var(--text-color);text-decoration:none;font-size:16px;font-weight:500;display:flex;align-items:center;gap:8px;">' . htmlspecialchars($thread['subject']) . '
                    ' . ($isNew ? '<span style="background:#ff4d4f;color:#fff;padding:2px 6px;border-radius:4px;font-size:10px;">新</span>' : '') . '
                    </a>
                    <div style="margin-top:8px;font-size:13px;color:var(--text-secondary);">
                        <span style="color:var(--primary-color);font-weight:500;">' . htmlspecialchars($thread['author']) . '</span>
                        <span style="margin:0 8px;">·</span>
                        <i class="fas fa-clock"></i> ' . $timeStr . '
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:20px;text-align:center;">
                <div style="padding:8px 16px;background:#f0f0f0;border-radius:8px;">
                    <div style="color:var(--primary-color);font-size:16px;font-weight:600;">' . $thread['replies'] . '</div>
                    <div style="font-size:11px;color:var(--text-placeholder);">回复</div>
                </div>
                <div style="padding:8px 16px;background:#f0f0f0;border-radius:8px;">
                    <div style="color:#666;font-size:16px;font-weight:600;">' . ($thread['views']+1) . '</div>
                    <div style="font-size:11px;color:var(--text-placeholder);">浏览</div>
                </div>
            </div>
        </div>';
    }
    
    echo '</div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2024 企业员工论坛 · 内网系统</p>
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
    
    if (!isset($_SESSION)) { session_start(); }
    $isLogin = isset($_SESSION['uid']);
    $username = $_SESSION['username'] ?? '';
    $avatar = $isLogin ? mb_substr($username, 0, 1) : '?';
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . htmlspecialchars($thread['subject']) . ' - 企业论坛</title>
    <link rel="stylesheet" href="/template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/kindeditor@4.1.10/themes/default/default.css">
    <script src="https://cdn.jsdelivr.net/npm/kindeditor@4.1.10/kindeditor-all-min.js"></script>
    <script src="/template/kindeditor.js"></script>
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <a href="/" style="text-decoration:none;"><i class="fas fa-comments" style="font-size:32px;color:#667eea;"></i></a>
            <h1><a href="/" style="text-decoration:none;color:inherit;">企业员工论坛</a></h1>
        </div>
        <div class="header-nav">
            <a href="/"><i class="fas fa-home"></i> 首页</a>
            <a href="?action=forum&id=' . $thread['fid'] . '"><i class="fas fa-arrow-left"></i> 返回版块</a>
            ' . ($isLogin ? '
                <div class="user-info">
                    <div class="avatar">' . $avatar . '</div>
                    <span>' . htmlspecialchars($username) . '</span>
                </div>
                <a href="?action=logout" style="color:#ff4d4f;"><i class="fas fa-sign-out-alt"></i> 退出</a>
            ' : '<a href="?action=login"><i class="fas fa-sign-in-alt"></i> 登录</a>') . '
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-banner" style="background: linear-gradient(135deg, #667eea, #764ba2);">
            <h2><i class="fas fa-file-alt"></i> ' . htmlspecialchars($thread['subject']) . '</h2>
            <p><i class="fas fa-user"></i> ' . htmlspecialchars($thread['author']) . ' · <i class="fas fa-clock"></i> ' . date('Y-m-d H:i', $thread['dateline']) . ' · <i class="fas fa-eye"></i> ' . ($thread['views']+1) . ' 浏览</p>
        </div>
        
        <div class="forum-section">
            <div class="forum-card" style="display:block;padding:0;">
                <div style="background:#f8f9fa;padding:16px 24px;border-bottom:1px solid #eee;font-weight:600;color:#303133;">
                    <i class="fas fa-comments"></i> 全部回复 (' . mysqli_num_rows($posts) . ')
                </div>';
    
    $floor = 1;
    while ($post = mysqli_fetch_assoc($posts)) {
        $timeAgo = time() - $post['dateline'];
        if ($timeAgo < 3600) $timeStr = intval($timeAgo/60) . '分钟前';
        elseif ($timeAgo < 86400) $timeStr = intval($timeAgo/3600) . '小时前';
        else $timeStr = date('m-d H:i', $post['dateline']);
        
        $floorStyle = $post['first'] == 1 ? 'background:linear-gradient(135deg, #667eea20, #764ba220);border-left:4px solid #667eea;' : 'background:#fff;';
        
        echo '<div style="padding:24px;border-bottom:1px solid #eee;' . $floorStyle . '">
            <div style="display:flex;align-items:flex-start;gap:16px;">
                <div style="text-align:center;min-width:60px;">
                    <div class="avatar" style="width:48px;height:48px;font-size:20px;margin-bottom:8px;">' . mb_substr($post['username'], 0, 1) . '</div>
                    <div style="font-size:12px;color:var(--text-secondary);">' . $timeStr . '</div>
                </div>
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                        <span style="font-weight:600;color:var(--primary-color);font-size:15px;">' . htmlspecialchars($post['username']) . '</span>
                        ' . ($post['first'] == 1 ? '<span style="background:#667eea;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;">楼主</span>' : '<span style="background:#e0e0e0;color:#666;padding:2px 8px;border-radius:4px;font-size:11px;">' . $floor . '楼</span>') . '
                    </div>
                    <div style="line-height:1.8;font-size:15px;min-height:40px;">' . $post['message'] . '</div>';
        
        $attachList = mysqli_query($link, "SELECT * FROM pre_forum_attachment WHERE tid=$tid AND pid=" . $post['pid']);
        if (mysqli_num_rows($attachList) > 0) {
            echo '<div style="margin-top:15px;padding:12px;background:#f8f9fa;border-radius:8px;border:1px dashed #ddd;">';
            echo '<div style="font-size:12px;color:var(--text-secondary);margin-bottom:10px;"><i class="fas fa-paperclip"></i> 附件:</div>';
            while ($att = mysqli_fetch_assoc($attachList)) {
                if ($att['isimage']) {
                    echo '<a href="' . $att['filepath'] . '" target="_blank"><img src="' . $att['filepath'] . '" style="max-width:180px;max-height:120px;margin:5px;border-radius:4px;border:1px solid #ddd;"></a>';
                } else {
                    echo '<a href="' . $att['filepath'] . '" download style="display:inline-flex;align-items:center;margin:5px;padding:8px 12px;background:#fff;border:1px solid #ddd;border-radius:4px;text-decoration:none;color:var(--text-color);font-size:13px;"><i class="fas fa-file-alt"></i> ' . htmlspecialchars($att['filename']) . '</a>';
                }
            }
            echo '</div>';
        }
        
        echo '</div></div></div>';
        $floor++;
    }
    
    echo '</div>
        </div>
        
        <div class="forum-section">
            <div class="section-header">
                <div class="section-title">
                    <div class="icon"><i class="fas fa-edit"></i></div>
                    快速回复
                </div>
            </div>
            <div class="forum-card" style="padding:24px;background:#fafafa;border:2px dashed #ddd;border-radius:12px;">';
    
    if ($isLogin) {
        echo '<form action="?action=submit_reply" method="post">
            <input type="hidden" name="tid" value="' . $tid . '">
            <div class="form-group">
                <textarea name="message" id="reply-editor" required></textarea>
            </div>
            <button type="submit" class="btn-enter"><i class="fas fa-paper-plane"></i> 提交回复</button>
        </form>
        <script>KindEditor.ready(function(K) { K.create("#reply-editor", {width:"100%",height:"150px",items:["bold","italic","underline","forecolor","link","image","emoticons"]}); });</script>';
    } else {
        echo '<div style="text-align:center;padding:30px;">
            <p style="color:var(--text-secondary);margin-bottom:15px;">登录后才能回复</p>
            <a href="?action=login" class="btn-enter"><i class="fas fa-sign-in-alt"></i> 立即登录</a>
        </div>';
    }
    
    echo '</div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2024 企业员工论坛 · 内网系统</p>
    </div>
</body>
</html>';
    
    mysqli_query($link, "UPDATE pre_forum_thread SET views=views+1 WHERE tid=$tid");
}

function loginForm() {
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>登录 - 企业论坛</title>
    <link rel="stylesheet" href="/template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div style="text-align:center;margin-bottom:30px;">
                <i class="fas fa-comments" style="font-size:48px;background: linear-gradient(135deg, #667eea, #764ba2);-webkit-background-clip: text;-webkit-text-fill-color: transparent;"></i>
                <h2>企业员工论坛</h2>
            </div>
            <form action="?action=dologin" method="post">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> 用户名</label>
                    <input type="text" name="username" required placeholder="请输入用户名">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> 密码</label>
                    <input type="password" name="password" required placeholder="请输入密码">
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-sign-in-alt"></i> 登录</button>
            </form>
            <a href="/wechat_oauth.php?action=login">
                <button class="btn-wechat"><i class="fab fa-weixin"></i> 企业微信扫码登录</button>
            </a>
            <div style="text-align:center;margin-top:20px;">
                <a href="/" style="color:#666;text-decoration:none;font-size:13px;"><i class="fas fa-arrow-left"></i> 返回首页</a>
            </div>
        </div>
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
    $username = $_SESSION['username'] ?? '';
    $avatar = mb_substr($username, 0, 1);
    
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>发布新帖 - 企业论坛</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/kindeditor@4.1.10/themes/default/default.css">
    <script src="https://cdn.jsdelivr.net/npm/kindeditor@4.1.10/kindeditor-all-min.js"></script>
    <script src="/template/kindeditor.js"></script>
    <link rel="stylesheet" href="/template/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <a href="/" style="text-decoration:none;"><i class="fas fa-comments" style="font-size:32px;color:#667eea;"></i></a>
            <h1><a href="/" style="text-decoration:none;color:inherit;">企业员工论坛</a></h1>
        </div>
        <div class="header-nav">
            <a href="/"><i class="fas fa-home"></i> 首页</a>
            <div class="user-info">
                <div class="avatar">' . $avatar . '</div>
                <span>' . htmlspecialchars($username) . '</span>
            </div>
            <a href="?action=logout" style="color:#ff4d4f;"><i class="fas fa-sign-out-alt"></i> 退出</a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-banner" style="background: linear-gradient(135deg, #ee0972, #ff6a00);">
            <h2><i class="fas fa-pen"></i> 发布新帖</h2>
            <p>分享你的想法，与同事交流</p>
        </div>
        
        <div class="forum-card" style="padding:30px;">
            <form action="?action=submit_post" method="post" enctype="multipart/form-data">
                <input type="hidden" name="fid" value="' . $fid . '">
                <div class="form-group">
                    <label style="font-size:15px;"><i class="fas fa-heading"></i> 标题</label>
                    <input type="text" name="subject" required placeholder="请输入帖子标题" style="width:100%;padding:14px;font-size:15px;border:2px solid var(--border-color);border-radius:8px;">
                </div>
                <div class="form-group">
                    <label style="font-size:15px;"><i class="fas fa-align-left"></i> 内容</label>
                    <textarea name="message" id="editor" required></textarea>
                </div>
                <div style="margin-bottom:20px;padding:20px;background:#f8f9fa;border-radius:8px;">
                    <div style="font-weight:600;margin-bottom:12px;color:var(--text-color);"><i class="fas fa-paperclip"></i> 上传附件/图片</div>
                    <input type="file" name="attachments[]" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar" style="font-size:14px;">
                    <p style="color:var(--text-placeholder);font-size:12px;margin:8px 0 0 0;">支持jpg、png、gif、pdf、doc、xls、zip、rar格式，单个文件最大10MB</p>
                </div>
                <div style="display:flex;gap:12px;">
                    <button type="submit" class="btn-enter" style="background: linear-gradient(135deg, #ee0972, #ff6a00);border:none;cursor:pointer;"><i class="fas fa-paper-plane"></i> 发布帖子</button>
                    <a href=\"?\" class=\"btn-enter\" style=\"background:#909399;border:none;\"><i class=\"fas fa-times\"></i> 取消</a>
                </div>
            </form>
        </div>
    </div>
    
    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/kindeditor@4.1.10/themes/default/default.css\">
    <script src=\"https://cdn.jsdelivr.net/npm/kindeditor@4.1.10/kindeditor-all-min.js\"></script>
    <script src=\"/template/kindeditor.js\"></script>
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

function submitReply($link) {
    if (!isset($_SESSION)) { session_start(); }
    if (!isset($_SESSION['uid'])) {
        die('请先登录');
    }
    
    $tid = intval($_POST['tid']);
    $message_raw = trim($_POST['message'] ?? '');
    $uid = $_SESSION['uid'];
    $username = mysqli_real_escape_string($link, $_SESSION['username']);
    $now = time();
    
    if (empty($message_raw)) {
        die('回复内容不能为空');
    }
    
    require_once __DIR__ . '/source/class/sensitive_filter.php';
    $filter = new SensitiveWordFilter($link);
    $filter->init();
    $check = $filter->filter($message_raw);
    $isWhitelist = $filter->isWhitelistUser($link, $uid);
    
    $message_esc = mysqli_real_escape_string($link, $message_raw);
    
    if ($isWhitelist) {
        $thread = mysqli_fetch_assoc(mysqli_query($link, "SELECT fid, author FROM pre_forum_thread WHERE tid=$tid"));
        mysqli_query($link, "INSERT INTO pre_forum_post (fid, tid, first, author, authorid, subject, message, dateline) 
            VALUES (" . $thread['fid'] . ", $tid, 0, '$username', $uid, '', '$message_esc', $now)");
        $pid = mysqli_insert_id($link);
        
        mysqli_query($link, "UPDATE pre_forum_thread SET replies=replies+1, lastpost=$now, lastposter='$username' WHERE tid=$tid");
        
        header("Location: ?action=view&tid=$tid");
    } elseif ($check['blocked']) {
        $content_esc = mysqli_real_escape_string($link, $message_raw);
        mysqli_query($link, "INSERT INTO pre_common_audit (uid, username, fid, tid, pid, content, type, status, createtime) 
            VALUES ($uid, '$username', " . $thread['fid'] . ", $tid, 0, '$content_esc', 'reply', 0, $now)");
        
        echo '回复包含敏感词，已提交人工审核。<br><a href="?action=view&tid=' . $tid . '">返回帖子</a>';
    } else {
        $thread = mysqli_fetch_assoc(mysqli_query($link, "SELECT fid FROM pre_forum_thread WHERE tid=$tid"));
        mysqli_query($link, "INSERT INTO pre_forum_post (fid, tid, first, author, authorid, subject, message, dateline) 
            VALUES (" . $thread['fid'] . ", $tid, 0, '$username', $uid, '', '$message_esc', $now)");
        $pid = mysqli_insert_id($link);
        
        mysqli_query($link, "UPDATE pre_forum_thread SET replies=replies+1, lastpost=$now, lastposter='$username' WHERE tid=$tid");
        
        header("Location: ?action=view&tid=$tid");
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