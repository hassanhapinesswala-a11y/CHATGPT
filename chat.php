<?php
// chat.php
session_start();
if (!isset($_SESSION['chat_session_id'])) {
    // generate a simple random id per visitor
    $_SESSION['chat_session_id'] = bin2hex(random_bytes(16));
}
$session_id = $_SESSION['chat_session_id'];
?>
<!doctype html>
<html lang="ur">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Chat — ChatGPT Clone</title>
<style>
  /* internal CSS: modern chat UI */
  :root{--bg:#071224; --panel:#0e1b2b; --glass: rgba(255,255,255,0.03); --accent:#06b6d4}
  *{box-sizing:border-box}
  body{margin:0; font-family:Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue"; background: linear-gradient(180deg,#031023,#06142b); color:#e7f0fb}
  .container{max-width:980px; margin:28px auto; padding:18px;}
  .chat-wrap{
    background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
    border-radius:16px; padding:18px; box-shadow:0 12px 40px rgba(2,6,23,0.6);
    display:grid; grid-template-columns:320px 1fr; gap:18px;
  }
  .sidebar{
    background:var(--panel); border-radius:12px; padding:12px; min-height:420px;
  }
  .sidebar h3{margin:6px 0 12px 0; font-size:16px}
  .start-btn{display:block; padding:10px; background:var(--glass); border-radius:10px; cursor:pointer; text-align:center; margin-bottom:10px}
  .history{max-height:340px; overflow:auto; padding-right:6px}
  .chat-panel{background:transparent; border-radius:12px; padding:12px; min-height:420px; display:flex; flex-direction:column}
  .messages{flex:1; overflow:auto; padding:6px; margin-bottom:8px}
  .msg{margin:8px 0; display:flex}
  .msg.user{justify-content:flex-end}
  .bubble{
    max-width:76%; padding:12px 14px; border-radius:14px; line-height:1.4;
    box-shadow: 0 6px 18px rgba(2,6,23,0.5);
  }
  .bubble.user{background:linear-gradient(90deg,#0ea5a9,#06b6d4); color:#012; border-bottom-right-radius:4px}
  .bubble.assistant{background:#071428; color:#dbeafe; border-bottom-left-radius:4px}
  .input-area{display:flex; gap:8px; align-items:center}
  textarea{flex:1; min-height:54px; resize:none; padding:10px; border-radius:12px; border:1px solid rgba(255,255,255,0.04); background:transparent; color:inherit}
  .send-btn{padding:12px 16px; border-radius:12px; background:linear-gradient(90deg,#7c3aed,#ef4444); border:none; color:white; cursor:pointer}
  .meta{font-size:12px; opacity:0.7; margin-bottom:8px}
  .loader{display:inline-block; width:18px; height:18px; border-radius:50%; border:3px solid rgba(255,255,255,0.08); border-top-color:#fff; animation:spin 1s linear infinite; vertical-align:middle}
  @keyframes spin{to{transform:rotate(360deg)}}
  @media(max-width:900px){
    .chat-wrap{grid-template-columns:1fr; }
    .sidebar{order:2}
  }
</style>
</head>
<body>
<div class="container">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px">
    <div><strong>ChatGPT Clone</strong> — session: <small><?php echo htmlspecialchars($session_id); ?></small></div>
    <div>
      <button onclick="goHome()" style="padding:8px 12px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.06);color:inherit;cursor:pointer">Home</button>
      <button onclick="clearHistory()" style="padding:8px 12px;border-radius:8px;background:transparent;border:1px solid rgba(255,255,255,0.06);color:inherit;cursor:pointer;margin-left:8px">Clear</button>
    </div>
  </div>
 
  <div class="chat-wrap">
    <div class="sidebar">
      <h3>Conversation History</h3>
      <div class="history" id="historyList">
        <!-- history items loaded by JS -->
      </div>
      <div style="margin-top:10px; font-size:12px; opacity:0.8">Note: All saved locally on server DB per session.</div>
    </div>
 
    <div class="chat-panel">
      <div class="meta">Ask anything — AI replies using OpenAI.</div>
      <div class="messages" id="messages"></div>
 
      <div class="input-area">
        <textarea id="messageInput" placeholder="سوال لکھیں... (مثلاً: 'Python کیا ہے؟')"></textarea>
        <button class="send-btn" id="sendBtn">Send</button>
      </div>
    </div>
  </div>
</div>
 
<script>
const sessionId = "<?php echo addslashes($session_id); ?>";
const messagesEl = document.getElementById('messages');
const historyEl = document.getElementById('historyList');
const input = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
 
sendBtn.addEventListener('click', sendMessage);
input.addEventListener('keydown', function(e){
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});
 
function goHome(){ window.location.href = 'index.php'; }
 
function appendMessage(role, text){
  const div = document.createElement('div');
  div.className = 'msg ' + (role === 'user' ? 'user' : 'assistant');
  const b = document.createElement('div');
  b.className = 'bubble ' + (role === 'user' ? 'user' : 'assistant');
  b.textContent = text;
  div.appendChild(b);
  messagesEl.appendChild(div);
  messagesEl.scrollTop = messagesEl.scrollHeight;
}
 
function showLoader(){
  const div = document.createElement('div');
  div.className = 'msg assistant loading';
  div.id = 'loadingMsg';
  const b = document.createElement('div');
  b.className = 'bubble assistant';
  b.innerHTML = '<span class="loader"></span> typing...';
  div.appendChild(b);
  messagesEl.appendChild(div);
  messagesEl.scrollTop = messagesEl.scrollHeight;
}
 
function removeLoader(){
  const el = document.getElementById('loadingMsg');
  if(el) el.remove();
}
 
function updateHistoryUI(items){
  historyEl.innerHTML = '';
  items.forEach(it => {
    const d = document.createElement('div');
    d.style.padding = '8px'; d.style.borderRadius='8px'; d.style.background='rgba(255,255,255,0.01)';
    d.style.marginBottom='8px'; d.style.cursor='pointer';
    d.textContent = it.preview;
    d.title = it.preview;
    d.onclick = () => {
      messagesEl.innerHTML = '';
      it.full.forEach(m => appendMessage(m.role, m.message));
    };
    historyEl.appendChild(d);
  });
}
 
// load history on open
fetch('api.php?action=fetch_history&session_id=' + encodeURIComponent(sessionId))
  .then(r => r.json())
  .then(data => {
    if(data.status === 'ok'){
      // show last conversation messages
      if(data.lastConversation){
        const conv = data.lastConversation;
        messagesEl.innerHTML = '';
        conv.forEach(m => appendMessage(m.role, m.message));
      }
      updateHistoryUI(data.summary || []);
    } else {
      console.error(data);
    }
  });
 
function sendMessage(){
  const text = input.value.trim();
  if(!text) return;
  appendMessage('user', text);
  input.value = '';
  showLoader();
 
  fetch('api.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({action:'send', session_id: sessionId, message: text})
  })
  .then(r => r.json())
  .then(data => {
    removeLoader();
    if(data.status === 'ok'){
      appendMessage('assistant', data.reply);
      updateHistoryUI(data.summary || []);
    } else {
      appendMessage('assistant', 'Error: ' + (data.error || 'Unknown error'));
    }
  })
  .catch(err => {
    removeLoader();
    appendMessage('assistant', 'Network error. ' + err.message);
  });
}
 
function clearHistory(){
  if(!confirm('Clear conversation history for this session?')) return;
  fetch('api.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({action:'clear', session_id: sessionId})
  })
  .then(r=>r.json()).then(d=>{
    if(d.status==='ok'){
      messagesEl.innerHTML='';
      updateHistoryUI([]);
      alert('History cleared.');
    } else alert('Failed to clear.');
  });
}
</script>
</body>
</html>
 
