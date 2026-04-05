<?php
// MARKER123

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) { echo json_encode(['error' => 'Not authenticated']); exit; }
$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function getKingdomByUser(PDO $pdo, int $userId): ?array {
  $s = $pdo->prepare('SELECT * FROM kingdoms WHERE user_id = ? LIMIT 1');
  $s->execute([$userId]);
  return $s->fetch() ?: null;
}

function getNeighbors(int $col, int $row): array {
  if ($col % 2 === 0) {
    return [[$col-1,$row-1],[$col,$row-1],[$col+1,$row-1],[$col-1,$row],[$col+1,$row],[$col,$row+1]];
  } else {
    return [[$col-1,$row],[$col,$row-1],[$col+1,$row],[$col-1,$row+1],[$col+1,$row+1],[$col,$row+1]];
  }
}

function kingdomColor(int $id): string {
  $palette = ['#c9a03c','#3d9e8c','#b450a0','#5080c8','#c05a40','#5a9a5a','#7a6ab8','#b88830','#3d7ab4','#a04060','#40a080','#8060c0','#c08040','#4080c0','#80a030','#c04080','#30a0a0','#a06030','#6040b0','#b04040','#40b060','#a08020','#2080b0','#b06080','#608030','#4060b0','#b03060','#306080','#80b040','#a04020'];
  return $palette[$id % count($palette)];
}

function checkHegemon(PDO $pdo) {
    
    $totalHexes = (int)$pdo->query('SELECT COUNT(*) FROM world_hexes')->fetchColumn();
	if ($totalHexes < 100) return; 

    $topKingdom = $pdo->query('SELECT id, name FROM kingdoms WHERE status = "active" ORDER BY hex_count DESC LIMIT 1')->fetch();
    
    if (!$topKingdom) return;

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM world_hexes WHERE kingdom_id = ?');
    $stmt->execute([$topKingdom['id']]);
    $actualHexes = (int)$stmt->fetchColumn();

    if ($actualHexes >= ($totalHexes * 0.75)) {        
        $msg = "WORLD HEGEMON: {$topKingdom['name']} has conquered 75 percent of the known world! The era concludes, and the map resets for a new age.";        
        $pdo->prepare('INSERT INTO world_events (type, attacker_id, description) VALUES ("founding", ?, ?)')->execute([$topKingdom['id'], $msg]);        
        $pdo->query('UPDATE kingdoms SET status = "eliminated" WHERE status = "active"');        
        $pdo->query('UPDATE world_hexes SET kingdom_id = NULL');       
        $pdo->query('UPDATE world_state SET week=1, year=1, total_kingdoms=0 WHERE id=1');
    }
}

