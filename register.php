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
  <title>Register — Civilization Simulator</title>
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
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
      pointer-events: none;
      z-index: 999;
      opacity: 0.35;
    }
    body::after {
      content: '';
      position: fixed;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      width: 700px; height: 600px;
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
    .form-group .hint-text {
      display: block;
      font-size: 12px;
      color: var(--text-3);
      font-style: italic;
      margin-top: 5px;
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
    .form-group input.valid   { border-color: rgba(90,154,90,0.5); }
    .form-group input.invalid { border-color: rgba(192,90,64,0.5); }

    /* Password strength bar */
    .strength-bar {
      height: 3px;
      border-radius: 2px;
      background: var(--bg-raised);
      margin-top: 7px;
      overflow: hidden;
    }
    .strength-fill {
      height: 100%;
      border-radius: 2px;
      width: 0%;
      transition: width 0.3s, background 0.3s;
    }

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

    .divider {
      display: flex;
      align-items: center;
      gap: 14px;
      margin: 24px 0 20px;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px; background: var(--border);
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
  </style>
</head>
<body>

<div class="page-wrap">

  <div class="brand">
    <div class="brand-eyebrow">Civilization Simulator</div>
    <span class="brand-crest">📜</span>
    <h1>Found Your Dynasty</h1>
    <p class="brand-sub">Inscribe your name into the annals of history</p>
  </div>

  <div class="card">
    <div class="card-title">Create Account</div>
    <div class="card-subtitle">Your legacy begins here</div>

    <div class="error-msg" id="error-msg"></div>
    <div class="success-flash" id="success-msg"></div>

    <div class="form-group">
      <label for="username">Choose a Name</label>
      <input type="text" id="username" placeholder="Letters, numbers, _ or -" autocomplete="username" maxlength="40" />
      <span class="hint-text">3–40 characters. This is your dynasty name.</span>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" placeholder="At least 6 characters" autocomplete="new-password" />
      <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
    </div>
    <div class="form-group">
      <label for="confirm">Confirm Password</label>
      <input type="password" id="confirm" placeholder="Repeat your password" autocomplete="new-password" />
    </div>

    <button class="btn-submit" id="reg-btn">Found Your Dynasty</button>

    <div class="divider"><span>Already have a realm?</span></div>
    <div class="alt-link">
      <a href="login.php">← Return to sign in</a>
    </div>
  </div>

</div>

<script>
const errorEl    = document.getElementById('error-msg');
const successEl  = document.getElementById('success-msg');
const regBtn     = document.getElementById('reg-btn');
const passInput  = document.getElementById('password');
const confInput  = document.getElementById('confirm');
const strengthEl = document.getElementById('strength-fill');

// Password strength indicator
passInput.addEventListener('input', () => {
  const v = passInput.value;
  let score = 0;
  if (v.length >= 6)  score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^a-zA-Z0-9]/.test(v)) score++;
  const pct   = (score / 5) * 100;
  const color = score <= 1 ? '#b03a3a' : score <= 3 ? '#b88830' : '#5a9a5a';
  strengthEl.style.width    = pct + '%';
  strengthEl.style.background = color;
});

// Confirm match indicator
confInput.addEventListener('input', () => {
  if (!confInput.value) { confInput.className = ''; return; }
  confInput.className = confInput.value === passInput.value ? 'valid' : 'invalid';
});

function showError(msg)   { errorEl.textContent = msg; errorEl.classList.add('show'); successEl.classList.remove('show'); }
function showSuccess(msg) { successEl.textContent = msg; successEl.classList.add('show'); errorEl.classList.remove('show'); }
function setLoading(on) {
  regBtn.disabled  = on;
  regBtn.innerHTML = on ? '<span class="btn-spinner"></span>Inscribing your name…' : 'Found Your Dynasty';
}

async function doRegister() {
  const username = document.getElementById('username').value.trim();
  const password = passInput.value;
  const confirm  = confInput.value;
  errorEl.classList.remove('show');

  if (!username || !password || !confirm) { showError('Please fill in all fields.'); return; }
  if (!/^[a-zA-Z0-9_\-]{3,40}$/.test(username)) { showError('Username must be 3–40 characters: letters, numbers, _ or -'); return; }
  if (password.length < 6) { showError('Password must be at least 6 characters.'); return; }
  if (password !== confirm) { showError('Passwords do not match.'); return; }

  setLoading(true);
  try {
    const fd = new FormData();
    fd.append('action',   'register');
    fd.append('username', username);
    fd.append('password', password);
    fd.append('confirm',  confirm);

    const res  = await fetch('auth.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
      showSuccess('Dynasty founded! Welcome, ' + data.username + '. Entering realm…');
      setTimeout(() => { window.location.href = 'index.php'; }, 900);
    } else {
      showError(data.error || 'Registration failed.');
      setLoading(false);
    }
  } catch (e) {
    showError('Could not reach the server. Check your connection.');
    setLoading(false);
  }
}

regBtn.addEventListener('click', doRegister);
document.addEventListener('keydown', e => { if (e.key === 'Enter') doRegister(); });
</script>
</body>
</html>