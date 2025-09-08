<?php
// index.php - simple homepage
session_start();
?>
<!doctype html>
<html lang="ur">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My ChatGPT Clone</title>
<style>
  /* Internal CSS - stylish homepage */
  :root{
    --bg1:#0f172a; --bg2:#0b3a53; --card:#0b1220;
    --accent:#7c3aed;
  }
  *{box-sizing:border-box}
  body{
    margin:0; font-family:Inter, system-ui, sans-serif;
    background: linear-gradient(135deg,var(--bg1),var(--bg2));
    color:#e6eef8; min-height:100vh; display:grid; place-items:center;
  }
  .wrap{
    width:min(980px,95%); background:linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02));
    padding:32px; border-radius:16px; box-shadow: 0 10px 30px rgba(2,6,23,0.6);
    display:flex; gap:24px; align-items:center;
  }
  .left{flex:1}
  h1{margin:0 0 8px 0; font-size:30px; letter-spacing:0.4px}
  p{margin:0 0 16px 0; opacity:0.9}
  .btn{
    display:inline-block; padding:12px 20px; border-radius:12px; cursor:pointer;
    background:linear-gradient(90deg, var(--accent), #ff6b6b); border:none; color:white; font-weight:600;
    box-shadow: 0 8px 24px rgba(124,58,237,0.18);
  }
  .right{
    width:260px; text-align:center;
  }
  .card-preview{
    background:rgba(255,255,255,0.02); padding:14px; border-radius:12px; font-size:13px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
  }
  footer{margin-top:16px; font-size:12px; opacity:0.7}
  @media(max-width:700px){
    .wrap{flex-direction:column; align-items:stretch}
  }
</style>
</head>
<body>
  <div class="wrap">
    <div class="left">
      <h1>ChatGPT Clone</h1>
      <p>AI سے پوچھیں، جواب فوراً لیں — محفوظ conversation history کے ساتھ۔</p>
      <button class="btn" id="goChat">شروع کریں</button>
      <div style="margin-top:10px"><small>نوٹ: OpenAI API key server میں لگائیں (config.php)</small></div>
      <footer>Built with PHP, MySQL, JS — stylish UI ✅</footer>
    </div>
    <div class="right">
      <div class="card-preview">
        <strong>Preview</strong>
        <div style="margin-top:10px; text-align:left; font-size:13px; line-height:1.5;">
          <div><strong>User:</strong> سلام، آج موسم کیسا ہے؟</div>
          <div style="margin-top:6px"><strong>Assistant:</strong> سلام! آپ کا شہر بتائیں، میں چیک کر کے بتاتا ہوں۔</div>
        </div>
      </div>
    </div>
  </div>
 
<script>
document.getElementById('goChat').addEventListener('click', function(){
  // JavaScript redirection (user requested no PHP redirection)
  window.location.href = 'chat.php';
});
</script>
</body>
</html>
 
