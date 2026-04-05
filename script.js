
'use strict';

// ── Outcome display helpers ───────────────────────
const OUTCOME_LABELS = {
  crushing_victory:  { label:'Crushing Victory',  color:'var(--green)' },
  decisive_victory:  { label:'Decisive Victory',  color:'var(--green)' },
  victory:           { label:'Victory',            color:'var(--green)' },
  skirmish:          { label:'Skirmish',           color:'var(--amber)' },
  defeat:            { label:'Defeat',             color:'var(--red)' },
  decisive_defeat:   { label:'Decisive Defeat',   color:'var(--red)' },
  crushing_defeat:   { label:'Crushing Defeat',   color:'var(--red)' },
};
// ── Hall of Records ───────────────────────────────
async function loadHall(mine = false) {
  const el = document.getElementById('hall-list');
  if (!el) return;
  el.innerHTML = '<div class="mp-loading"><div class="loading-ring"></div><span>Loading…</span></div>';
  try {
    const res  = await fetch(`world.php?action=hall${mine ? '&mine=1' : ''}`);
    const data = await res.json();
    if (!data.ok || !data.kingdoms.length) {
      el.innerHTML = '<div class="mp-loading">No kingdoms found.</div>'; return;
    }
    el.innerHTML = data.kingdoms.map(k => {
      const statusColor = k.status === 'eliminated' ? 'var(--red)' : 'var(--green)';
      return `<div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:16px 18px;margin-bottom:10px;display:flex;align-items:center;gap:14px;">
        <div style="width:14px;height:14px;border-radius:50%;background:${k.color};flex-shrink:0"></div>
        <div style="flex:1;min-width:0">
          <div style="font-family:var(--font-display);font-size:16px;color:var(--text)">${k.name}</div>
          <div style="font-size:12px;color:var(--text-3);font-style:italic;margin-top:2px">${k.username} · ${k.region} · ${k.government}</div>
        </div>
        <div style="display:flex;gap:18px;flex-shrink:0;text-align:center">
          <div><div style="font-family:var(--font-display);font-size:17px;color:var(--gold)">${k.prosperity}</div><div style="font-family:var(--font-mono);font-size:8px;color:var(--text-3);text-transform:uppercase;letter-spacing:0.08em">Prosperity</div></div>
          <div><div style="font-family:var(--font-display);font-size:17px;color:var(--teal)">${k.military}</div><div style="font-family:var(--font-mono);font-size:8px;color:var(--text-3);text-transform:uppercase;letter-spacing:0.08em">Military</div></div>
          <div><div style="font-family:var(--font-display);font-size:17px;color:var(--text-2)">${k.hex_count}</div><div style="font-family:var(--font-mono);font-size:8px;color:var(--text-3);text-transform:uppercase;letter-spacing:0.08em">Hexes</div></div>
          <div><div style="font-family:var(--font-display);font-size:14px;color:${statusColor}">${k.status}</div><div style="font-family:var(--font-mono);font-size:8px;color:var(--text-3);text-transform:uppercase;letter-spacing:0.08em">Status</div></div>
        </div>
      </div>`;
    }).join('');
  } catch(e) {
    el.innerHTML = '<div class="mp-loading">Failed to load.</div>';
  }
}

