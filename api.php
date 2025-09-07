<?php
// api.php
session_start();
header('Content-Type: application/json; charset=utf-8');
 
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
 
// simple router
$method = $_SERVER['REQUEST_METHOD'];
$input = null;
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
} else {
    $input = $_GET;
}
 
$action = isset($input['action']) ? $input['action'] : null;
 
function jsonResp($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
 
if ($action === 'send' && $method === 'POST') {
    $session_id = $input['session_id'] ?? session_id();
    $message = trim($input['message'] ?? '');
    if ($message === '') jsonResp(['status'=>'error','error'=>'empty_message']);
 
    global $pdo;
    // save user message
    $stmt = $pdo->prepare("INSERT INTO conversations (session_id, role, message) VALUES (?, 'user', ?)");
    $stmt->execute([$session_id, $message]);
 
    // build context: fetch last N messages for session
    $stmt = $pdo->prepare("SELECT role,message FROM conversations WHERE session_id = ? ORDER BY id DESC LIMIT 10");
    $stmt->execute([$session_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rows = array_reverse($rows); // oldest first
 
    // Prepare messages payload for OpenAI
    $messages = [];
    foreach($rows as $r){
        $role = ($r['role'] === 'user') ? 'user' : 'assistant';
        $messages[] = ['role'=>$role, 'content'=>$r['message']];
    }
    // append current user message in case not included
    $messages[] = ['role'=>'user', 'content'=>$message];
 
    // Call OpenAI API (Chat Completions)
    $openai_key = OPENAI_API_KEY;
    if (empty($openai_key) || $openai_key === 'PUT_YOUR_OPENAI_API_KEY_HERE') {
        jsonResp(['status'=>'error','error'=>'no_openai_key','message'=>'Set OPENAI_API_KEY in config.php']);
    }
 
    // You can change model if you have access; defaulting to gpt-3.5-turbo
    $payload = [
        'model' => 'gpt-3.5-turbo',
        'messages' => $messages,
        'max_tokens' => 600,
        'temperature' => 0.6
    ];
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $openai_key
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
 
    if ($err || $httpcode >= 400) {
        jsonResp(['status'=>'error','error'=>'openai_error','details'=>$err ?: $resp]);
    }
 
    $data = json_decode($resp, true);
    if (!isset($data['choices'][0]['message']['content'])) {
        jsonResp(['status'=>'error','error'=>'no_reply','raw'=>$data]);
    }
    $reply = trim($data['choices'][0]['message']['content']);
 
    // save assistant reply
    $stmt = $pdo->prepare("INSERT INTO conversations (session_id, role, message) VALUES (?, 'assistant', ?)");
    $stmt->execute([$session_id, $reply]);
 
    // return reply + a summary of conversations (for sidebar preview)
    // prepare history summary (group by conversation chunks)
    $stmt = $pdo->prepare("SELECT id, role, message FROM conversations WHERE session_id = ? ORDER BY id ASC");
    $stmt->execute([$session_id]);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    // For summary, create small previews every assistant message (simple grouping)
    $summary = [];
    $chunk = [];
    foreach ($all as $row) {
        $chunk[] = ['role'=>$row['role'],'message'=>$row['message']];
        if ($row['role'] === 'assistant') {
            // create preview of last user message
            $preview = '';
            foreach (array_reverse($chunk) as $c) {
                if ($c['role'] === 'user') { $preview = mb_substr($c['message'], 0, 80); break; }
            }
            if ($preview === '') $preview = mb_substr($chunk[0]['message'],0,80);
            $summary[] = ['preview'=>$preview, 'full'=>$chunk];
            $chunk = [];
        }
    }
    // if leftover chunk present
    if (!empty($chunk)) {
        $preview = '';
        foreach (array_reverse($chunk) as $c) {
            if ($c['role'] === 'user') { $preview = mb_substr($c['message'], 0, 80); break; }
        }
        $summary[] = ['preview'=>$preview ?: mb_substr($chunk[0]['message'],0,80), 'full'=>$chunk];
    }
 
    jsonResp(['status'=>'ok','reply'=>$reply,'summary'=>$summary]);
    exit;
}
 
// fetch history summary or last conversation
if ($action === 'fetch_history' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $session_id = $_GET['session_id'] ?? session_id();
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, role, message FROM conversations WHERE session_id = ? ORDER BY id ASC");
    $stmt->execute([$session_id]);
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
    // last conversation array for immediate display
    $lastConversation = [];
    foreach ($all as $row) {
        $lastConversation[] = ['role'=>$row['role'],'message'=>$row['message']];
    }
 
    // summary same as above
    $summary = [];
    $chunk = [];
    foreach ($all as $row) {
        $chunk[] = ['role'=>$row['role'],'message'=>$row['message']];
        if ($row['role'] === 'assistant') {
            $preview = '';
            foreach (array_reverse($chunk) as $c) {
                if ($c['role'] === 'user'){ $preview = mb_substr($c['message'],0,80); break;}
            }
            $summary[] = ['preview'=>$preview ?: mb_substr($chunk[0]['message'],0,80), 'full'=>$chunk];
            $chunk = [];
        }
    }
    if (!empty($chunk)) $summary[] = ['preview'=>mb_substr($chunk[0]['message'],0,80), 'full'=>$chunk];
 
    jsonResp(['status'=>'ok','lastConversation'=>$lastConversation, 'summary'=>$summary]);
    exit;
}
 
// clear history for session
if ($action === 'clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = $input['session_id'] ?? session_id();
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM conversations WHERE session_id = ?");
    $stmt->execute([$session_id]);
    jsonResp(['status'=>'ok']);
    exit;
}
 
jsonResp(['status'=>'error','error'=>'invalid_action']);
 
Syntax highlighting powered by GeSHi
Help Guide | License
