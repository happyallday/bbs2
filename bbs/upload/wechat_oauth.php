<?php
/**
 * 企业微信OAuth2登录模块
 * M2: 企业微信OAuth2登录
 */

$config = include __DIR__ . '/config/wechat_config.php';

$corp_id = $config['corp_id'];
$agent_id = $config['agent_id'];
$corp_secret = $config['corp_secret'];
$callback_url = $config['callback_url'];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        wechatLogin();
        break;
    case 'callback':
        wechatCallback();
        break;
    default:
        showLoginPage();
}

function showLoginPage() {
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>企业微信登录</title>
    <style>
        body { font-family: "Microsoft YaHei", Arial, sans-serif; background: #f5f5f5; padding: 50px; text-align: center; }
        .btn { display: inline-block; padding: 15px 40px; background: #1aad19; color: #fff; text-decoration: none; border-radius: 5px; font-size: 18px; }
        .btn:hover { background: #168d13; }
    </style>
</head>
<body>
    <h2>企业微信扫码登录</h2>
    <p>请点击下方按钮使用企业微信扫码登录</p>
    <a href="?action=login" class="btn">企业微信登录</a>
</body>
</html>';
}

function wechatLogin() {
    global $corp_id, $agent_id, $callback_url;
    
    $redirect_uri = urlencode($callback_url . '?action=callback');
    $state = md5(uniqid(rand(), true));
    
    $_SESSION['wechat_state'] = $state;
    
    $auth_url = "https://open.weixin.qq.com/connect/oauth2/authorize?" .
        "appid={$corp_id}&" .
        "redirect_uri={$redirect_uri}&" .
        "response_type=code&" .
        "scope=snsapi_base&" .
        "state={$state}#wechat_redirect";
    
    header("Location: {$auth_url}");
    exit;
}

function wechatCallback() {
    global $corp_id, $corp_secret, $agent_id;
    
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    
    if (empty($code)) {
        die('授权失败：缺少code参数');
    }
    
    if ($state !== $_SESSION['wechat_state']) {
        die('授权失败：state不匹配');
    }
    
    $token_url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?" .
        "corpid={$corp_id}&" .
        "corpsecret={$corp_secret}";
    
    $token_json = file_get_contents($token_url);
    $token_data = json_decode($token_json, true);
    
    if (empty($token_data['access_token'])) {
        die('获取access_token失败');
    }
    
    $access_token = $token_data['access_token'];
    
    $userinfo_url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?" .
        "access_token={$access_token}&" .
        "code={$code}&" .
        "agentid={$agent_id}";
    
    $userinfo_json = file_get_contents($userinfo_url);
    $userinfo = json_decode($userinfo_json, true);
    
    if (empty($userinfo['UserId'])) {
        die('获取用户信息失败');
    }
    
    $userid = $userinfo['UserId'];
    
    loginWithWechat($userid);
}

function loginWithWechat($userid) {
    $dbhost = 'localhost';
    $dbuser = 'bbs';
    $dbpw = 'bbs123456';
    $dbname = 'bbs';
    
    $link = mysqli_connect($dbhost, $dbuser, $dbpw, $dbname);
    mysqli_query($link, "SET NAMES utf8mb4");
    
    $userid_safe = mysqli_real_escape_string($link, $userid);
    $result = mysqli_query($link, "SELECT * FROM pre_common_member WHERE username='$userid_safe'");
    $user = mysqli_fetch_assoc($result);
    
    if (!$user) {
        $now = time();
        mysqli_query($link, "INSERT INTO pre_common_member (username, password, email, regdate, groupid) 
            VALUES ('$userid', '', '', $now, 10)");
        $uid = mysqli_insert_id($link);
        mysqli_query($link, "INSERT INTO pre_common_member_count (uid) VALUES ($uid)");
        $user = ['uid' => $uid, 'username' => $userid];
    }
    
    session_start();
    $_SESSION['uid'] = $user['uid'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['login_type'] = 'wechat';
    
    header('Location: /');
    exit;
}