// ── Leaderboard ───────────────────────────────────
async function loadLeaderboard(sort = 'prosperity') {
  const civEl = document.getElementById('lb-civs');
  const playerEl = document.getElementById('lb-players');
  if (!civEl) return;
  civEl.innerHTML = '<div class="mp-loading"><div class="loading-ring"></div></div>';
  try {
    const res  = await fetch(`world.php?action=leaderboard&sort=${sort}`);
    const data = await res.json();
    if (!data.ok) return;
    civEl.innerHTML = data.kingdoms.map((k, i) => `
      <div style="display:flex;align-items:center;gap:10px;padding:10px 6px;border-bottom:1px solid var(--border)">
        <div style="font-family:var(--font-mono);font-size:11px;color:var(--text-3);width:20px">${i+1}</div>
        <div style="width:10px;height:10px;border-radius:50%;background:${k.color};flex-shrink:0"></div>
        <div style="flex:1;min-width:0">
          <div style="font-family:var(--font-display);font-size:13px;color:var(--text)">${k.name}</div>
          <div style="font-size:11px;color:var(--text-3);font-style:italic">${k.username} · ${k.region}</div>
        </div>
        <div style="font-family:var(--font-display);font-size:16px;color:var(--gold)">${k[sort] ?? '—'}</div>
      </div>`).join('');
    if (playerEl && data.players) {
      playerEl.innerHTML = data.players.map((p, i) => `
        <div style="display:flex;align-items:center;gap:10px;padding:10px 6px;border-bottom:1px solid var(--border)">
          <div style="font-family:var(--font-mono);font-size:11px;color:var(--text-3);width:20px">${i+1}</div>
          <div style="flex:1">
            <div style="font-family:var(--font-display);font-size:13px;color:var(--text)">${p.username}</div>
            <div style="font-size:11px;color:var(--text-3);font-style:italic">${p.kingdom_count} kingdom${p.kingdom_count!=1?'s':''}</div>
          </div>
          <div style="text-align:right">
            <div style="font-family:var(--font-display);font-size:15px;color:var(--gold)">${p.best_prosperity}</div>
            <div style="font-family:var(--font-mono);font-size:8px;color:var(--text-3);text-transform:uppercase">best prosperity</div>
          </div>
        </div>`).join('');
    }
  } catch(e) { civEl.innerHTML = '<div class="mp-loading">Failed.</div>'; }
}

// ── Rivalries ─────────────────────────────────────
async function loadRivalries() {
  const el = document.getElementById('ch-resolved');
  const incoming = document.getElementById('ch-incoming');
  const outgoing = document.getElementById('ch-outgoing');
  if (!el) return;
  el.innerHTML = '<div class="mp-loading"><div class="loading-ring"></div></div>';
  try {
    const res  = await fetch('world.php?action=rivalries');
    const data = await res.json();
    if (!data.ok || !data.battles.length) {
      el.innerHTML = '<div class="mp-loading">No battles yet.</div>';
      if (incoming) incoming.innerHTML = '<div class="mp-loading">No incoming challenges.</div>';
      if (outgoing) outgoing.innerHTML = '';
      return;
    }
    if (incoming) incoming.innerHTML = '<div class="mp-loading">Challenges resolve instantly.</div>';
    if (outgoing) outgoing.innerHTML = '';

    el.innerHTML = data.battles.map(b => {
      const oc = OUTCOME_LABELS[b.outcome] || { label: b.outcome, color: 'var(--text-3)' };

      // Determine winner/loser from outcome
      const attWon = ['crushing_victory','decisive_victory','victory'].includes(b.outcome);
      const defWon = ['crushing_defeat','decisive_defeat','defeat'].includes(b.outcome);
      const draw   = b.outcome === 'skirmish';

      const attBorderColor = attWon ? 'var(--green)' : defWon ? 'var(--red)' : 'var(--amber)';
      const defBorderColor = defWon ? 'var(--green)' : attWon ? 'var(--red)' : 'var(--amber)';

      const attLabel = attWon ? '👑 WINNER' : defWon ? '💀 DEFEATED' : '⚔ ATTACKER';
      const defLabel = defWon ? '👑 WINNER' : attWon ? '💀 DEFEATED' : '⚔ DEFENDER';
      const attLabelColor = attWon ? 'var(--green)' : defWon ? 'var(--red)' : 'var(--amber)';
      const defLabelColor = defWon ? 'var(--green)' : attWon ? 'var(--red)' : 'var(--amber)';

      return `
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:18px;margin-bottom:12px;">

          <!-- Outcome badge + hexes -->
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;">
            <span style="font-family:var(--font-mono);font-size:11px;letter-spacing:0.08em;padding:4px 12px;border-radius:5px;background:rgba(0,0,0,0.3);color:${oc.color};border:1px solid ${oc.color}44">${oc.label}</span>
            <span style="font-family:var(--font-mono);font-size:10px;color:var(--text-3)">
              🗺 <b style="color:var(--gold)">${b.hexes_transferred}</b> territories transferred
            </span>
          </div>

          <!-- Two kingdom cards side by side -->
          <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:10px;align-items:center;margin-bottom:14px;">

            <!-- Attacker -->
            <div style="background:var(--bg-raised);border:1px solid ${attBorderColor}44;border-radius:10px;padding:12px 14px;">
              <div style="font-family:var(--font-mono);font-size:8px;letter-spacing:0.1em;text-transform:uppercase;color:${attLabelColor};margin-bottom:6px">${attLabel}</div>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <div style="width:10px;height:10px;border-radius:50%;background:${b.att_color};flex-shrink:0"></div>
                <span style="font-family:var(--font-display);font-size:14px;color:var(--text)">${b.att_kingdom}</span>
              </div>
              <div style="font-size:11px;color:var(--text-3);font-style:italic;margin-bottom:8px">${b.att_user}</div>
              <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-3)">
                Power: <b style="color:var(--text-2)">${b.attacker_power}</b>
              </div>
            </div>

            <!-- VS -->
            <div style="text-align:center;font-family:var(--font-mono);font-size:12px;color:var(--text-3)">⚔<br/>vs</div>

            <!-- Defender -->
            <div style="background:var(--bg-raised);border:1px solid ${defBorderColor}44;border-radius:10px;padding:12px 14px;">
              <div style="font-family:var(--font-mono);font-size:8px;letter-spacing:0.1em;text-transform:uppercase;color:${defLabelColor};margin-bottom:6px">${defLabel}</div>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <div style="width:10px;height:10px;border-radius:50%;background:${b.def_color};flex-shrink:0"></div>
                <span style="font-family:var(--font-display);font-size:14px;color:var(--text)">${b.def_kingdom}</span>
              </div>
              <div style="font-size:11px;color:var(--text-3);font-style:italic;margin-bottom:8px">${b.def_user}</div>
              <div style="font-family:var(--font-mono);font-size:10px;color:var(--text-3)">
                Power: <b style="color:var(--text-2)">${b.defender_power}</b>
              </div>
            </div>
          </div>

          <!-- Battle description -->
          <div style="font-size:13px;color:var(--text-2);font-style:italic;line-height:1.6;padding:10px 12px;background:var(--bg-raised);border-radius:8px;border-left:2px solid ${oc.color}66">
            ${b.description}
          </div>

        </div>`;
    }).join('');
  } catch(e) { el.innerHTML = '<div class="mp-loading">Failed to load.</div>'; }
}

