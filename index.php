<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Civilization Simulator</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600&family=Crimson+Text:ital,wght@0,400;0,600;1,400;1,600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
</head>
<body>

<!-- ══════════════ MAIN APP ══════════════ -->
<div id="app">

  <!-- TOP NAV -->
  <nav class="top-nav">
    <div class="nav-brand">
      <span class="eyebrow" style="margin:0">Civilization Simulator</span>
    </div>
    <div class="nav-links">
      <button class="nav-btn active" data-panel="simulate">⚔ Simulate</button>
      <button class="nav-btn" data-panel="hall">📜 Hall of Records</button>
      <button class="nav-btn" data-panel="leaderboard">🏆 Leaderboard</button>
      <button class="nav-btn" data-panel="challenges">⚡ Rivalries <span id="challenge-badge" class="badge hidden">0</span></button>
    </div>
    <div class="nav-user">
      <span class="nav-username" id="nav-username"><?= $username ?></span>
      <button id="logout-btn" class="btn-ghost" style="padding:6px 14px;font-size:10px;">Sign out</button>
    </div>
  </nav>

  <!-- ══ PANEL: SIMULATE ══ -->
  <!-- PASTE THIS TO REPLACE THE ENTIRE  ══ PANEL: SIMULATE ══  div in index.php -->
