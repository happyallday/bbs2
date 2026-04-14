<?php
/**
 * 敏感词过滤模块 (DFA算法)
 * M3: 敏感词DFA过滤 + 人工审核 + 白名单用户管理
 */

class SensitiveWordFilter {
    private $trieTree = [];
    private $dbLink = null;
    
    public function __construct($dbLink = null) {
        $this->dbLink = $dbLink;
    }
    
    public function init() {
        $words = $this->getWords();
        $this->buildTrieTree($words);
    }
    
    private function getWords() {
        if (!$this->dbLink) {
            return $this->getDefaultWords();
        }
        
        $result = mysqli_query($this->dbLink, "SELECT word, replacement FROM pre_common_sensitive WHERE level>=1");
        $words = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $words[$row['word']] = $row['replacement'];
        }
        
        if (empty($words)) {
            return $this->getDefaultWords();
        }
        
        return $words;
    }
    
    private function getDefaultWords() {
        return [
            '政治敏感' => '***',
            '色情' => '***',
            '赌博' => '***',
            '暴力' => '***',
            '诈骗' => '***',
        ];
    }
    
    private function buildTrieTree($words) {
        $this->trieTree = [];
        foreach ($words as $word => $replacement) {
            $chars = $this->mbStringToArray($word);
            $node = &$this->trieTree;
            
            foreach ($chars as $char) {
                if (!isset($node[$char])) {
                    $node[$char] = [];
                }
                $node = &$node[$char];
            }
            
            $node['#'] = $replacement;
        }
    }
    
    private function mbStringToArray($string) {
        $chars = [];
        $len = mb_strlen($string, 'utf-8');
        for ($i = 0; $i < $len; $i++) {
            $chars[] = mb_substr($string, $i, 1, 'utf-8');
        }
        return $chars;
    }
    
    public function filter($content) {
        if (empty($this->trieTree)) {
            $this->init();
        }
        
        $result = $this->check($content);
        
        if ($result['found']) {
            return [
                'blocked' => true,
                'words' => $result['words'],
                'filtered' => $result['filtered']
            ];
        }
        
        return ['blocked' => false];
    }
    
    public function check($content) {
        $chars = $this->mbStringToArray($content);
        $len = count($chars);
        $foundWords = [];
        
        for ($i = 0; $i < $len; $i++) {
            $node = $this->trieTree;
            $matchWord = '';
            $matchReplacement = '';
            
            for ($j = $i; $j < $len; $j++) {
                $char = $chars[$j];
                
                if (isset($node[$char])) {
                    $node = $node[$char];
                    $matchWord .= $char;
                    
                    if (isset($node['#'])) {
                        $matchReplacement = $node['#'];
                    }
                } else {
                    break;
                }
                
                if (!empty($matchReplacement)) {
                    $foundWords[] = [
                        'word' => $matchWord,
                        'replacement' => $matchReplacement
                    ];
                    break;
                }
            }
        }
        
        if (empty($foundWords)) {
            return ['found' => false];
        }
        
        $filtered = $content;
        foreach ($foundWords as $fw) {
            $filtered = str_replace($fw['word'], $fw['replacement'], $filtered);
        }
        
        return [
            'found' => true,
            'words' => $foundWords,
            'filtered' => $filtered
        ];
    }
    
    public function isWhitelistUser($dbLink, $uid) {
        $uid = intval($uid);
        $result = mysqli_query($dbLink, "SELECT id FROM pre_common_whitelist WHERE uid=$uid");
        return mysqli_num_rows($result) > 0;
    }
}

function SensitiveFilter_addWord($dbLink, $word, $replacement = '***', $level = 1) {
    $word = mysqli_real_escape_string($dbLink, trim($word));
    $replacement = mysqli_real_escape_string($dbLink, $replacement);
    $level = intval($level);
    $now = time();
    
    $result = mysqli_query($dbLink, "INSERT INTO pre_common_sensitive (word, replacement, level, addtime) 
        VALUES ('$word', '$replacement', $level, $now)");
    
    return $result;
}

function SensitiveFilter_getWords($dbLink) {
    $result = mysqli_query($dbLink, "SELECT * FROM pre_common_sensitive ORDER BY id DESC");
    $words = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $words[] = $row;
    }
    return $words;
}

function SensitiveFilter_deleteWord($dbLink, $id) {
    $id = intval($id);
    return mysqli_query($dbLink, "DELETE FROM pre_common_sensitive WHERE id=$id");
}

function SensitiveFilter_addWhitelist($dbLink, $uid, $username, $note = '') {
    $uid = intval($uid);
    $username = mysqli_real_escape_string($dbLink, $username);
    $note = mysqli_real_escape_string($dbLink, $note);
    $now = time();
    
    $result = mysqli_query($dbLink, "INSERT INTO pre_common_whitelist (uid, username, addtime, note) 
        VALUES ($uid, '$username', $now, '$note')");
    
    return $result;
}

function SensitiveFilter_removeWhitelist($dbLink, $id) {
    $id = intval($id);
    return mysqli_query($dbLink, "DELETE FROM pre_common_whitelist WHERE id=$id");
}

function SensitiveFilter_getWhitelist($dbLink) {
    $result = mysqli_query($dbLink, "SELECT * FROM pre_common_whitelist ORDER BY id DESC");
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    return $users;
}

function SensitiveFilter_addAudit($dbLink, $uid, $username, $fid, $tid, $pid, $content, $type = 'post') {
    $uid = intval($uid);
    $username = mysqli_real_escape_string($dbLink, $username);
    $content = mysqli_real_escape_string($dbLink, $content);
    $type = mysqli_real_escape_string($dbLink, $type);
    $fid = intval($fid);
    $tid = intval($tid);
    $pid = intval($pid);
    $now = time();
    
    $result = mysqli_query($dbLink, "INSERT INTO pre_common_audit 
        (uid, username, fid, tid, pid, content, type, status, createtime) 
        VALUES ($uid, '$username', $fid, $tid, $pid, '$content', '$type', 0, $now)");
    
    return $result;
}

function SensitiveFilter_approvePost($dbLink, $tid) {
    $tid = intval($tid);
    mysqli_query($dbLink, "UPDATE pre_forum_thread SET displayorder=0 WHERE tid=$tid");
    mysqli_query($dbLink, "UPDATE pre_common_audit SET status=1, audittime=" . time() . " WHERE tid=$tid AND type='post'");
}

function SensitiveFilter_rejectPost($dbLink, $tid, $reason = '') {
    $tid = intval($tid);
    $reason = mysqli_real_escape_string($dbLink, $reason);
    mysqli_query($dbLink, "DELETE FROM pre_forum_thread WHERE tid=$tid");
    mysqli_query($dbLink, "DELETE FROM pre_forum_post WHERE tid=$tid");
    mysqli_query($dbLink, "UPDATE pre_common_audit SET status=2, audittime=" . time() . ", reason='$reason' WHERE tid=$tid AND type='post'");
}

function SensitiveFilter_getPending($dbLink) {
    $result = mysqli_query($dbLink, "SELECT * FROM pre_common_audit WHERE status=0 ORDER BY createtime DESC");
    $audits = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $audits[] = $row;
    }
    return $audits;
}

function SensitiveFilter_getAudited($dbLink, $limit = 50) {
    $limit = intval($limit);
    $result = mysqli_query($dbLink, "SELECT * FROM pre_common_audit WHERE status>0 ORDER BY audittime DESC LIMIT $limit");
    $audits = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $audits[] = $row;
    }
    return $audits;
}