async function issueChallenge() {
  const defenderInput = document.getElementById('ch-defender');
  const errEl = document.getElementById('ch-error');
  const btn = document.getElementById('ch-send-btn');
  if (!defenderInput) return;

  const defender = defenderInput.value.trim();
  errEl.classList.add('hidden');
  if (!defender) { errEl.textContent = 'Enter opponent username.'; errEl.classList.remove('hidden'); return; }

  btn.disabled = true;
  btn.textContent = 'Resolving battle…';

  try {
    const fd = new FormData();
    fd.append('action', 'challenge');
    fd.append('defender_username', defender);
    const res  = await fetch('world.php', { method:'POST', body:fd });
    const data = await res.json();

    if (data.ok) {
      // Hide modal, show result
      document.getElementById('challenge-modal').classList.add('hidden');
      const oc = OUTCOME_LABELS[data.outcome] || { label: data.outcome, color: 'var(--gold)' };
      const resultDiv = document.createElement('div');
      resultDiv.style.cssText = 'background:var(--bg-card);border:2px solid ' + oc.color + '44;border-radius:14px;padding:24px;margin-bottom:20px;';
      resultDiv.innerHTML = `
        <div style="font-family:var(--font-display);font-size:22px;color:${oc.color};margin-bottom:8px">${oc.label}</div>
        <div style="font-size:15px;color:var(--text-2);font-style:italic;margin-bottom:14px">${data.description}</div>
        <div style="display:flex;gap:20px;font-family:var(--font-mono);font-size:11px;color:var(--text-3)">
          <span>Your power: <b style="color:var(--text)">${data.attacker_power}</b></span>
          <span>Opponent power: <b style="color:var(--text)">${data.defender_power}</b></span>
          <span>Hexes transferred: <b style="color:var(--gold)">${data.hexes_transferred}</b></span>
        </div>
        ${data.att_eliminated ? '<div style="margin-top:12px;color:var(--red);font-family:var(--font-mono);font-size:11px">⚠ Your kingdom was eliminated. Return to the map to found a new one.</div>' : ''}
        ${data.def_eliminated ? '<div style="margin-top:12px;color:var(--green);font-family:var(--font-mono);font-size:11px">✓ Enemy kingdom eliminated. Their lands are yours.</div>' : ''}
      `;
      const panel = document.querySelector('#panel-challenges .mp-panel-wrap');
      panel.insertBefore(resultDiv, panel.children[1]);
      loadRivalries();
      defenderInput.value = '';
    } else {
      errEl.textContent = data.error || 'Challenge failed.';
      errEl.classList.remove('hidden');
    }
  } catch(e) {
    errEl.textContent = 'Server error. Try again.';
    errEl.classList.remove('hidden');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Send Challenge';
  }
}

