<?php
session_start();
if (!empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In — Civilization Simulator</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600&family=Crimson+Text:ital,wght@0,400;0,600;1,400;1,600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg:        #0c0b09;
      --bg-card:   #181613;
      --bg-raised: #1e1b17;
      --border:    rgba(201,160,60,0.14);
      --border-hi: rgba(201,160,60,0.5);
      --gold:      #c9a03c;
      --gold-hi:   #e2b84a;
      --gold-dim:  #7a6120;
      --text:      #ede5d0;
      --text-2:    #9e9078;
      --text-3:    #5a5040;
      --red:       #c05a40;
      --green:     #5a9a5a;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { background: var(--bg); }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Crimson Text', Georgia, serif;
      font-size: 16px;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      -webkit-font-smoothing: antialiased;
    }

    /* Animated grain overlay */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
      pointer-events: none;
      z-index: 999;
      opacity: 0.35;
    }

    /* Radial glow behind card */
    body::after {
      content: '';
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 700px;
      height: 500px;
      background: radial-gradient(ellipse, rgba(201,160,60,0.06) 0%, transparent 70%);
      pointer-events: none;
      z-index: 0;
    }

    .page-wrap {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 440px;
      animation: fadeUp 0.6s cubic-bezier(0.22,1,0.36,1) both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ─ Brand mark ─ */
    .brand {
      text-align: center;
      margin-bottom: 36px;
    }
    .brand-eyebrow {
      font-family: 'JetBrains Mono', monospace;
      font-size: 9px;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--gold-dim);
      margin-bottom: 10px;
    }
    .brand-crest {
      font-size: 32px;
      line-height: 1;
      display: block;
      margin-bottom: 10px;
      filter: drop-shadow(0 0 12px rgba(201,160,60,0.3));
    }
    .brand h1 {
      font-family: 'Cinzel', Georgia, serif;
      font-weight: 400;
      font-size: 26px;
      color: var(--gold);
      letter-spacing: 0.04em;
      line-height: 1.1;
      margin-bottom: 6px;
    }
    .brand-sub {
      font-size: 15px;
      color: var(--text-3);
      font-style: italic;
    }

    /* ─ Card ─ */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 40px 40px 36px;
      position: relative;
      overflow: hidden;
    }
    .card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, rgba(201,160,60,0.4), transparent);
    }

    .card-title {
      font-family: 'Cinzel', Georgia, serif;
      font-size: 20px;
      font-weight: 400;
      color: var(--text);
      margin-bottom: 6px;
      letter-spacing: 0.02em;
    }
    .card-subtitle {
      font-size: 14px;
      color: var(--text-3);
      font-style: italic;
      margin-bottom: 28px;
    }

    /* ─ Form ─ */
    .form-group { margin-bottom: 20px; }
    .form-group label {
      display: block;
      font-family: 'JetBrains Mono', monospace;
      font-size: 9px;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--text-3);
      margin-bottom: 8px;
    }
    .form-group input {
      width: 100%;
      background: var(--bg-raised);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 13px 16px;
      color: var(--text);
      font-family: 'Crimson Text', Georgia, serif;
      font-size: 16px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-group input:focus {
      border-color: var(--border-hi);
      box-shadow: 0 0 0 3px rgba(201,160,60,0.07);
    }
    .form-group input::placeholder { color: var(--text-3); }

    /* ─ Error ─ */
    .error-msg {
      background: rgba(192,90,64,0.1);
      border: 1px solid rgba(192,90,64,0.25);
      border-radius: 7px;
      color: var(--red);
      font-size: 13px;
      padding: 10px 14px;
      margin-bottom: 18px;
      display: none;
    }
    .error-msg.show { display: block; }

    /* ─ Button ─ */
    .btn-submit {
      width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, var(--gold) 0%, var(--gold-hi) 100%);
      color: #0c0b09;
      border: none;
      border-radius: 9px;
      font-family: 'Cinzel', Georgia, serif;
      font-size: 14px;
      letter-spacing: 0.08em;
      cursor: pointer;
      transition: opacity 0.2s, transform 0.1s;
      position: relative;
      overflow: hidden;
      margin-top: 4px;
    }
    .btn-submit::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.14) 0%, transparent 60%);
    }
    .btn-submit:hover { opacity: 0.9; }
    .btn-submit:active { transform: scale(0.99); }
    .btn-submit:disabled { opacity: 0.35; cursor: not-allowed; }

    /* ─ Divider & footer link ─ */
    .divider {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 24px 0 20px;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }
    .divider span {
      font-family: 'JetBrains Mono', monospace;
      font-size: 9px;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--text-3);
    }

    .alt-link {
      text-align: center;
      font-size: 14px;
      color: var(--text-3);
    }
    .alt-link a {
      color: var(--gold);
      text-decoration: none;
      font-style: italic;
      transition: color 0.18s;
    }
    .alt-link a:hover { color: var(--gold-hi); }

    /* Loading spinner inside button */
    @keyframes spin { to { transform: rotate(360deg); } }
    .btn-spinner {
      display: inline-block;
      width: 14px; height: 14px;
      border: 1.5px solid rgba(12,11,9,0.3);
      border-top-color: #0c0b09;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      vertical-align: middle;
      margin-right: 8px;
    }

    /* Success flash */
    .success-flash {
      background: rgba(90,154,90,0.1);
      border: 1px solid rgba(90,154,90,0.25);
      border-radius: 7px;
      color: var(--green);
      font-size: 13px;
      padding: 10px 14px;
      margin-bottom: 18px;
      display: none;
    }
    .success-flash.show { display: block; }
  </style>
