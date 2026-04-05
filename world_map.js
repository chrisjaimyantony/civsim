/* ═══════════════════════════════════════════════════
   CIVILIZATION SIMULATOR — world_map.js
   Shared persistent world map engine
   ═══════════════════════════════════════════════════ */
'use strict';

const WorldMap = (() => {

  // ── Constants ──────────────────────────────────
  const COLS = 40, ROWS = 28;
  const REGION_COLORS = {
    temperate:   { base: '#1a2e1a', border: 'rgba(60,120,60,0.35)',  label: '🌲 Temperate' },
    desert:      { base: '#2e2210', border: 'rgba(160,100,20,0.35)', label: '🏜 Desert' },
    arctic:      { base: '#1a2030', border: 'rgba(80,130,180,0.35)', label: '❄ Arctic' },
    tropical:    { base: '#1a2a18', border: 'rgba(40,140,60,0.35)',  label: '🌴 Tropical' },
    island:      { base: '#101e2a', border: 'rgba(30,100,160,0.35)', label: '🏝 Island' },
    river_delta: { base: '#101e22', border: 'rgba(30,120,140,0.35)', label: '🌊 River Delta' },
  };

  const REGION_BOUNDS = {
    temperate:   { colMin:0,  colMax:13, rowMin:0,  rowMax:13 },
    desert:      { colMin:14, colMax:26, rowMin:0,  rowMax:13 },
    arctic:      { colMin:27, colMax:39, rowMin:0,  rowMax:13 },
    tropical:    { colMin:0,  colMax:13, rowMin:14, rowMax:27 },
    island:      { colMin:14, colMax:26, rowMin:14, rowMax:27 },
    river_delta: { colMin:27, colMax:39, rowMin:14, rowMax:27 },
  };

  // ── State ──────────────────────────────────────
  let canvas, ctx, W, H, S;
  let hexData   = {};   // key "col,row" → hex object
  let kingdoms  = [];
  let worldState = {};
  let myKingdom = null;
  let userId    = null;
  let hoveredHex = null;
  let selectedKingdom = null;
  let tickInterval = null;
  let pollInterval = null;

  // ── Init ───────────────────────────────────────
  function init(canvasEl, uid) {
    canvas = canvasEl;
    userId = uid;
    ctx    = canvas.getContext('2d');
    resize();
    window.addEventListener('resize', resize);
    canvas.addEventListener('mousemove', onMouseMove);
    canvas.addEventListener('click', onClick);
    // Attach found kingdom button directly
    const foundBtn = document.getElementById('wm-found-btn');
    if (foundBtn) foundBtn.addEventListener('click', foundKingdom);
    loadState();
    // Poll for world updates every 30s
    pollInterval = setInterval(loadState, 30000);
    // Attempt tick every 5 min
    tickInterval = setInterval(attemptTick, 60000);
    initStatSliders();
  }

  function resize() {
    const dpr = window.devicePixelRatio || 1;
    const wrap = canvas.parentElement;
    const cw   = wrap.clientWidth - 32;
    const ch   = Math.round(cw * 0.62);
    canvas.width  = cw * dpr;
    canvas.height = ch * dpr;
    canvas.style.width  = cw + 'px';
    canvas.style.height = ch + 'px';
    ctx.scale(dpr, dpr);
    W = cw; H = ch;
    S = Math.floor(W / (COLS * 1.58 + 0.5));
    render();
  }

  // ── Hex geometry ──────────────────────────────
  function hexCenter(col, row) {
    const x  = S * 1.5 * col;
    const y  = S * Math.sqrt(3) * (row + (col % 2 === 0 ? 0 : 0.5));
    const tw = S * 1.5 * (COLS - 1) + S * 2;
    const th = S * Math.sqrt(3) * (ROWS - 1 + 0.5) + S * Math.sqrt(3);
    return { x: x + (W - tw) / 2 + S, y: y + (H - th) / 2 + S * Math.sqrt(3) * 0.5 };
  }

  function hexKey(col, row) { return `${col},${row}`; }

  function hexAtPoint(px, py) {
    // Approximate reverse — scan nearby hexes
    let best = null, bestD = Infinity;
    for (let col = 0; col < COLS; col++) {
      for (let row = 0; row < ROWS; row++) {
        const { x, y } = hexCenter(col, row);
        const d = Math.hypot(px - x, py - y);
        if (d < S * 1.1 && d < bestD) { bestD = d; best = { col, row }; }
      }
    }
    return best;
  }

  // ── Data loading ──────────────────────────────
  async function loadState() {
    try {
      const res  = await fetch('world.php?action=state');
      const data = await res.json();
      if (!data.ok) return;

      hexData    = {};
      data.hexes.forEach(h => { hexData[hexKey(h.col, h.row)] = h; });
      kingdoms   = data.kingdoms;
      worldState = data.world;
      myKingdom  = data.my_kingdom;
      userId     = data.user_id;

      render();
      renderSidebar();
      renderWorldClock();
      renderMyKingdomPanel();
      renderWorldEvents(data.events || []);
    } catch (e) { console.error('[WorldMap] loadState error:', e); }
  }

  async function attemptTick() {
    try {
      const res  = await fetch('world.php?action=tick&_=' + Date.now());
      const data = await res.json();
      if (data.ok && !data.skipped) {
        loadState();
        if (data.events && data.events.length) renderTickEvents(data.events);
      }
    } catch (e) {}
  }

  // ── Render ────────────────────────────────────
  function drawHex(x, y, size, fill, stroke, lineW) {
    ctx.beginPath();
    for (let i = 0; i < 6; i++) {
      const a = (Math.PI / 3) * i;
      i === 0 ? ctx.moveTo(x + size * Math.cos(a), y + size * Math.sin(a))
              : ctx.lineTo(x + size * Math.cos(a), y + size * Math.sin(a));
    }
    ctx.closePath();
    ctx.fillStyle = fill;
    ctx.fill();
    if (stroke) {
      ctx.strokeStyle = stroke;
      ctx.lineWidth   = lineW || 0.5;
      ctx.stroke();
    }
  }

  function render() {
    ctx.clearRect(0, 0, W, H);
    ctx.fillStyle = '#070b0f';
    ctx.fillRect(0, 0, W, H);

    // Draw region background bands first
    Object.entries(REGION_BOUNDS).forEach(([region, b]) => {
      const tl = hexCenter(b.colMin, b.rowMin);
      const br = hexCenter(b.colMax, b.rowMax);
      ctx.fillStyle = REGION_COLORS[region].base + '88';
      ctx.fillRect(tl.x - S, tl.y - S, br.x - tl.x + S * 3, br.y - tl.y + S * 3);
    });

    // Draw region divider lines
    ctx.setLineDash([3, 4]);
    ctx.strokeStyle = 'rgba(201,160,60,0.08)';
    ctx.lineWidth = 1;
    // Vertical dividers
    [14, 27].forEach(col => {
      const top = hexCenter(col, 0);
      const bot = hexCenter(col, ROWS - 1);
      ctx.beginPath(); ctx.moveTo(top.x - S, top.y - S); ctx.lineTo(bot.x - S, bot.y + S * 2); ctx.stroke();
    });
    // Horizontal divider
    const midLeft  = hexCenter(0, 14);
    const midRight = hexCenter(COLS - 1, 14);
    ctx.beginPath(); ctx.moveTo(midLeft.x - S, midLeft.y); ctx.lineTo(midRight.x + S, midRight.y); ctx.stroke();
    ctx.setLineDash([]);

    // Draw all hexes
    for (let col = 0; col < COLS; col++) {
      for (let row = 0; row < ROWS; row++) {
        const k   = hexKey(col, row);
        const h   = hexData[k];
        const { x, y } = hexCenter(col, row);
        const s   = S - 1;

        if (!h) { drawHex(x, y, s, 'transparent', 'rgba(201,160,60,0.04)'); continue; }

        const isHovered   = hoveredHex && hoveredHex.col === col && hoveredHex.row === row;
        const region      = h.region || 'temperate';
        const regionColor = REGION_COLORS[region];

        if (h.kingdom_id) {
          const kColor = h.color || '#c9a03c';
          const isMe   = h.kingdom_user_id == userId;
          const alpha  = isMe ? 'cc' : '88';
          drawHex(x, y, s, kColor + alpha, kColor + '44', isMe ? 1 : 0.5);

          // Glow for my kingdom
          if (isMe) {
            ctx.shadowColor = kColor;
            ctx.shadowBlur  = 6;
            drawHex(x, y, s - 1, 'transparent', kColor + 'aa', 1.5);
            ctx.shadowBlur  = 0;
          }

          // Capital dot
          if (isMe) {
            ctx.beginPath();
            ctx.arc(x, y, 2.5, 0, Math.PI * 2);
            ctx.fillStyle = '#fff';
            ctx.fill();
          }
        } else {
          drawHex(x, y, s, regionColor.base, regionColor.border, 0.4);
        }

        // Hover highlight
        if (isHovered) {
          drawHex(x, y, s, 'rgba(255,255,255,0.08)', 'rgba(255,255,255,0.3)', 1);
        }
      }
    }

    // Region labels
    ctx.font = `bold ${Math.max(10, S * 1.8)}px 'JetBrains Mono', monospace`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    Object.entries(REGION_BOUNDS).forEach(([region, b]) => {
      const cx = Math.floor((b.colMin + b.colMax) / 2);
      const cy = Math.floor((b.rowMin + b.rowMax) / 2);
      const { x, y } = hexCenter(cx, cy);
      ctx.fillStyle = 'rgba(201,160,60,0.18)';
      ctx.fillText(REGION_COLORS[region].label, x, y);
    });

    // Hover tooltip
    if (hoveredHex) {
      const h = hexData[hexKey(hoveredHex.col, hoveredHex.row)];
      if (h && h.kingdom_name) {
        const { x, y } = hexCenter(hoveredHex.col, hoveredHex.row);
        ctx.font = `500 11px 'JetBrains Mono', monospace`;
        ctx.textAlign = 'center';
        const label = h.kingdom_name;
        const tw    = ctx.measureText(label).width + 16;
        const tx    = Math.min(Math.max(x, tw / 2 + 4), W - tw / 2 - 4);
        const ty    = y - S * 2.2;
        ctx.fillStyle = 'rgba(20,16,10,0.92)';
        ctx.beginPath();
        ctx.roundRect(tx - tw/2, ty - 12, tw, 22, 4);
        ctx.fill();
        ctx.strokeStyle = h.color + '88';
        ctx.lineWidth = 1;
        ctx.stroke();
        ctx.fillStyle = h.color || '#c9a03c';
        ctx.fillText(label, tx, ty + 1);
      }
    }
  }

  function renderSidebar() {
    const el = document.getElementById('world-kingdoms-list');
    if (!el) return;
    if (!kingdoms.length) {
      el.innerHTML = '<div class="wm-empty">No kingdoms yet. Be the first to found one!</div>';
      return;
    }
    el.innerHTML = kingdoms.map((k, i) => {
      const isMe = k.user_id == userId;
      const maxStat = k.maxed_stat;
      const power = ((k.resources + k.technology + k.territory_score + k.military) / 4).toFixed(0);
      return `
        <div class="wm-kingdom-row ${isMe ? 'wm-kingdom-mine' : ''}" data-kid="${k.id}">
          <div class="wm-rank">${i + 1}</div>
          <div class="wm-color-dot" style="background:${k.color}"></div>
          <div class="wm-kingdom-info">
            <div class="wm-kingdom-name">${k.name} ${isMe ? '<span class="wm-you">YOU</span>' : ''}</div>
            <div class="wm-kingdom-meta">${k.username} · ${k.region} · ⚡${maxStat}</div>
          </div>
          <div class="wm-kingdom-stats">
            <div class="wm-stat-pill">${k.hex_count} hexes</div>
            <div class="wm-stat-pill">⚔ ${k.military}</div>
            <div class="wm-stat-pill ${k.status === 'eliminated' ? 'wm-eliminated' : ''}">
              ${k.status === 'eliminated' ? 'Eliminated' : '◆ ' + power}
            </div>
          </div>
        </div>`;
    }).join('');

    el.querySelectorAll('.wm-kingdom-row').forEach(row => {
      row.addEventListener('click', () => {
        const kid = parseInt(row.dataset.kid);
        showKingdomPanel(kid);
      });
    });
  }

  function renderWorldClock() {
    const el = document.getElementById('world-clock');
    if (!el || !worldState) return;
    el.textContent = `Year ${worldState.year}, Week ${worldState.week} · ${worldState.total_kingdoms || 0} kingdoms`;
  }

  function renderWorldEvents(events) {
    const el = document.getElementById('world-events-list');
    if (!el) return;
    if (!events || !events.length) {
      el.innerHTML = '<div class="wm-empty">No events yet — the world is quiet.</div>';
      return;
    }
    el.innerHTML = events.map(ev => {
      const typeClass = 'wm-ev-' + (ev.type || 'founding');
      const label = ev.type || 'event';
      let text = ev.description || '';
      // Mention attacker/defender names if present
      return `<div class="wm-event-entry">
        <span class="wm-ev-type ${typeClass}">${label}</span> ${text}
      </div>`;
    }).join('');
  }

  function renderMyKingdomPanel() {
    const foundPanel = document.getElementById('wm-found-panel');
    const myPanel    = document.getElementById('wm-my-kingdom-panel');
    if (!foundPanel || !myPanel) return;

    if (!myKingdom || myKingdom.status === 'eliminated') {
      foundPanel.style.display = 'block';
      myPanel.style.display    = 'none';
      if (myKingdom && myKingdom.status === 'eliminated') {
        document.getElementById('wm-eliminated-notice').style.display = 'block';
      }
      initStatSliders();
    } else {
      foundPanel.style.display = 'none';
      myPanel.style.display    = 'block';
      document.getElementById('my-k-name').textContent   = myKingdom.name;
      document.getElementById('my-k-region').textContent = myKingdom.region;
      document.getElementById('my-k-gov').textContent    = myKingdom.government || '';
      document.getElementById('my-k-hexes').textContent     = myKingdom.hex_count;
      document.getElementById('my-k-military').textContent  = myKingdom.military;
      document.getElementById('my-k-prosperity').textContent = myKingdom.prosperity;

      const stats = ['resources','technology','territory_score','military','prosperity'];
      stats.forEach(st => {
        const bar = document.getElementById('bar-' + st);
        const val = document.getElementById('bar-' + st + '-val');
        const v   = myKingdom[st] || 0;
        if (bar) {
          bar.style.width      = v + '%';
          bar.style.background = v >= 70 ? '#c9a03c' : v >= 40 ? '#3d9e8c' : '#b03a3a';
        }
        if (val) val.textContent = v;
      });
    }
  }

  function renderTickEvents(events) {
    // After a tick, just reload full state to get fresh events from DB
    loadState();
  }

  async function showKingdomPanel(kid) {
    selectedKingdom = kid;
    try {
      const res  = await fetch(`world.php?action=kingdom_info&id=${kid}`);
      const data = await res.json();
      if (!data.ok) return;
      const k = data.kingdom;
      const panel = document.getElementById('wm-kingdom-detail');
      if (!panel) return;
      panel.style.display = 'block';
      panel.innerHTML = `
        <div class="wm-detail-header" style="border-left:3px solid ${k.color}">
          <div class="wm-detail-name">${k.name}</div>
          <div class="wm-detail-sub">${k.username} · ${k.region} · ${k.government}</div>
        </div>
        <div class="wm-detail-stats">
          ${[['Resources',k.resources],['Technology',k.technology],['Territory',k.territory_score],['Military',k.military],['Prosperity',k.prosperity]].map(([label,val])=>`
          <div class="wm-detail-stat">
            <span>${label}</span>
            <div class="wm-stat-track"><div class="wm-stat-fill" style="width:${val}%;background:${val>=70?'#c9a03c':val>=40?'#3d9e8c':'#b03a3a'}"></div></div>
            <span>${val}</span>
          </div>`).join('')}
        </div>
        <div class="wm-detail-footer">
          Maxed: <strong>${k.maxed_stat}</strong> · ${k.hex_count} hexes · Status: <strong>${k.status}</strong>
        </div>
        <button class="wm-close-btn" onclick="document.getElementById('wm-kingdom-detail').style.display='none'">✕ Close</button>
      `;
    } catch (e) {}
  }

  // ── Point-buy slider logic ─────────────────────
  function initStatSliders() {
    const TOTAL = 200;
    const sliders = document.querySelectorAll('#wm-found-panel input[type=range]');
    function update() {
      let used = 0;
      sliders.forEach(s => { used += parseInt(s.value); });
      const remaining = TOTAL - used;
      const remEl = document.getElementById('pts-remaining');
      const warnEl = document.getElementById('pts-warning');
      if (remEl) {
        remEl.textContent = remaining;
        remEl.style.color = remaining === 0 ? 'var(--green)' : remaining < 0 ? 'var(--red)' : 'var(--gold)';
      }
      if (warnEl) warnEl.style.display = remaining !== 0 ? 'block' : 'none';
      sliders.forEach(s => {
        const valEl = document.getElementById(s.id + '-val');
        if (valEl) valEl.textContent = s.value;
      });
    }
    sliders.forEach(s => s.addEventListener('input', update));
    update();
  }

  // ── Found Kingdom ─────────────────────────────
  async function foundKingdom() {
    const name   = document.getElementById('wm-found-name').value.trim();
    const region = document.getElementById('wm-found-region').value;
    const gov    = document.getElementById('wm-found-gov').value;
    const errEl  = document.getElementById('wm-found-error');
    const btn    = document.getElementById('wm-found-btn');

    errEl.style.display = 'none';
    if (!name) { errEl.textContent = 'Enter a kingdom name.'; errEl.style.display = 'block'; return; }

    // Collect stats
    const sliders = document.querySelectorAll('#wm-found-panel input[type=range]');
    let stats = {}, total = 0;
    sliders.forEach(s => { stats[s.dataset.stat] = parseInt(s.value); total += parseInt(s.value); });
    if (total !== 200) {
      errEl.textContent = `Points must total exactly 200 (currently ${total}).`;
      errEl.style.display = 'block';
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Founding…';

    try {
      const fd = new FormData();
      fd.append('action', 'found');
      fd.append('name', name);
      fd.append('region', region);
      fd.append('government', gov);
      Object.entries(stats).forEach(([k, v]) => fd.append(k, v));

      const res  = await fetch('world.php', { method: 'POST', body: fd });
      const data = await res.json();

      if (data.ok) {
        await loadState();
      } else {
        errEl.textContent = data.error || 'Failed to found kingdom.';
        errEl.style.display = 'block';
      }
    } catch (e) {
      errEl.textContent = 'Server error. Try again.';
      errEl.style.display = 'block';
    } finally {
      btn.disabled = false;
      btn.textContent = 'Found Kingdom';
    }
  }

  // ── Mouse events ──────────────────────────────
  function onMouseMove(e) {
    const rect = canvas.getBoundingClientRect();
    const px   = e.clientX - rect.left;
    const py   = e.clientY - rect.top;
    const hex  = hexAtPoint(px, py);
    if (!hex || (hoveredHex && hoveredHex.col === hex.col && hoveredHex.row === hex.row)) return;
    hoveredHex = hex;
    render();
  }

  function onClick(e) {
    const rect = canvas.getBoundingClientRect();
    const hex  = hexAtPoint(e.clientX - rect.left, e.clientY - rect.top);
    if (!hex) return;
    const h = hexData[hexKey(hex.col, hex.row)];
    if (h && h.kingdom_id) showKingdomPanel(h.kingdom_id);
  }

  function destroy() {
    clearInterval(tickInterval);
    clearInterval(pollInterval);
    window.removeEventListener('resize', resize);
  }

  // ── Public API ────────────────────────────────
  return { init, foundKingdom, loadState, destroy, attemptTick };

})();