if ($action === 'state') {
  $pdo = getDB();
  $hexes = $pdo->query('
    SELECT h.`col`, h.`row`, h.region, h.kingdom_id,
           k.name as kingdom_name, k.color, k.user_id as kingdom_user_id,
           k.status as kingdom_status
    FROM world_hexes h
    LEFT JOIN kingdoms k ON h.kingdom_id = k.id
  ')->fetchAll();
  $kingdoms = $pdo->query('SELECT k.*, u.username FROM kingdoms k JOIN users u ON k.user_id = u.id ORDER BY k.prosperity DESC')->fetchAll();
  $ws = $pdo->query('SELECT * FROM world_state WHERE id=1')->fetch();
  $events = $pdo->query('
    SELECT e.*, a.name as attacker_name, a.color as attacker_color, d.name as defender_name, d.color as defender_color
    FROM world_events e LEFT JOIN kingdoms a ON e.attacker_id = a.id LEFT JOIN kingdoms d ON e.defender_id = d.id
    ORDER BY e.created_at DESC LIMIT 20
  ')->fetchAll();
  $myKingdom = getKingdomByUser($pdo, $userId);
  echo json_encode(['ok'=>true,'hexes'=>$hexes,'kingdoms'=>$kingdoms,'world'=>$ws,'events'=>$events,'my_kingdom'=>$myKingdom,'user_id'=>$userId]);
  exit;
}

if ($action === 'targets') {
  $pdo = getDB();
    $stmt = $pdo->prepare('SELECT u.username, k.name as kingdom_name FROM kingdoms k JOIN users u ON k.user_id = u.id WHERE k.status = "active" AND k.user_id != ? ORDER BY u.username ASC');
    $stmt->execute([$userId]);
    echo json_encode(['ok' => true, 'targets' => $stmt->fetchAll()]);
    exit;
}

if ($action === 'found') {
  $pdo = getDB();
  $existing = $pdo->prepare('SELECT id, status FROM kingdoms WHERE user_id = ?');
  $existing->execute([$userId]);
  $ex = $existing->fetch();
  if ($ex && $ex['status'] !== 'eliminated') { echo json_encode(['error' => 'You already rule a kingdom.']); exit; }

  $name       = trim($_POST['name'] ?? '');
  $region     = $_POST['region'] ?? '';
  $government = $_POST['government'] ?? 'monarchy';

  if (!$name) { echo json_encode(['error' => 'Kingdom name required.']); exit; }
  if (!in_array($region, ['temperate','desert','arctic','tropical','island','river_delta'])) {
    echo json_encode(['error' => 'Invalid region.']); exit;
  }

  // Read point-buy stats from frontend sliders
  $resources       = max(10, min(80, (int)($_POST['resources']        ?? 40)));
  $technology      = max(10, min(80, (int)($_POST['technology']       ?? 40)));
  $territory_score = max(10, min(80, (int)($_POST['territory_score']  ?? 40)));
  $military        = max(10, min(80, (int)($_POST['military']         ?? 40)));
  $prosperity      = max(10, min(80, (int)($_POST['prosperity']       ?? 40)));

  $total = $resources + $technology + $territory_score + $military + $prosperity;
  if ($total !== 200) { echo json_encode(['error' => "Stats must total 200 (got {$total})"]); exit; }

  $freeHexes = $pdo->prepare('SELECT `col`, `row` FROM world_hexes WHERE region = ? AND kingdom_id IS NULL ORDER BY RAND() LIMIT 80');  $freeHexes->execute([$region]);
  $candidates = $freeHexes->fetchAll();
  if (count($candidates) < 5) { echo json_encode(['error' => 'That region is full! Choose another.']); exit; }

  $seed     = $candidates[array_rand($candidates)];
  $claimed  = [[$seed['col'], $seed['row']]];
  $frontier = array_values(array_filter($candidates, fn($f) => !($f['col'] == $seed['col'] && $f['row'] == $seed['row'])));

  while (count($claimed) < 5 && count($frontier) > 0) {
    $adj = [];
    foreach ($claimed as [$cc, $cr]) {
      foreach (getNeighbors($cc, $cr) as [$nc, $nr]) {
        foreach ($frontier as $f) {
          if ($f['col'] == $nc && $f['row'] == $nr) { $adj[] = $f; break; }
        }
      }
    }
    $pick    = empty($adj) ? $frontier[array_rand($frontier)] : $adj[array_rand($adj)];
    $claimed[] = [$pick['col'], $pick['row']];
    $claimed   = array_unique($claimed, SORT_REGULAR);
    $frontier  = array_values(array_filter($frontier, fn($f) => !($f['col'] == $pick['col'] && $f['row'] == $pick['row'])));
  }

  $color = kingdomColor((int)$pdo->query('SELECT COUNT(*) FROM kingdoms')->fetchColumn());
  if ($ex) $pdo->prepare('DELETE FROM kingdoms WHERE user_id = ?')->execute([$userId]);

  $pdo->prepare('INSERT INTO kingdoms (user_id,name,region,government,maxed_stat,color,resources,technology,territory_score,military,prosperity) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
      ->execute([$userId, $name, $region, $government, 'custom', $color, $resources, $technology, $territory_score, $military, $prosperity]);
  $kingdomId = (int)$pdo->lastInsertId();

  $upd = $pdo->prepare('UPDATE world_hexes SET kingdom_id = ? WHERE `col` = ? AND `row` = ?');
  foreach ($claimed as [$c, $r]) $upd->execute([$kingdomId, $c, $r]);

  $pdo->prepare('UPDATE kingdoms SET hex_count = ? WHERE id = ?')->execute([count($claimed), $kingdomId]);
  $pdo->query('UPDATE world_state SET total_kingdoms = total_kingdoms + 1 WHERE id = 1');
  $pdo->prepare('INSERT INTO world_events (type,attacker_id,description) VALUES ("founding",?,?)')->execute([$kingdomId, "{$name} has been founded in the {$region} region."]);

  echo json_encode(['ok'=>true,'kingdom_id'=>$kingdomId,'color'=>$color,'hexes'=>$claimed]);
  exit;
}

// ── ACTION: DEVELOP (Manual Stat Upgrades) ────────
if ($action === 'develop') {
    $pdo = getDB();
    $type = $_POST['type'] ?? '';
    
    $k = getKingdomByUser($pdo, $userId);
    if (!$k || $k['status'] !== 'active') {
        echo json_encode(['error' => 'No active kingdom found.']);
        exit;
    }

    $cost = 15;
    if ($k['resources'] < $cost) {
        echo json_encode(['error' => 'Not enough resources. Need ' . $cost]);
        exit;
    }

    switch ($type) {
        case 'technology':
            $stmt = $pdo->prepare('UPDATE kingdoms SET technology = LEAST(technology + 4, 100), resources = resources - ? WHERE id = ?');
            $msg = 'Technological breakthrough achieved! (+4 Tech)';
            break;
        case 'military':
            $stmt = $pdo->prepare('UPDATE kingdoms SET military = LEAST(military + 5, 100), resources = resources - ? WHERE id = ?');
            $msg = 'Military reserves bolstered! (+5 Military)';
            break;
        case 'prosperity':
            $stmt = $pdo->prepare('UPDATE kingdoms SET prosperity = LEAST(prosperity + 5, 100), resources = resources - ? WHERE id = ?');
            $msg = 'Invested in public infrastructure! (+5 Prosperity)';
            break;
        default:
            echo json_encode(['error' => 'Invalid action type.']);
            exit;
    }

    $stmt->execute([$cost, $k['id']]);
    
    $pdo->prepare('INSERT INTO world_events (type, attacker_id, description) VALUES ("discovery", ?, ?)')
        ->execute([$k['id'], "{$k['name']} invested heavily in their {$type}."]);

    echo json_encode(['ok' => true, 'message' => $msg]);
    exit;
}

if ($action === 'tick') {
  $pdo = getDB();
  $ws = $pdo->query('SELECT * FROM world_state WHERE id=1')->fetch();
  if (time() - strtotime($ws['last_tick']) < 300) { echo json_encode(['ok'=>true,'skipped'=>true]); exit; }

  $events_fired = [];
  $kingdoms = $pdo->query('SELECT * FROM kingdoms WHERE status = "active"')->fetchAll();
  $kingdomMap = [];
  foreach ($kingdoms as $k) $kingdomMap[$k['id']] = $k;

  $hexOwners = $pdo->query('SELECT `col`, `row`, kingdom_id FROM world_hexes WHERE kingdom_id IS NOT NULL')->fetchAll();
  $hexGrid = [];
  foreach ($hexOwners as $h) $hexGrid[$h['col'].','.$h['row']] = $h['kingdom_id'];

  $adjacency = [];
  foreach ($hexOwners as $h) {
    $kid = $h['kingdom_id'];
    foreach (getNeighbors($h['col'], $h['row']) as [$nc,$nr]) {
      $nkey = "$nc,$nr";
      if (isset($hexGrid[$nkey]) && $hexGrid[$nkey] != $kid) {
        $adjacency[$kid][] = $hexGrid[$nkey];
        $adjacency[$kid] = array_unique($adjacency[$kid]);
      }
    }
  }

  foreach ($kingdoms as $k) {
    $kid = $k['id'];
    foreach ($adjacency[$kid] ?? [] as $nid) {
      if (!isset($kingdomMap[$nid])) continue;
      $neighbor = $kingdomMap[$nid];
      $kPower = ($k['military'] * 1.5) * (1 + $k['technology'] / 100);
      $kPower += $k['resources'];
      $nPower = ($neighbor['military'] * 1.5) * (1 + $neighbor['technology'] / 100);
      $nPower += $neighbor['resources'];
      
      if ($kPower >= $nPower * 2 && $k['hex_count'] >= 8) {
        $abs = $pdo->prepare('SELECT `col`, `row` FROM world_hexes WHERE kingdom_id = ? LIMIT 3');
        $abs->execute([$nid]);
        $toAbsorb = $abs->fetchAll();
        foreach ($toAbsorb as $ah) $pdo->prepare('UPDATE world_hexes SET kingdom_id = ? WHERE `col` = ? AND `row` = ?')->execute([$kid,$ah['col'],$ah['row']]);
        $newKCount = $k['hex_count'] + count($toAbsorb);
        $newNCount = $neighbor['hex_count'] - count($toAbsorb);
        $pdo->prepare('UPDATE kingdoms SET hex_count=?,military=LEAST(military+5,100) WHERE id=?')->execute([$newKCount,$kid]);
        
        if ($newNCount <= 0) {
          $pdo->prepare('UPDATE kingdoms SET status="eliminated",hex_count=0 WHERE id=?')->execute([$nid]);
          $pdo->prepare('UPDATE world_hexes SET kingdom_id=? WHERE kingdom_id=?')->execute([$kid,$nid]);
          $pdo->prepare('INSERT INTO world_events(type,attacker_id,defender_id,description,outcome) VALUES("absorption",?,?,?,"eliminated")')->execute([$kid,$nid,"{$k['name']} absorbed and eliminated {$neighbor['name']}."]);
          $events_fired[] = ['type'=>'absorption','msg'=>"{$k['name']} eliminated {$neighbor['name']}"];
          unset($kingdomMap[$nid]);
        } else {
          $pdo->prepare('UPDATE kingdoms SET hex_count=?,military=GREATEST(military-8,5) WHERE id=?')->execute([$newNCount,$nid]);
          $pdo->prepare('INSERT INTO world_events(type,attacker_id,defender_id,description) VALUES("border_clash",?,?,?)')->execute([$kid,$nid,"{$k['name']} seized territories from {$neighbor['name']}."]);
          $events_fired[] = ['type'=>'border_clash','msg'=>"{$k['name']} seized land from {$neighbor['name']}"];
        }
        $kingdomMap[$kid]['hex_count']=$newKCount;
        $kingdomMap[$kid]['military']=min($k['military']+5,100);
      }

      $kAvg = ($k['resources']+$k['technology']+$k['territory_score'])/3;
      if ($kAvg > 75 && $kPower > $nPower && rand(1,100) <= 40) {
        $pdo->prepare('INSERT INTO world_events(type,attacker_id,defender_id,description) VALUES("war",?,?,?)')->execute([$kid,$nid,"{$k['name']}'s dominance provokes war against {$neighbor['name']}."]);
        $pdo->prepare('UPDATE kingdoms SET military=LEAST(military+4,100),prosperity=LEAST(prosperity+3,100) WHERE id=?')->execute([$kid]);
        $pdo->prepare('UPDATE kingdoms SET military=GREATEST(military-6,5),prosperity=GREATEST(prosperity-8,5) WHERE id=?')->execute([$nid]);
        $events_fired[] = ['type'=>'war','msg'=>"War: {$k['name']} vs {$neighbor['name']}"];
      }
    }
  }

  $randomEvents = [
    ['type'=>'discovery','desc'=>'%s discovers an ancient archive, boosting technology.','stat'=>'technology','delta'=>6],
    ['type'=>'plague','desc'=>'A plague sweeps through %s, weakening its population.','stat'=>'prosperity','delta'=>-10],
    ['type'=>'rebellion','desc'=>'A rebellion erupts within %s, fracturing its military.','stat'=>'military','delta'=>-8],
    ['type'=>'discovery','desc'=>'%s strikes a rich vein of ore, flooding its coffers.','stat'=>'resources','delta'=>7],
    ['type'=>'alliance','desc'=>'Merchants of %s forge new trade routes, wealth pours in.','stat'=>'prosperity','delta'=>8],
    ['type'=>'rebellion','desc'=>'Famine grips %s as harvests fail for the third year.','stat'=>'resources','delta'=>-7],
  ];
  
  foreach ($kingdoms as $k) {
    if (rand(1,100) <= 60) {
      $ev = $randomEvents[array_rand($randomEvents)];
      $desc = sprintf($ev['desc'], $k['name']);
      $pdo->prepare("UPDATE kingdoms SET `{$ev['stat']}` = GREATEST(LEAST(`{$ev['stat']}` + ?, 100), 5) WHERE id = ?")->execute([$ev['delta'],$k['id']]);
      $pdo->prepare('INSERT INTO world_events(type,attacker_id,description) VALUES(?,?,?)')->execute([$ev['type'],$k['id'],$desc]);
      $events_fired[] = ['type'=>$ev['type'],'msg'=>$desc];

      if ($ev['delta'] > 0) {
        $freeHex = $pdo->prepare('SELECT `col`, `row` FROM world_hexes WHERE kingdom_id IS NULL AND region = ? ORDER BY RAND() LIMIT 1');
        $freeHex->execute([$k['region']]);
        $newHex = $freeHex->fetch();
        if ($newHex) {
          $pdo->prepare('UPDATE world_hexes SET kingdom_id = ? WHERE `col` = ? AND `row` = ?')->execute([$k['id'], $newHex['col'], $newHex['row']]);
          $pdo->prepare('UPDATE kingdoms SET hex_count = hex_count + 1 WHERE id = ?')->execute([$k['id']]);
        }
      }
    }
  }

  $newWeek = ($ws['week'] % 52) + 1;
  $newYear = $ws['year'] + ($ws['week'] == 52 ? 1 : 0);
  $pdo->prepare('UPDATE world_state SET week=?,year=?,last_tick=NOW(),total_kingdoms=(SELECT COUNT(*) FROM kingdoms WHERE status="active") WHERE id=1')->execute([$newWeek,$newYear]);
  
  checkHegemon($pdo);

  echo json_encode(['ok'=>true,'events'=>$events_fired,'week'=>$newWeek,'year'=>$newYear]);
  exit;
}

if ($action === 'events') {
  $pdo = getDB();
  $events = $pdo->query('SELECT e.*,a.name as attacker_name,a.color as attacker_color,d.name as defender_name,d.color as defender_color FROM world_events e LEFT JOIN kingdoms a ON e.attacker_id=a.id LEFT JOIN kingdoms d ON e.defender_id=d.id ORDER BY e.created_at DESC LIMIT 30')->fetchAll();
  echo json_encode(['ok'=>true,'events'=>$events]);
  exit;
}

if ($action === 'kingdom_info') {
  $kid = (int)($_GET['id'] ?? 0);
  if (!$kid) { echo json_encode(['error'=>'No id']); exit; }
  $pdo = getDB();
  $k = $pdo->prepare('SELECT k.*,u.username FROM kingdoms k JOIN users u ON k.user_id=u.id WHERE k.id=?');
  $k->execute([$kid]);
  $kingdom = $k->fetch();
  if (!$kingdom) { echo json_encode(['error'=>'Not found']); exit; }
  echo json_encode(['ok'=>true,'kingdom'=>$kingdom]);
  exit;
}


// ── ACTION: CHALLENGE (instant resolution) ────────
if ($action === 'challenge') {
    $pdo = getDB();

    $defenderUsername = trim($_POST['defender_username'] ?? '');

    if (!$defenderUsername) { 
        echo json_encode(['error' => 'Enter opponent username.']); 
        exit; 
    }
    $attKingdom = getKingdomByUser($pdo, $userId);
    if (!$attKingdom || $attKingdom['status'] !== 'active') {
        echo json_encode(['error' => 'You need an active kingdom to issue a challenge.']); 
        exit;
    }

    $defUser = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $defUser->execute([$defenderUsername]);
    $defUserRow = $defUser->fetch();

    if (!$defUserRow) { 
        echo json_encode(['error' => "No user found: {$defenderUsername}"]); 
        exit; 
    }

    if ((int)$defUserRow['id'] === $userId) { 
        echo json_encode(['error' => 'You cannot challenge yourself.']); 
        exit; 
    }

    $defKingdom = getKingdomByUser($pdo, (int)$defUserRow['id']);

    if (!$defKingdom || $defKingdom['status'] !== 'active') {
        echo json_encode(['error' => "{$defenderUsername} has no active kingdom."]); 
        exit;
    }

    if ($attKingdom['hex_count'] > ($defKingdom['hex_count'] * 3) && $defKingdom['hex_count'] < 15) {
        echo json_encode(['error' => "Honor forbids this! {$defKingdom['name']} is too small to be a valid rival. Challenge an empire your own size."]);
        exit;
    }


  // ── MULTI-FACTOR POWER CALCULATION ──────────────
  function calcPower(array $k): float {
      $base = $k['military'] * (1 + $k['technology'] / 100);
      $resourceMod = 0.5 + ($k['resources'] / 100);
      $effectiveTerritory = $k['territory_score'] + ($k['hex_count'] * 2);
      $territoryMod = 0.8 + ($effectiveTerritory / 150);
      
      $prosperityMod = 0.6 + ($k['prosperity'] / 100);
      $specializationMod = 1.0;
      
      $highestStat = max($k['military'], $k['technology'], $k['resources'], $k['territory_score'], $k['prosperity']);
      if ($highestStat < 50) {
          $specializationMod = 0.85;
      }
      
      return $base * $resourceMod * $territoryMod * $prosperityMod * $specializationMod;
  }

  $attBase = calcPower($attKingdom);
  $defBase = calcPower($defKingdom);

  // ── COUNTER RULES ────────────────────────────────
  $attFinal = $attBase;
  $defFinal = $defBase;
  $battleNotes = [];

  $milGap = $attKingdom['military'] - $defKingdom['military'];
  if ($milGap > 0 && $defKingdom['technology'] >= 60) {
      $attFinal -= ($milGap * 0.4) * (1 + $defKingdom['technology'] / 100);
      $battleNotes[] = "{$defKingdom['name']}'s superior technology negated the military gap.";
  }

  if ($milGap < 0 && $attKingdom['technology'] >= 60) {
      $defFinal -= (abs($milGap) * 0.4) * (1 + $attKingdom['technology'] / 100);
      $battleNotes[] = "{$attKingdom['name']}'s superior technology negated the military gap.";
  }

  if ($attKingdom['technology'] > $defKingdom['technology'] && $attKingdom['resources'] < 35) {
      $attFinal *= 0.6;
      $battleNotes[] = "{$attKingdom['name']}'s technology advantage collapsed due to resource shortage.";
  }

  if ($defKingdom['technology'] > $attKingdom['technology'] && $defKingdom['resources'] < 35) {
      $defFinal *= 0.6;
      $battleNotes[] = "{$defKingdom['name']}'s technology advantage collapsed due to resource shortage.";
  }

  $attResourceStarved = $attKingdom['resources'] < 30;
  if ($attResourceStarved) {
      $attFinal *= 0.75;
      $battleNotes[] = "{$attKingdom['name']}'s campaign is hampered by critical resource shortages.";
  }

  $attFinal = max($attFinal, 1);
  $defFinal = max($defFinal, 1);
  $ratio = $attFinal / $defFinal;

  // ── SPECIAL THRESHOLD MODIFIERS ─────────────────
  $defLowMorale = $defKingdom['prosperity'] < 20;
  $defEntrenched = $defKingdom['territory_score'] > 70;

  $eliminationThreshold = 2.0;
  if ($defLowMorale)   $eliminationThreshold = 1.5;
  if ($defEntrenched)  $eliminationThreshold = 2.5;

  // ── OUTCOME RESOLUTION ───────────────────────────
  $outcome = '';
  $desc = '';
  $hexesTransferred = 0;
  $attEliminated = false;
  $defEliminated = false;

  $transferHexes = function(int $fromId, int $toId, int $count) use ($pdo): int {
    $grab = $pdo->prepare('SELECT `col`, `row` FROM world_hexes WHERE kingdom_id = :kid ORDER BY RAND() LIMIT :lim');
    $grab->bindValue(':kid', $fromId, PDO::PARAM_INT);
    $grab->bindValue(':lim', $count, PDO::PARAM_INT);    
    $grab->execute();    
    $grabbed = $grab->fetchAll();    
    $upd = $pdo->prepare('UPDATE world_hexes SET kingdom_id = ? WHERE `col` = ? AND `row` = ?');    
    foreach ($grabbed as $h) {
        $upd->execute([$toId, $h['col'], $h['row']]);
    }    
    return count($grabbed);
  };

  $eliminateKingdom = function(int $loserId, int $winnerId) use ($pdo, $transferHexes, &$hexesTransferred) {
    $loser = $pdo->prepare('SELECT hex_count FROM kingdoms WHERE id = ?');
    $loser->execute([$loserId]);
    $loserData = $loser->fetch();
    $hexesTransferred = (int)$loserData['hex_count'];
    $pdo->prepare('UPDATE world_hexes SET kingdom_id = ? WHERE kingdom_id = ?')->execute([$winnerId, $loserId]);
    $pdo->prepare('UPDATE kingdoms SET status = "eliminated", hex_count = 0 WHERE id = ?')->execute([$loserId]);
    $pdo->prepare('UPDATE kingdoms SET hex_count = hex_count + ?, military = LEAST(military+15,100), prosperity = LEAST(prosperity+10,100) WHERE id = ?')->execute([$hexesTransferred, $winnerId]);
  };

  $noteStr = !empty($battleNotes) ? ' ' . implode(' ', $battleNotes) : '';

  if ($ratio >= $eliminationThreshold) {
    if ($attResourceStarved) {
      $outcome = 'victory';
      $pct = rand(40, 60) / 100;
      $take = max(1, (int)round($defKingdom['hex_count'] * $pct));
      $hexesTransferred = $transferHexes($defKingdom['id'], $attKingdom['id'], $take);
      $desc = "{$attKingdom['name']} achieved a decisive victory over {$defKingdom['name']}, seizing {$hexesTransferred} territories — though supply shortages prevented total conquest.{$noteStr}";
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count-?, military=GREATEST(military-20,5), prosperity=GREATEST(prosperity-15,5) WHERE id=?')->execute([$hexesTransferred, $defKingdom['id']]);
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count+?, military=LEAST(military+8,100), prosperity=LEAST(prosperity+6,100) WHERE id=?')->execute([$hexesTransferred, $attKingdom['id']]);
    } else {
      $outcome = 'crushing_victory';
      $defEliminated = true;
      $eliminateKingdom($defKingdom['id'], $attKingdom['id']);
      $desc = "{$attKingdom['name']} crushed {$defKingdom['name']} in a total conquest. The defeated kingdom is dissolved and all {$hexesTransferred} territories seized.{$noteStr}";
    }

  } elseif ($ratio >= 1.4) {
    if ($attResourceStarved) {
      $outcome = 'victory';
      $pct = rand(20, 35) / 100;
    } else {
      $outcome = 'decisive_victory';
      $pct = rand(40, 60) / 100;
    }
    $take = max(1, (int)round($defKingdom['hex_count'] * $pct));
    $hexesTransferred = $transferHexes($defKingdom['id'], $attKingdom['id'], $take);
    $desc = "{$attKingdom['name']} decisively defeated {$defKingdom['name']}, seizing {$hexesTransferred} territories.{$noteStr}";
    $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count-?, military=GREATEST(military-18,5), prosperity=GREATEST(prosperity-12,5) WHERE id=?')->execute([$hexesTransferred, $defKingdom['id']]);
    $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count+?, military=LEAST(military+10,100), prosperity=LEAST(prosperity+8,100) WHERE id=?')->execute([$hexesTransferred, $attKingdom['id']]);

  } elseif ($ratio >= 1.15) {
    if ($attResourceStarved) {
      $outcome = 'skirmish';
      $take = rand(1, max(1, (int)round($defKingdom['hex_count'] * 0.08)));
      $hexesTransferred = $transferHexes($defKingdom['id'], $attKingdom['id'], $take);
      $desc = "{$attKingdom['name']} pushed into {$defKingdom['name']}'s lands but resource starvation forced an early withdrawal. Minor gains only.{$noteStr}";
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count-?, military=GREATEST(military-8,5) WHERE id=?')->execute([$hexesTransferred, $defKingdom['id']]);
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count+?, military=GREATEST(military-10,5) WHERE id=?')->execute([$hexesTransferred, $attKingdom['id']]);
    } else {
      $outcome = 'victory';
      $pct = rand(20, 35) / 100;
      $take = max(1, (int)round($defKingdom['hex_count'] * $pct));
      $hexesTransferred = $transferHexes($defKingdom['id'], $attKingdom['id'], $take);
      $desc = "{$attKingdom['name']} defeated {$defKingdom['name']} and seized {$hexesTransferred} territories.{$noteStr}";
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count-?, military=GREATEST(military-12,5), prosperity=GREATEST(prosperity-8,5) WHERE id=?')->execute([$hexesTransferred, $defKingdom['id']]);
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count+?, military=LEAST(military+8,100), prosperity=LEAST(prosperity+5,100) WHERE id=?')->execute([$hexesTransferred, $attKingdom['id']]);
    }

  } elseif ($ratio >= 0.87) {
    $outcome = 'skirmish';
    $attGains = $ratio >= 1.0;
    $take = rand(1, max(1, (int)round(min($attKingdom['hex_count'], $defKingdom['hex_count']) * 0.10)));
    if ($attGains) {
      $hexesTransferred = $transferHexes($defKingdom['id'], $attKingdom['id'], $take);
      $desc = "{$attKingdom['name']} and {$defKingdom['name']} clashed in a grinding skirmish. {$attKingdom['name']} gained a slight edge, taking {$hexesTransferred} territories.{$noteStr}";
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count-?, military=GREATEST(military-10,5), prosperity=GREATEST(prosperity-5,5) WHERE id=?')->execute([$hexesTransferred, $defKingdom['id']]);
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count+?, military=GREATEST(military-7,5) WHERE id=?')->execute([$hexesTransferred, $attKingdom['id']]);
    } else {
      $hexesTransferred = $transferHexes($attKingdom['id'], $defKingdom['id'], $take);
      $desc = "{$attKingdom['name']} attacked {$defKingdom['name']} but was repelled in a costly skirmish, losing {$hexesTransferred} territories.{$noteStr}";
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count+?, military=GREATEST(military-7,5) WHERE id=?')->execute([$hexesTransferred, $defKingdom['id']]);
      $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count-?, military=GREATEST(military-10,5), prosperity=GREATEST(prosperity-5,5) WHERE id=?')->execute([$hexesTransferred, $attKingdom['id']]);
    }

  } elseif ($ratio >= 0.6) {
    $outcome = 'defeat';
    $pct = rand(20, 35) / 100;
    $take = max(1, (int)round($attKingdom['hex_count'] * $pct));
    $hexesTransferred = $transferHexes($attKingdom['id'], $defKingdom['id'], $take);
    $desc = "{$defKingdom['name']} repelled {$attKingdom['name']}'s assault and seized {$hexesTransferred} territories.{$noteStr}";
    $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count+?, military=LEAST(military+8,100), prosperity=LEAST(prosperity+5,100) WHERE id=?')->execute([$hexesTransferred, $defKingdom['id']]);
    $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count-?, military=GREATEST(military-12,5), prosperity=GREATEST(prosperity-8,5) WHERE id=?')->execute([$hexesTransferred, $attKingdom['id']]);

  } elseif ($ratio >= 0.4) {
    $outcome = 'decisive_defeat';
    $pct = rand(40, 60) / 100;
    $take = max(1, (int)round($attKingdom['hex_count'] * $pct));
    $hexesTransferred = $transferHexes($attKingdom['id'], $defKingdom['id'], $take);
    $desc = "{$defKingdom['name']} crushed {$attKingdom['name']}'s forces in a decisive counter, claiming {$hexesTransferred} territories.{$noteStr}";
    $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count+?, military=LEAST(military+10,100), prosperity=LEAST(prosperity+8,100) WHERE id=?')->execute([$hexesTransferred, $defKingdom['id']]);
    $pdo->prepare('UPDATE kingdoms SET hex_count=hex_count-?, military=GREATEST(military-18,5), prosperity=GREATEST(prosperity-12,5) WHERE id=?')->execute([$hexesTransferred, $attKingdom['id']]);

  } else {
    $outcome = 'crushing_defeat';
    $attEliminated = true;
    $eliminateKingdom($attKingdom['id'], $defKingdom['id']);
    $desc = "{$defKingdom['name']} utterly annihilated {$attKingdom['name']}'s forces. The aggressor's kingdom is dissolved and all {$hexesTransferred} territories absorbed.{$noteStr}";
  }

  $pdo->prepare('UPDATE kingdoms SET status="eliminated", hex_count=0 WHERE hex_count <= 0 AND status="active"')->execute();

  $pdo->prepare('INSERT INTO challenges (attacker_id,defender_id,attacker_kingdom_id,defender_kingdom_id,status,outcome,description,attacker_power,defender_power,hexes_transferred) VALUES (?,?,?,?,?,?,?,?,?,?)')
    ->execute([$userId, $defUserRow['id'], $attKingdom['id'], $defKingdom['id'], 'resolved', $outcome, $desc, round($attFinal), round($defFinal), $hexesTransferred]);
  
  $pdo->prepare('INSERT INTO world_events (type,attacker_id,defender_id,description,outcome) VALUES ("war",?,?,?,?)')
    ->execute([$attKingdom['id'], $defKingdom['id'], $desc, $outcome]);
  
  $pdo->query('UPDATE world_state SET total_kingdoms=(SELECT COUNT(*) FROM kingdoms WHERE status="active") WHERE id=1');

  // THIS is where the Hegemon check goes: right at the end, before the final JSON response!
  checkHegemon($pdo);

  echo json_encode([
    'ok'               => true,
    'outcome'          => $outcome,
    'description'      => $desc,
    'attacker_power'   => round($attFinal),
    'defender_power'   => round($defFinal),
    'hexes_transferred'=> $hexesTransferred,
    'att_eliminated'   => $attEliminated,
    'def_eliminated'   => $defEliminated,
    'attacker'         => $attKingdom['name'],
    'defender'         => $defKingdom['name'],
    'battle_notes'     => $battleNotes,
  ]);
  exit;
}

// ── ACTION: RIVALRIES LIST ─────────────────────────
if ($action === 'rivalries') {
  $pdo = getDB();
  $battles = $pdo->query("
    SELECT c.*,
      ak.name as att_kingdom, ak.color as att_color,
      dk.name as def_kingdom, dk.color as def_color,
      au.username as att_user, du.username as def_user
    FROM challenges c
    JOIN kingdoms ak ON c.attacker_kingdom_id = ak.id
    JOIN kingdoms dk ON c.defender_kingdom_id = dk.id
    JOIN users au ON c.attacker_id = au.id
    JOIN users du ON c.defender_id = du.id
    ORDER BY c.created_at DESC LIMIT 30
  ")->fetchAll();
  echo json_encode(['ok'=>true,'battles'=>$battles]);
  exit;
}

// ── ACTION: HALL OF RECORDS ───────────────────────
if ($action === 'hall') {
  $pdo  = getDB();
  $mine = isset($_GET['mine']) ? (int)$_GET['mine'] : 0;
  if ($mine) {
    $rows = $pdo->prepare('SELECT k.*,u.username,(SELECT COUNT(*) FROM world_events e WHERE e.attacker_id=k.id OR e.defender_id=k.id) as event_count FROM kingdoms k JOIN users u ON k.user_id=u.id WHERE k.user_id=? ORDER BY k.prosperity DESC');
    $rows->execute([$userId]);
  } else {
    $rows = $pdo->query('SELECT k.*,u.username,(SELECT COUNT(*) FROM world_events e WHERE e.attacker_id=k.id OR e.defender_id=k.id) as event_count FROM kingdoms k JOIN users u ON k.user_id=u.id ORDER BY k.prosperity DESC LIMIT 50');
  }
  echo json_encode(['ok'=>true,'kingdoms'=>$rows->fetchAll()]);
  exit;
}

// ── ACTION: LEADERBOARD ───────────────────────────
if ($action === 'leaderboard') {
  $pdo     = getDB();
  $sort    = $_GET['sort'] ?? 'prosperity';
  $allowed = ['prosperity','military','resources','technology','territory_score','hex_count'];
  if (!in_array($sort, $allowed)) $sort = 'prosperity';
  $kingdoms = $pdo->query("SELECT k.*,u.username FROM kingdoms k JOIN users u ON k.user_id=u.id ORDER BY k.`{$sort}` DESC LIMIT 20")->fetchAll();
  $players  = $pdo->query("SELECT u.username,u.id,COUNT(k.id) as kingdom_count,MAX(k.prosperity) as best_prosperity,MAX(k.military) as best_military,MAX(k.hex_count) as most_hexes FROM users u JOIN kingdoms k ON k.user_id=u.id GROUP BY u.id,u.username ORDER BY best_prosperity DESC LIMIT 10")->fetchAll();
  echo json_encode(['ok'=>true,'kingdoms'=>$kingdoms,'players'=>$players]);
  exit;
}

echo json_encode(['error' => 'Unknown action']);