// ── DOMContentLoaded ──────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

  const worldCanvas = document.getElementById('world-canvas');
  if (worldCanvas) {
    WorldMap.init(worldCanvas, typeof CURRENT_USER_ID !== 'undefined' ? CURRENT_USER_ID : null);
  }

  // Hall buttons
  document.getElementById('hall-mine-btn')?.addEventListener('click', () => loadHall(true));
  document.getElementById('hall-all-btn')?.addEventListener('click',  () => loadHall(false));

  // Leaderboard sort
  document.querySelectorAll('.lb-sort-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.lb-sort-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      loadLeaderboard(btn.dataset.sort);
    });
  });

  // Rivalries — challenge modal
document.getElementById('send-challenge-btn')?.addEventListener('click', async () => {
  const modal = document.getElementById('challenge-modal');  
  modal?.classList.remove('hidden');  
  const selectEl = document.getElementById('ch-defender');  
  if (!selectEl) return;  
  selectEl.innerHTML = '<option value="">Loading opponents...</option>';  
  try {
    const res = await fetch('world.php?action=targets');    
    const data = await res.json();    
    if (data.ok && data.targets.length > 0) {
      const optionsHtml = data.targets.map(t => `<option value="${t.username}">${t.username} (${t.kingdom_name})</option>`).join('');      
      selectEl.innerHTML = '<option value="">-- Choose an opponent --</option>' + optionsHtml;
    } else {
      selectEl.innerHTML = '<option value="">No valid opponents found.</option>';
    }
  } catch(e) {
    selectEl.innerHTML = '<option value="">Error loading opponents.</option>';
  }
});
  document.getElementById('ch-cancel-btn')?.addEventListener('click', () => {
    document.getElementById('challenge-modal')?.classList.add('hidden');
  });
  document.getElementById('ch-send-btn')?.addEventListener('click', issueChallenge);

  // Nav
  document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.panel').forEach(p => { p.classList.remove('active'); p.classList.add('hidden'); });
      btn.classList.add('active');
      const panel = document.getElementById('panel-' + btn.dataset.panel);
      if (panel) {
        panel.classList.remove('hidden');
        panel.classList.add('active');
        if (btn.dataset.panel === 'simulate')    WorldMap.loadState();
        if (btn.dataset.panel === 'hall')        loadHall(false);
        if (btn.dataset.panel === 'leaderboard') loadLeaderboard('prosperity');
        if (btn.dataset.panel === 'challenges')  loadRivalries();
      }
    });
  }); // <-- The Nav loop safely closes here!

  // ── Manual Development Actions ──
  const handleDevelop = async (type, btnId) => {
    const btn = document.getElementById(btnId);
    const msgEl = document.getElementById('dev-msg');
    btn.disabled = true;
    
    try {
      const fd = new FormData();
      fd.append('action', 'develop');
      fd.append('type', type);
      
      const res = await fetch('world.php', { method: 'POST', body: fd });
      const data = await res.json();
      
      msgEl.style.display = 'block';
      if (data.ok) {
        msgEl.style.color = 'var(--green)';
        msgEl.textContent = data.message;
        WorldMap.loadState(); // Refresh the map UI to show new stats!
      } else {
        msgEl.style.color = 'var(--red)';
        msgEl.textContent = data.error;
      }
      
      setTimeout(() => { msgEl.style.display = 'none'; }, 3000);
    } catch(e) {
      msgEl.style.color = 'var(--red)';
      msgEl.textContent = 'Server error.';
      msgEl.style.display = 'block';
    } finally {
      btn.disabled = false;
    }
  };

  document.getElementById('btn-dev-tech')?.addEventListener('click', () => handleDevelop('technology', 'btn-dev-tech'));
  document.getElementById('btn-dev-mil')?.addEventListener('click', () => handleDevelop('military', 'btn-dev-mil'));
  document.getElementById('btn-dev-pros')?.addEventListener('click', () => handleDevelop('prosperity', 'btn-dev-pros'));

  // Logout
  document.getElementById('logout-btn')?.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('action', 'logout');
    await fetch('auth.php', { method:'POST', body:fd });
    window.location.href = 'login.php';
  });

});