<!-- Replace from:  <div id="panel-simulate" class="panel active">  -->
<!-- To its closing:  </div><!-- /panel-simulate -->               

  <!-- ══ PANEL: WORLD MAP (replaces Simulate) ══ -->
  <div id="panel-simulate" class="panel active">
    <div class="wm-layout">

      <!-- LEFT: Map canvas -->
      <div class="wm-map-col">
        <div class="wm-map-header">
          <div>
            <div class="eyebrow" style="margin-bottom:4px">Shared World</div>
            <h2 class="wm-title">The Known World</h2>
          </div>
          <div id="world-clock" class="wm-clock">Year 1, Week 1 · 0 kingdoms</div>
        </div>
        <div class="wm-canvas-wrap">
          <canvas id="world-canvas"></canvas>
          <div id="wm-kingdom-detail" class="wm-kingdom-detail" style="display:none"></div>
        </div>
        <div class="wm-region-legend">
          <span class="wm-leg-item" style="color:#3d9e5a">🌲 Temperate</span>
          <span class="wm-leg-item" style="color:#b88830">🏜 Desert</span>
          <span class="wm-leg-item" style="color:#5080c8">❄ Arctic</span>
          <span class="wm-leg-item" style="color:#40b060">🌴 Tropical</span>
          <span class="wm-leg-item" style="color:#3d7ab4">🏝 Island</span>
          <span class="wm-leg-item" style="color:#3d9e8c">🌊 River Delta</span>
        </div>
      </div>

      <!-- RIGHT: Sidebar -->
      <div class="wm-sidebar">

        <!-- My Kingdom Panel -->
        <div id="wm-found-panel" class="wm-panel-box" style="display:none">
          <div id="wm-eliminated-notice" class="wm-eliminated-notice" style="display:none">
            ⚔ Your kingdom was eliminated. You may found a new one.
          </div>
          <div class="wm-panel-title">Found Your Kingdom</div>
          <div class="form-group" style="margin-bottom:14px">
            <label style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.12em;text-transform:uppercase;color:var(--text-3);display:block;margin-bottom:7px">Kingdom Name</label>
            <input type="text" id="wm-found-name" placeholder="Name your kingdom" style="width:100%;background:var(--bg-raised);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-family:var(--font-body);font-size:15px;outline:none" />
          </div>
          <div class="form-group" style="margin-bottom:14px">
            <label style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.12em;text-transform:uppercase;color:var(--text-3);display:block;margin-bottom:7px">Starting Region</label>
            <select id="wm-found-region" style="width:100%;background:var(--bg-raised);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-family:var(--font-body);font-size:15px;outline:none;appearance:none">
              <option value="temperate">🌲 Temperate Forest</option>
              <option value="desert">🏜 Arid Desert</option>
              <option value="arctic">❄ Frozen Arctic</option>
              <option value="tropical">🌴 Tropical Rainforest</option>
              <option value="island">🏝 Island Chain</option>
              <option value="river_delta">🌊 River Delta</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:14px">
            <label style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.12em;text-transform:uppercase;color:var(--text-3);display:block;margin-bottom:7px">Government</label>
            <select id="wm-found-gov" style="width:100%;background:var(--bg-raised);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-family:var(--font-body);font-size:15px;outline:none;appearance:none">
              <option value="monarchy">Monarchy</option>
              <option value="democratic republic">Democratic Republic</option>
              <option value="theocracy">Theocracy</option>
              <option value="oligarchy">Oligarchy</option>
              <option value="tribal council">Tribal Council</option>
              <option value="military junta">Military Junta</option>
            </select>
          </div>

          <!-- Point-buy stat allocation -->
          <div class="form-group" style="margin-bottom:4px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
              <label style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.12em;text-transform:uppercase;color:var(--text-3)">Allocate Stats</label>
              <span style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--gold)">
                <span id="pts-remaining">200</span> / 200 pts left
              </span>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <?php
              $statDefs = [
                ['id'=>'wm-stat-resources',   'label'=>'⛏ Resources',   'name'=>'resources'],
                ['id'=>'wm-stat-technology',  'label'=>'⚗ Technology',  'name'=>'technology'],
                ['id'=>'wm-stat-territory',   'label'=>'🗺 Territory',   'name'=>'territory_score'],
                ['id'=>'wm-stat-military',    'label'=>'⚔ Military',    'name'=>'military'],
                ['id'=>'wm-stat-prosperity',  'label'=>'💰 Prosperity',  'name'=>'prosperity'],
              ];
              foreach($statDefs as $s): ?>
              <div style="display:flex;align-items:center;gap:8px;">
                <span style="font-family:'JetBrains Mono',monospace;font-size:9px;text-transform:uppercase;color:var(--text-3);width:82px;flex-shrink:0"><?= $s['label'] ?></span>
                <input type="range" id="<?= $s['id'] ?>" data-stat="<?= $s['name'] ?>"
                  min="10" max="80" value="40"
                  style="flex:1;height:3px;accent-color:var(--gold);cursor:pointer" />
                <span id="<?= $s['id'] ?>-val" style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--gold);width:22px;text-align:right">40</span>
              </div>
              <?php endforeach; ?>
            </div>
            <div id="pts-warning" style="display:none;margin-top:8px;font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--red);letter-spacing:0.06em">
              ⚠ Total must equal exactly 200 points
            </div>
          </div>

          <div id="wm-found-error" class="wm-error" style="display:none"></div>
          <button id="wm-found-btn" class="btn-primary" style="width:100%;margin-top:14px">Found Kingdom</button>
        </div>

        <!-- Active kingdom stats -->
        <div id="wm-my-kingdom-panel" class="wm-panel-box" style="display:none">
          <div class="wm-panel-title">Your Kingdom</div>
          <div class="wm-my-name" id="my-k-name">—</div>
          <div class="wm-my-meta">
            <span id="my-k-region">—</span> · <span id="my-k-gov" style="color:var(--text-2)">—</span>
          </div>
          <div class="wm-stat-bars">
            <div class="wm-sbar-row"><span>Resources</span><div class="wm-sbar-track"><div class="wm-sbar-fill" id="bar-resources"></div></div><span id="bar-resources-val" style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text-3);width:22px;text-align:right"></span></div>
            <div class="wm-sbar-row"><span>Technology</span><div class="wm-sbar-track"><div class="wm-sbar-fill" id="bar-technology"></div></div><span id="bar-technology-val" style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text-3);width:22px;text-align:right"></span></div>
            <div class="wm-sbar-row"><span>Territory</span><div class="wm-sbar-track"><div class="wm-sbar-fill" id="bar-territory_score"></div></div><span id="bar-territory_score-val" style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text-3);width:22px;text-align:right"></span></div>
            <div class="wm-sbar-row"><span>Military</span><div class="wm-sbar-track"><div class="wm-sbar-fill" id="bar-military"></div></div><span id="bar-military-val" style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text-3);width:22px;text-align:right"></span></div>
            <div class="wm-sbar-row"><span>Prosperity</span><div class="wm-sbar-track"><div class="wm-sbar-fill" id="bar-prosperity"></div></div><span id="bar-prosperity-val" style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--text-3);width:22px;text-align:right"></span></div>
          </div>
          <div class="wm-my-footer">
            <div class="wm-my-stat"><div class="wm-my-stat-val" id="my-k-hexes">—</div><div class="wm-my-stat-label">Hexes</div></div>
            <div class="wm-my-stat"><div class="wm-my-stat-val" id="my-k-military">—</div><div class="wm-my-stat-label">Military</div></div>
            <div class="wm-my-stat"><div class="wm-my-stat-val" id="my-k-prosperity">—</div><div class="wm-my-stat-label">Prosperity</div></div>
          </div>
            
            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border);">
            <div style="font-family: var(--font-mono); font-size: 10px; color: var(--gold); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 10px;">
              Royal Decrees (Cost: 15 Res)
            </div>
            <div style="display: flex; gap: 8px; flex-direction: column;">
              <button id="btn-dev-tech" class="btn-secondary" style="font-size: 11px; padding: 6px;">⚗ Fund Research (+Tech)</button>
              <button id="btn-dev-mil" class="btn-secondary" style="font-size: 11px; padding: 6px;">⚔ Recruit Army (+Mil)</button>
              <button id="btn-dev-pros" class="btn-secondary" style="font-size: 11px; padding: 6px;">Fund Festivals (+Pros)</button>
            </div>
            <div id="dev-msg" style="margin-top: 8px; font-size: 11px; color: var(--teal); font-style: italic; display: none;"></div>
          </div>
            
        </div>

        <!-- World Kingdoms list -->
        <div class="wm-panel-box wm-panel-kingdoms">
          <div class="wm-panel-title">All Kingdoms <span id="world-kingdom-count" style="color:var(--text-3);font-weight:400"></span></div>
          <div id="world-kingdoms-list" class="wm-kingdoms-list">
            <div class="wm-empty">Loading…</div>
          </div>
        </div>

        <!-- Recent world events -->
        <div class="wm-panel-box">
          <div class="wm-panel-title">World Chronicle</div>
          <div id="world-events-list" class="wm-events-list">
            <div class="wm-empty">Loading…</div>
          </div>
        </div>

      </div><!-- /wm-sidebar -->
    </div><!-- /wm-layout -->
  </div><!-- /panel-simulate -->

    <!-- SIM SCREEN -->
    <div id="sim-screen" class="screen">
      <div class="sim-header">
        <div class="sim-title-group">
          <div class="eyebrow">Simulating</div>
          <h1 id="sim-civ-name">—</h1>
          <div id="ruling-house" class="ruling-house-label"></div>
        </div>
        <div class="sim-controls">
          <span id="century-badge" class="century-badge">Century 1</span>
          <button id="save-sim-btn" class="btn-secondary" style="display:none;padding:8px 18px;font-size:11px;">💾 Save to Hall</button>
          <button id="reset-btn" class="btn-ghost">← New Civilization</button>
        </div>
      </div>

      <div class="stat-bar">
        <div class="stat-card"><div class="stat-label">Population</div><div class="stat-value" id="stat-pop">—</div></div>
        <div class="stat-card"><div class="stat-label">Era</div><div class="stat-value" id="stat-era">—</div></div>
        <div class="stat-card"><div class="stat-label">Prosperity</div><div class="stat-value" id="stat-prosperity">—</div></div>
        <div class="stat-card"><div class="stat-label">Stability</div><div class="stat-value" id="stat-stability">—</div></div>
        <div class="stat-card"><div class="stat-label">Ruler</div><div class="stat-value ruler-val" id="stat-ruler">—</div></div>
        <div class="stat-card"><div class="stat-label">Outcome</div><div class="stat-value outcome" id="stat-outcome">Unwritten</div></div>
      </div>

      <!-- TERRITORY BAR -->
      <div style="background:#181613;border:1px solid rgba(201,160,60,0.12);border-radius:14px;padding:14px 18px 16px;margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
          <span style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.14em;text-transform:uppercase;color:#5a5040;">Territory Control</span>
          <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center;">
            <span style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.06em;text-transform:uppercase;color:#7a6120;display:flex;align-items:center;gap:6px;"><b style="display:inline-block;width:12px;height:8px;border-radius:2px;background:#c9a03c;"></b>Your realm</span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.06em;text-transform:uppercase;color:#5a5040;display:flex;align-items:center;gap:6px;"><b style="display:inline-block;width:12px;height:8px;border-radius:2px;background:#3d9e8c;"></b><span id="legend-rival1">Rival I</span></span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.06em;text-transform:uppercase;color:#5a5040;display:flex;align-items:center;gap:6px;"><b style="display:inline-block;width:12px;height:8px;border-radius:2px;background:#b450a0;"></b><span id="legend-rival2">Rival II</span></span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.06em;text-transform:uppercase;color:#5a5040;display:flex;align-items:center;gap:6px;"><b style="display:inline-block;width:12px;height:8px;border-radius:2px;background:#5080c8;"></b><span id="legend-rival3">Rival III</span></span>
            <span style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.06em;text-transform:uppercase;color:#5a5040;display:flex;align-items:center;gap:6px;"><b style="display:inline-block;width:12px;height:8px;border-radius:2px;background:rgba(80,65,45,0.6);border:1px solid rgba(90,72,50,0.4);"></b>Unclaimed</span>
          </div>
        </div>
        <div style="width:100%;height:22px;border-radius:11px;overflow:hidden;display:flex;flex-direction:row;background:#1e1b17;border:1px solid rgba(255,255,255,0.06);">
          <div id="tb-player"  style="height:100%;width:20%;background:linear-gradient(90deg,#c9a03c,#a07828);transition:width 0.75s ease;flex-shrink:0;"></div>
          <div id="tb-rival1"  style="height:100%;width:12%;background:linear-gradient(90deg,#3d9e8c,#2a6e60);transition:width 0.75s ease;flex-shrink:0;"></div>
          <div id="tb-rival2"  style="height:100%;width:10%;background:linear-gradient(90deg,#b450a0,#7a2e70);transition:width 0.75s ease;flex-shrink:0;"></div>
          <div id="tb-rival3"  style="height:100%;width:9%;background:linear-gradient(90deg,#5080c8,#304880);transition:width 0.75s ease;flex-shrink:0;"></div>
          <div id="tb-neutral" style="height:100%;width:49%;background:rgba(40,32,20,0.8);transition:width 0.75s ease;flex-shrink:0;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:9px;">
          <span id="tb-pct-player"  style="font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:0.06em;color:#c9a03c;">20% yours</span>
          <span id="tb-pct-rivals"  style="font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:0.06em;color:#3d9e8c;">31% rivals</span>
          <span id="tb-pct-neutral" style="font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:0.06em;color:#5a5040;">49% unclaimed</span>
        </div>
      </div>

      <div class="sim-body">
        <div class="map-panel">
          <div class="panel-label">Territory — <span id="map-century-label">Century 1</span></div>
          <canvas id="hex-map"></canvas>
          <div class="map-legend">
            <span class="legend-item player">Your territory</span>
            <span class="legend-item rival">Rival states</span>
            <span class="legend-item neutral">Unclaimed land</span>
          </div>
        </div>
        <div class="right-col">
          <div class="rivals-panel">
            <div class="panel-label">Rival Nations</div>
            <div id="rivals-list" class="rivals-list"><div class="rivals-loading">Awaiting simulation…</div></div>
          </div>
          <div class="chronicle-panel">
            <div class="panel-label">Chronicle of Events</div>
            <div id="loading-state" class="loading-state">
              <div class="loading-ring"></div>
              <span id="loading-text">Consulting the historian AI…</span>
            </div>
            <div id="timeline" class="timeline"></div>
          </div>
        </div>
      </div>

      <div class="graph-panel">
        <div class="panel-label">Prosperity across centuries</div>
        <canvas id="prosperity-graph"></canvas>
      </div>

      <div id="save-result" class="save-result hidden"></div>
    </div>
  </div><!-- /panel-simulate -->

  <!-- ══ PANEL: HALL OF RECORDS ══ -->
  <div id="panel-hall" class="panel hidden">
    <div class="mp-panel-wrap">
      <div class="mp-panel-header">
        <div>
          <div class="eyebrow">Multiplayer</div>
          <h1 style="font-size:26px;">Hall of Records</h1>
          <p style="color:var(--text-3);font-style:italic;font-size:14px;margin-top:4px;">Every civilization ever simulated — written into the annals of history</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;">
          <select id="hall-filter" class="mp-select">
            <option value="">All outcomes</option>
            <option value="flourishing">Flourishing</option>
            <option value="dominant">Dominant</option>
            <option value="transformed">Transformed</option>
            <option value="stagnant">Stagnant</option>
            <option value="collapsed">Collapsed</option>
          </select>
          <button id="hall-mine-btn" class="btn-secondary" style="padding:8px 16px;font-size:11px;">My Civilizations</button>
          <button id="hall-all-btn" class="btn-secondary" style="padding:8px 16px;font-size:11px;">All Records</button>
        </div>
      </div>
      <div id="hall-list" class="hall-list">
        <div class="mp-loading"><div class="loading-ring"></div><span>Loading records…</span></div>
      </div>
    </div>
  </div>

  <!-- ══ PANEL: LEADERBOARD ══ -->
  <div id="panel-leaderboard" class="panel hidden">
    <div class="mp-panel-wrap">
      <div class="mp-panel-header">
        <div>
          <div class="eyebrow">Multiplayer</div>
          <h1 style="font-size:26px;">Leaderboard</h1>
          <p style="color:var(--text-3);font-style:italic;font-size:14px;margin-top:4px;">The greatest civilizations ever forged</p>
        </div>
        <div style="display:flex;gap:8px;">
          <button class="lb-sort-btn btn-secondary active" data-sort="prosperity_peak" style="padding:8px 14px;font-size:11px;">Prosperity</button>
          <button class="lb-sort-btn btn-secondary" data-sort="territory_peak" style="padding:8px 14px;font-size:11px;">Territory</button>
          <button class="lb-sort-btn btn-secondary" data-sort="centuries" style="padding:8px 14px;font-size:11px;">Centuries</button>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
        <div>
          <div class="panel-label">Top Civilizations</div>
          <div id="lb-civs" class="lb-list">
            <div class="mp-loading"><div class="loading-ring"></div></div>
          </div>
        </div>
        <div>
          <div class="panel-label">Top Players</div>
          <div id="lb-players" class="lb-list"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ PANEL: RIVALRIES ══ -->
 <div id="panel-challenges" class="panel hidden">
  <div class="mp-panel-wrap">
    <div class="mp-panel-header">
      <div>
        <div class="eyebrow">Multiplayer</div>
        <h1 style="font-size:26px;">Rivalries</h1>
        <p style="color:var(--text-3);font-style:italic;font-size:14px;margin-top:4px;">Challenge other dynasties to war — may history judge the worthiest</p>
      </div>
      <button id="send-challenge-btn" class="btn-primary" style="padding:12px 22px;font-size:13px;">Issue a Challenge</button>
    </div>

    <div id="challenge-modal" class="challenge-modal hidden">
      <div class="challenge-modal-inner">
        <h2 style="font-family:var(--font-display);color:var(--gold);margin-bottom:16px;">Issue a Challenge</h2>
        
        <div class="form-group">
          <label style="font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:0.12em;text-transform:uppercase;color:var(--text-3);display:block;margin-bottom:7px">Select Opponent</label>
          
          <select id="ch-defender" style="width:100%;background:var(--bg-raised);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-family:var(--font-body);font-size:15px;outline:none;appearance:none">
            <option value="">Loading opponents...</option>
          </select>
        </div>
        
        <div id="ch-error" class="error-msg hidden" style="margin-bottom:14px"></div>
        
        <div style="display:flex;gap:10px;margin-top:16px;">
          <button id="ch-send-btn" class="btn-primary" style="flex:1;">Send Challenge</button>
          <button id="ch-cancel-btn" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <div class="panel-label">Incoming Challenges</div>
        <div id="ch-incoming" class="ch-list"><div class="mp-loading"><div class="loading-ring"></div></div></div>
      </div>
      <div>
        <div class="panel-label">Outgoing Challenges</div>
        <div id="ch-outgoing" class="ch-list"></div>
      </div>
    </div>

    <div style="margin-top:20px;">
      <div class="panel-label">Recent Battle Results</div>
      <div id="ch-resolved" class="ch-list"></div>
    </div>
  </div>
</div>

<script>const CURRENT_USER_ID = <?php echo json_encode($_SESSION['user_id']); ?>;</script>

<script src="world_map.js?v=<?php echo time(); ?>"></script>

<script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