</head>
<body>

<div class="page-wrap">

  <!-- Brand -->
  <div class="brand">
    <div class="brand-eyebrow">Civilization Simulator</div>
    <span class="brand-crest">⚔</span>
    <h1>Enter the Annals</h1>
    <p class="brand-sub">Your dynasty awaits its place in history</p>
  </div>

  <!-- Card -->
  <div class="card">
    <div class="card-title">Sign In</div>
    <div class="card-subtitle">Return to your realm</div>

    <div class="error-msg" id="error-msg"></div>
    <div class="success-flash" id="success-msg"></div>

    <div class="form-group">
      <label for="username">Username</label>
      <input type="text" id="username" placeholder="Your username" autocomplete="username" />
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" placeholder="Your password" autocomplete="current-password" />
    </div>

    <button class="btn-submit" id="login-btn">Enter the Realm</button>

    <div class="divider"><span>New to the annals?</span></div>
    <div class="alt-link">
      <a href="register.php">Found your dynasty →</a>
    </div>
  </div>

</div>

<script>
const errorEl   = document.getElementById('error-msg');
const successEl = document.getElementById('success-msg');
const loginBtn  = document.getElementById('login-btn');

function showError(msg) {
  errorEl.textContent = msg;
  errorEl.classList.add('show');
  successEl.classList.remove('show');
}
function showSuccess(msg) {
  successEl.textContent = msg;
  successEl.classList.add('show');
  errorEl.classList.remove('show');
}
function setLoading(loading) {
  loginBtn.disabled = loading;
  loginBtn.innerHTML = loading
    ? '<span class="btn-spinner"></span>Consulting the scrolls…'
    : 'Enter the Realm';
}

async function doLogin() {
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;
  errorEl.classList.remove('show');

  if (!username || !password) { showError('Please fill in all fields.'); return; }

  setLoading(true);
  try {
    const fd = new FormData();
    fd.append('action', 'login');
    fd.append('username', username);
    fd.append('password', password);

    const res  = await fetch('auth.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
      showSuccess('Welcome back, ' + data.username + '! Entering realm…');
      setTimeout(() => { window.location.href = 'index.php'; }, 800);
    } else {
      showError(data.error || 'Login failed.');
      setLoading(false);
    }
  } catch (e) {
    showError('Could not reach the server. Check your connection.');
    setLoading(false);
  }
}

loginBtn.addEventListener('click', doLogin);
document.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
</script>
</body>
</html>