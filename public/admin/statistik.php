<?php
/**
 * STATISTIKEN
 *
 * Platzhalterseite fuer Statistik-Ansichten.
 *
 * URL: http://localhost/aufgussplan/admin/statistik.php
 */

session_start();

require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/db/connection.php';

// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: login.php');
//     exit;
// }

$db = Database::getInstance()->getConnection();

function buildBarItems(array $labels, array $counts) {
    $items = [];
    foreach ($labels as $index => $label) {
        $items[] = [
            'label' => $label,
            'value' => (int)($counts[$index] ?? 0),
        ];
    }
    return $items;
}

function renderBarList(array $items, $barClass) {
    $maxValue = 0;
    foreach ($items as $item) {
        if ($item['value'] > $maxValue) {
            $maxValue = $item['value'];
        }
    }
    if ($maxValue <= 0) {
        $maxValue = 1;
    }

    echo '<div class="space-y-3">';
    foreach ($items as $item) {
        $label = htmlspecialchars($item['label']);
        $value = (int)$item['value'];
        $width = ($value / $maxValue) * 100;
        $widthStyle = number_format($width, 2, '.', '');
        echo '<div>';
        echo '<div class="flex justify-between text-xs text-gray-500 mb-1">';
        echo '<span class="truncate max-w-[70%]">' . $label . '</span>';
        echo '<span>' . $value . '</span>';
        echo '</div>';
        echo '<div class="h-3 w-full rounded bg-gray-100">';
        echo '<div class="h-3 rounded ' . $barClass . '" style="width:' . $widthStyle . '%"></div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}

function renderLineChart(array $items, $strokeClass, array $options = []) {
    $values = array_map(function ($item) {
        return (int)$item['value'];
    }, $items);

    $maxValue = max(1, max($values));
    $count = count($items);
    $pointWidth = $options['pointWidth'] ?? 48;
    $width = max(320, $count * $pointWidth);
    $height = 360;
    $paddingLeft = 28;
    $paddingRight = 0;
    $paddingY = 14;
    $plotWidth = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height - ($paddingY * 2);
    $gridLines = 6;
    $showArea = $options['area'] ?? false;
    $fillClass = $options['fillClass'] ?? 'fill-blue-100/70';

    $points = [];
    foreach ($items as $index => $item) {
        $x = $paddingLeft;
        if ($count > 1) {
            $x += ($plotWidth * $index) / ($count - 1);
        }
        $value = (int)$item['value'];
        $y = $paddingY + ($plotHeight * (1 - ($value / $maxValue)));
        $points[] = [
            'x' => number_format($x, 2, '.', ''),
            'y' => number_format($y, 2, '.', ''),
            'label' => $item['label'],
            'value' => $value
        ];
    }

    $polyPoints = [];
    foreach ($points as $point) {
        $polyPoints[] = $point['x'] . ',' . $point['y'];
    }

    $areaPath = '';
    if ($showArea && !empty($points)) {
        $first = $points[0];
        $last = $points[count($points) - 1];
        $bottom = number_format($paddingY + $plotHeight, 2, '.', '');
        $areaPath = 'M ' . $first['x'] . ' ' . $first['y'];
        foreach ($points as $point) {
            $areaPath .= ' L ' . $point['x'] . ' ' . $point['y'];
        }
        $areaPath .= ' L ' . $last['x'] . ' ' . $bottom . ' L ' . $first['x'] . ' ' . $bottom . ' Z';
    }

    echo '<div class="w-full overflow-x-auto">';
    echo '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="h-96" style="min-width:' . $width . 'px">';
    for ($i = 0; $i <= $gridLines; $i++) {
        $y = $paddingY + ($plotHeight * $i / $gridLines);
        $yPos = number_format($y, 2, '.', '');
        echo '<line x1="' . $paddingLeft . '" y1="' . $yPos . '" x2="' . ($width - $paddingRight) . '" y2="' . $yPos . '" class="stroke-gray-200" stroke-width="1" />';
    }
    echo '<text x="2" y="' . ($paddingY + 6) . '" class="fill-gray-400 text-[10px]">' . $maxValue . '</text>';
    echo '<text x="2" y="' . ($paddingY + $plotHeight + 4) . '" class="fill-gray-400 text-[10px]">0</text>';

    if ($showArea && $areaPath !== '') {
        echo '<path d="' . $areaPath . '" class="' . $fillClass . '"></path>';
    }

    if (!empty($polyPoints)) {
        echo '<polyline fill="none" stroke-width="3" class="' . $strokeClass . '" points="' . implode(' ', $polyPoints) . '"></polyline>';
    }

    foreach ($points as $point) {
        $label = htmlspecialchars($point['label']);
        $value = (int)$point['value'];
        echo '<circle cx="' . $point['x'] . '" cy="' . $point['y'] . '" r="3.5" class="fill-white stroke-2 ' . $strokeClass . '"></circle>';
        echo '<circle cx="' . $point['x'] . '" cy="' . $point['y'] . '" r="10" class="chart-point fill-transparent stroke-transparent" data-label="' . $label . '" data-value="' . $value . '"></circle>';
    }

    echo '</svg>';
    echo '<div class="mt-3 flex text-xs text-gray-500" style="min-width:' . $width . 'px;padding-left:' . $paddingLeft . 'px;padding-right:' . $paddingRight . 'px">';
    foreach ($items as $item) {
        echo '<span class="flex-1 text-center truncate">' . htmlspecialchars($item['label']) . '</span>';
    }
    echo '</div>';
    echo '</div>';
}

function renderAreaChart(array $items, $strokeClass, $fillClass) {
    renderLineChart($items, $strokeClass, [
        'area' => true,
        'fillClass' => $fillClass
    ]);
}

function renderMultiLineChart(array $series, array $options = []) {
    if (empty($series)) {
        echo '<div class="text-sm text-gray-500">Keine Daten vorhanden.</div>';
        return;
    }

    $baseKey = $options['baseKey'] ?? '';
    $pointWidth = $options['pointWidth'] ?? 48;
    $labels = [];
    if (!empty($series[0]['items'])) {
        foreach ($series[0]['items'] as $item) {
            $labels[] = $item['label'];
        }
    }
    $count = count($labels);
    $width = max(320, $count * $pointWidth);
    $height = 140;
    $paddingLeft = 28;
    $paddingRight = 10;
    $paddingY = 14;
    $plotWidth = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height - ($paddingY * 2);
    $gridLines = 4;

    $maxValue = 1;
    foreach ($series as $set) {
        foreach ($set['items'] as $item) {
            $maxValue = max($maxValue, (int)$item['value']);
        }
    }

    echo '<div class="w-full overflow-x-auto">';
    echo '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="h-36" style="min-width:' . $width . 'px">';
    for ($i = 0; $i <= $gridLines; $i++) {
        $y = $paddingY + ($plotHeight * $i / $gridLines);
        $yPos = number_format($y, 2, '.', '');
        echo '<line x1="' . $paddingLeft . '" y1="' . $yPos . '" x2="' . ($width - $paddingRight) . '" y2="' . $yPos . '" class="stroke-gray-200" stroke-width="1" />';
    }
    echo '<text x="2" y="' . ($paddingY + 6) . '" class="fill-gray-400 text-[10px]">' . $maxValue . '</text>';
    echo '<text x="2" y="' . ($paddingY + $plotHeight + 4) . '" class="fill-gray-400 text-[10px]">0</text>';

    foreach ($series as $set) {
        $items = $set['items'] ?? [];
        $key = $set['key'] ?? '';
        $strokeClass = $set['strokeClass'] ?? 'stroke-blue-500';
        $hidden = $key !== $baseKey;
        $points = [];

        foreach ($items as $index => $item) {
            $x = $paddingLeft;
            if ($count > 1) {
                $x += ($plotWidth * $index) / ($count - 1);
            }
            $value = (int)$item['value'];
            $y = $paddingY + ($plotHeight * (1 - ($value / $maxValue)));
            $points[] = [
                'x' => number_format($x, 2, '.', ''),
                'y' => number_format($y, 2, '.', ''),
                'label' => $item['label'],
                'value' => $value
            ];
        }

        $polyPoints = [];
        foreach ($points as $point) {
            $polyPoints[] = $point['x'] . ',' . $point['y'];
        }

        $hiddenClass = $hidden ? ' hidden' : '';
        if (!empty($polyPoints)) {
            echo '<polyline fill="none" stroke-width="3" class="' . $strokeClass . $hiddenClass . '" data-series="' . htmlspecialchars($key) . '" points="' . implode(' ', $polyPoints) . '"></polyline>';
        }

        foreach ($points as $point) {
            $label = htmlspecialchars($point['label']);
            $value = (int)$point['value'];
            echo '<circle cx="' . $point['x'] . '" cy="' . $point['y'] . '" r="3.5" class="fill-white stroke-2 ' . $strokeClass . $hiddenClass . '" data-series="' . htmlspecialchars($key) . '"></circle>';
        $seriesLabel = htmlspecialchars($set['label'] ?? '');
        echo '<circle cx="' . $point['x'] . '" cy="' . $point['y'] . '" r="10" class="chart-point fill-transparent stroke-transparent' . $hiddenClass . '" data-series="' . htmlspecialchars($key) . '" data-series-label="' . $seriesLabel . '" data-label="' . $label . '" data-value="' . $value . '"></circle>';
        }
    }

    echo '</svg>';
    echo '<div class="mt-3 flex text-xs text-gray-500" style="min-width:' . $width . 'px;padding-left:' . $paddingLeft . 'px;padding-right:' . $paddingRight . 'px">';
    foreach ($labels as $label) {
        echo '<span class="flex-1 text-center truncate">' . htmlspecialchars($label) . '</span>';
    }
    echo '</div>';
    echo '</div>';
}

function renderColumnChart(array $items, $barClass, array $options = []) {
    $values = array_map(function ($item) {
        return (int)$item['value'];
    }, $items);

    $maxValue = max(1, max($values));
    $count = count($items);
    $pointWidth = $options['pointWidth'] ?? 48;
    $width = max(320, $count * $pointWidth);
    $height = 170;
    $paddingLeft = 22;
    $paddingRight = 10;
    $paddingTop = 12;
    $paddingBottom = 26;
    $plotWidth = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height - $paddingTop - $paddingBottom;
    $gridLines = 4;
    $slotWidth = $count > 0 ? ($plotWidth / $count) : $plotWidth;
    $barWidth = min(28, $slotWidth * 0.6);
    $palette = $options['palette'] ?? ['#475569', '#0EA5E9', '#F97316', '#F59E0B', '#EF4444', '#22C55E', '#8B5CF6', '#14B8A6'];

    echo '<div class="w-full overflow-x-auto">';
    echo '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="h-40" style="min-width:' . $width . 'px">';
    for ($i = 0; $i <= $gridLines; $i++) {
        $y = $paddingTop + ($plotHeight * $i / $gridLines);
        $yPos = number_format($y, 2, '.', '');
        echo '<line x1="' . $paddingLeft . '" y1="' . $yPos . '" x2="' . ($width - $paddingRight) . '" y2="' . $yPos . '" class="stroke-gray-200" stroke-width="1" />';
    }
    echo '<text x="2" y="' . ($paddingTop + 6) . '" class="fill-gray-400 text-[10px]">' . $maxValue . '</text>';
    echo '<text x="2" y="' . ($paddingTop + $plotHeight + 4) . '" class="fill-gray-400 text-[10px]">0</text>';

    foreach ($items as $index => $item) {
        $value = (int)$item['value'];
        $xCenter = $paddingLeft + ($slotWidth * $index) + ($slotWidth / 2);
        $barX = $xCenter - ($barWidth / 2);
        $barHeight = $plotHeight * ($value / $maxValue);
        $barY = $paddingTop + ($plotHeight - $barHeight);
        $xPos = number_format($barX, 2, '.', '');
        $yPos = number_format($barY, 2, '.', '');
        $hPos = number_format($barHeight, 2, '.', '');
        $wPos = number_format($barWidth, 2, '.', '');
        $label = htmlspecialchars($item['label']);
        $color = $palette[$index % count($palette)];
        echo '<rect x="' . $xPos . '" y="' . $yPos . '" width="' . $wPos . '" height="' . $hPos . '" class="' . $barClass . ' chart-bar" style="fill:' . $color . ';transition:opacity 0.15s ease;cursor:pointer;" data-label="' . $label . '" data-value="' . $value . '"></rect>';
    }

    echo '</svg>';
    echo '<div class="mt-3 flex text-xs text-gray-500" style="min-width:' . $width . 'px">';
    foreach ($items as $item) {
        echo '<span class="flex-1 text-center truncate">' . htmlspecialchars($item['label']) . '</span>';
    }
    echo '</div>';
    echo '</div>';
}

function mapCountsByKey(array $rows, $keyField, $valueField) {
    $map = [];
    foreach ($rows as $row) {
        $map[$row[$keyField]] = (int)$row[$valueField];
    }
    return $map;
}

function buildPlanFilter(array $selectedPlanIds, $alias = 'a') {
    if (empty($selectedPlanIds)) {
        return ['sql' => '', 'params' => []];
    }
    $placeholders = implode(',', array_fill(0, count($selectedPlanIds), '?'));
    return ['sql' => $alias . '.plan_id IN (' . $placeholders . ')', 'params' => $selectedPlanIds];
}

function buildStatsWhere($periodCondition, $planFilterSql) {
    $parts = [];
    if ($periodCondition) {
        $parts[] = $periodCondition;
    }
    if ($planFilterSql) {
        $parts[] = $planFilterSql;
    }
    return implode(' AND ', $parts);
}

function fetchStatsItems(PDO $db, $labelExpr, $joinSql, $whereSql, array $params) {
    $sql = "SELECT " . $labelExpr . " AS label, COALESCE(SUM(s.anzahl), 0) AS cnt
            FROM statistik s " . $joinSql;
    if ($whereSql) {
        $sql .= " WHERE " . $whereSql;
    }
    $sql .= " GROUP BY " . $labelExpr . " ORDER BY cnt DESC, label ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $items = [];
    foreach ($rows as $row) {
        $items[] = ['label' => $row['label'], 'value' => (int)$row['cnt']];
    }
    return $items;
}

function fetchStaerkeItems(PDO $db, $whereSql, array $params) {
    $sql = "SELECT s.staerke, COALESCE(SUM(s.anzahl), 0) AS cnt
            FROM statistik s";
    if ($whereSql) {
        $sql .= " WHERE " . $whereSql;
    }
    $sql .= " GROUP BY s.staerke";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $map = [];
    $ohne = 0;
    foreach ($rows as $row) {
        if ($row['staerke'] === null) {
            $ohne = (int)$row['cnt'];
            continue;
        }
        $map[(int)$row['staerke']] = (int)$row['cnt'];
    }
    $items = [];
    for ($s = 1; $s <= 6; $s++) {
        $items[] = ['label' => 'St ' . $s, 'value' => $map[$s] ?? 0];
    }
    if ($ohne > 0) {
        $items[] = ['label' => 'Ohne Staerke', 'value' => $ohne];
    }
    return $items;
}

function fetchTimeSeriesMap(PDO $db, $labelExpr, $whereSql, array $params) {
    $sql = "SELECT " . $labelExpr . " AS label, COALESCE(SUM(s.anzahl), 0) AS cnt
            FROM statistik s";
    if ($whereSql) {
        $sql .= " WHERE " . $whereSql;
    }
    $sql .= " GROUP BY " . $labelExpr;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return mapCountsByKey($stmt->fetchAll(), 'label', 'cnt');
}

function buildTimeSeriesItems(array $labels, array $keys, array $map) {
    $counts = [];
    foreach ($keys as $key) {
        $counts[] = $map[$key] ?? 0;
    }
    return buildBarItems($labels, $counts);
}

$planRows = $db->query("SELECT id, name FROM plaene ORDER BY name ASC")->fetchAll();
$allPlanIds = [];
foreach ($planRows as $planRow) {
    $allPlanIds[] = (int)$planRow['id'];
}

$selectedPlanIds = [];
if (isset($_GET['plans']) && $_GET['plans'] !== '' && $_GET['plans'] !== 'all') {
    $rawPlanIds = array_filter(array_map('trim', explode(',', $_GET['plans'])));
    foreach ($rawPlanIds as $rawPlanId) {
        if (ctype_digit($rawPlanId)) {
            $selectedPlanIds[] = (int)$rawPlanId;
        }
    }
    if (!empty($allPlanIds)) {
        $selectedPlanIds = array_values(array_intersect($selectedPlanIds, $allPlanIds));
    }
}

if (!empty($allPlanIds) && (!empty($selectedPlanIds) && count($selectedPlanIds) < count($allPlanIds))) {
    $planFilter = buildPlanFilter($selectedPlanIds, 'a');
    $planFilterStats = buildPlanFilter($selectedPlanIds, 's');
} else {
    $selectedPlanIds = [];
    $planFilter = buildPlanFilter([]);
    $planFilterStats = buildPlanFilter([], 's');
}

// Aufguesse pro Tag (letzte 7 Tage)
$byDayRows = $db->prepare(
    "SELECT DATE(datum) AS label, COALESCE(SUM(anzahl), 0) AS cnt
     FROM statistik s
     WHERE s.datum >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
     . ($planFilterStats['sql'] ? " AND " . $planFilterStats['sql'] : "") . "
     GROUP BY DATE(datum)
     ORDER BY label ASC"
);
$byDayRows->execute($planFilterStats['params']);
$byDayMap = mapCountsByKey($byDayRows->fetchAll(), 'label', 'cnt');
$dayLabels = [];
$dayCounts = [];
$dayKeys = [];
$dayStart = new DateTime('today');
$dayStart->modify('-6 days');
for ($i = 0; $i < 7; $i++) {
    $label = $dayStart->format('d.m');
    $key = $dayStart->format('Y-m-d');
    $dayLabels[] = $label;
    $dayKeys[] = $key;
    $dayCounts[] = $byDayMap[$key] ?? 0;
    $dayStart->modify('+1 day');
}
$byDayItems = buildBarItems($dayLabels, $dayCounts);

// Aufguesse pro Woche (letzte 8 Wochen)
$byWeekRows = $db->prepare(
    "SELECT YEARWEEK(datum, 3) AS yw, COALESCE(SUM(anzahl), 0) AS cnt
     FROM statistik s
     WHERE s.datum >= DATE_SUB(CURDATE(), INTERVAL 7 WEEK)"
     . ($planFilterStats['sql'] ? " AND " . $planFilterStats['sql'] : "") . "
     GROUP BY YEARWEEK(datum, 3)
     ORDER BY yw ASC"
);
$byWeekRows->execute($planFilterStats['params']);
$byWeekMap = mapCountsByKey($byWeekRows->fetchAll(), 'yw', 'cnt');
$weekLabels = [];
$weekCounts = [];
$weekKeys = [];
$weekStart = new DateTime('monday this week');
$weekStart->modify('-7 weeks');
for ($i = 0; $i < 8; $i++) {
    $key = $weekStart->format('oW');
    $weekLabels[] = 'KW ' . $weekStart->format('W');
    $weekKeys[] = $key;
    $weekCounts[] = $byWeekMap[$key] ?? 0;
    $weekStart->modify('+1 week');
}
$byWeekItems = buildBarItems($weekLabels, $weekCounts);

// Aufguesse pro Monat (letzte 12 Monate)
$byMonthRows = $db->prepare(
    "SELECT DATE_FORMAT(datum, '%Y-%m') AS ym, COALESCE(SUM(anzahl), 0) AS cnt
     FROM statistik s
     WHERE s.datum >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)"
     . ($planFilterStats['sql'] ? " AND " . $planFilterStats['sql'] : "") . "
     GROUP BY DATE_FORMAT(datum, '%Y-%m')
     ORDER BY ym ASC"
);
$byMonthRows->execute($planFilterStats['params']);
$byMonthMap = mapCountsByKey($byMonthRows->fetchAll(), 'ym', 'cnt');
$monthLabels = [];
$monthCounts = [];
$monthKeys = [];
$monthStart = new DateTime('first day of this month');
$monthStart->modify('-11 months');
for ($i = 0; $i < 12; $i++) {
    $key = $monthStart->format('Y-m');
    $monthLabels[] = $monthStart->format('m/Y');
    $monthKeys[] = $key;
    $monthCounts[] = $byMonthMap[$key] ?? 0;
    $monthStart->modify('+1 month');
}
$byMonthItems = buildBarItems($monthLabels, $monthCounts);

// Aufguesse pro Jahr
$byYearRows = $db->prepare(
    "SELECT YEAR(datum) AS y, COALESCE(SUM(anzahl), 0) AS cnt
     FROM statistik s"
     . ($planFilterStats['sql'] ? " WHERE " . $planFilterStats['sql'] : "") . "
     GROUP BY YEAR(datum)
     ORDER BY y ASC"
);
$byYearRows->execute($planFilterStats['params']);
$yearLabels = [];
$yearCounts = [];
foreach ($byYearRows->fetchAll() as $row) {
    $yearLabels[] = (string)$row['y'];
    $yearCounts[] = (int)$row['cnt'];
}
$yearKeys = $yearLabels;
$byYearItems = buildBarItems($yearLabels, $yearCounts);

// Wie oft welcher Aufguss
$aufgussNameStmt = $db->prepare(
    "SELECT COALESCE(an.name, 'Ohne Name') AS label, COUNT(*) AS cnt
     FROM aufguesse a
     LEFT JOIN aufguss_namen an ON a.aufguss_name_id = an.id"
    . ($planFilter['sql'] ? " WHERE " . $planFilter['sql'] : "") . "
     GROUP BY COALESCE(an.name, 'Ohne Name')
     ORDER BY cnt DESC, label ASC"
);
$aufgussNameStmt->execute($planFilter['params']);
$aufgussNameRows = $aufgussNameStmt->fetchAll();
$aufgussNameItems = [];
foreach ($aufgussNameRows as $row) {
    $aufgussNameItems[] = ['label' => $row['label'], 'value' => (int)$row['cnt']];
}

// Wie oft ein Duftmittel verwendet wurde
$duftmittelStmt = $db->prepare(
    "SELECT COALESCE(d.name, 'Ohne Duftmittel') AS label, COUNT(*) AS cnt
     FROM aufguesse a
     LEFT JOIN duftmittel d ON a.duftmittel_id = d.id
     " . ($planFilter['sql'] ? "WHERE " . $planFilter['sql'] : "") . "
     GROUP BY COALESCE(d.name, 'Ohne Duftmittel')
     ORDER BY cnt DESC, label ASC"
);
$duftmittelStmt->execute($planFilter['params']);
$duftmittelRows = $duftmittelStmt->fetchAll();
$duftmittelItems = [];
foreach ($duftmittelRows as $row) {
    $duftmittelItems[] = ['label' => $row['label'], 'value' => (int)$row['cnt']];
}

// Wie oft welche Sauna genutzt wurde
$saunaStmt = $db->prepare(
    "SELECT COALESCE(s.name, 'Ohne Sauna') AS label, COUNT(*) AS cnt
     FROM aufguesse a
     LEFT JOIN saunen s ON a.sauna_id = s.id
     " . ($planFilter['sql'] ? "WHERE " . $planFilter['sql'] : "") . "
     GROUP BY COALESCE(s.name, 'Ohne Sauna')
     ORDER BY cnt DESC, label ASC"
);
$saunaStmt->execute($planFilter['params']);
$saunaRows = $saunaStmt->fetchAll();
$saunaItems = [];
foreach ($saunaRows as $row) {
    $saunaItems[] = ['label' => $row['label'], 'value' => (int)$row['cnt']];
}

// Aufguesse nach Staerke
$staerkeStmt = $db->prepare(
    "SELECT staerke, COUNT(*) AS cnt
     FROM aufguesse a"
     . ($planFilter['sql'] ? " WHERE " . $planFilter['sql'] : "") . "
     GROUP BY staerke"
);
$staerkeStmt->execute($planFilter['params']);
$staerkeRows = $staerkeStmt->fetchAll();
$staerkeMap = [];
$ohneStaerkeCount = 0;
foreach ($staerkeRows as $row) {
    if ($row['staerke'] === null) {
        $ohneStaerkeCount = (int)$row['cnt'];
        continue;
    }
    $staerkeMap[(int)$row['staerke']] = (int)$row['cnt'];
}
$staerkeItems = [];
for ($s = 0; $s <= 6; $s++) {
    $staerkeItems[] = ['label' => 'St ' . $s, 'value' => $staerkeMap[$s] ?? 0];
}
if ($ohneStaerkeCount > 0) {
    $staerkeItems[] = ['label' => 'Ohne Staerke', 'value' => $ohneStaerkeCount];
}

// Statistik nach Zeitraum (aus statistik-Tabelle)
$periodDay = "s.datum = CURDATE()";
$periodWeek = "s.datum >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
$periodMonth = "s.datum >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
$periodYear = "s.datum >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";

$duftmittelList = $db->query("SELECT id, name FROM duftmittel ORDER BY name ASC")->fetchAll();
$saunaList = $db->query("SELECT id, name FROM saunen ORDER BY name ASC")->fetchAll();

$duftDayMap = fetchTimeSeriesMap($db, "DATE(datum)", buildStatsWhere($periodDay . " AND s.duftmittel_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);
$duftWeekMap = fetchTimeSeriesMap($db, "YEARWEEK(datum, 3)", buildStatsWhere($periodWeek . " AND s.duftmittel_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);
$duftMonthMap = fetchTimeSeriesMap($db, "DATE_FORMAT(datum, '%Y-%m')", buildStatsWhere($periodMonth . " AND s.duftmittel_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);
$duftYearMap = fetchTimeSeriesMap($db, "YEAR(datum)", buildStatsWhere("s.duftmittel_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);

$saunaDayMap = fetchTimeSeriesMap($db, "DATE(datum)", buildStatsWhere($periodDay . " AND s.sauna_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);
$saunaWeekMap = fetchTimeSeriesMap($db, "YEARWEEK(datum, 3)", buildStatsWhere($periodWeek . " AND s.sauna_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);
$saunaMonthMap = fetchTimeSeriesMap($db, "DATE_FORMAT(datum, '%Y-%m')", buildStatsWhere($periodMonth . " AND s.sauna_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);
$saunaYearMap = fetchTimeSeriesMap($db, "YEAR(datum)", buildStatsWhere("s.sauna_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);

$aufgussDayMap = fetchTimeSeriesMap($db, "DATE(datum)", buildStatsWhere($periodDay . " AND s.aufguss_name_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);
$aufgussWeekMap = fetchTimeSeriesMap($db, "YEARWEEK(datum, 3)", buildStatsWhere($periodWeek . " AND s.aufguss_name_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);
$aufgussMonthMap = fetchTimeSeriesMap($db, "DATE_FORMAT(datum, '%Y-%m')", buildStatsWhere($periodMonth . " AND s.aufguss_name_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);
$aufgussYearMap = fetchTimeSeriesMap($db, "YEAR(datum)", buildStatsWhere("s.aufguss_name_id IS NOT NULL", $planFilterStats['sql']), $planFilterStats['params']);

$staerkeDayItemsByLevel = [];
$staerkeWeekItemsByLevel = [];
$staerkeMonthItemsByLevel = [];
$staerkeYearItemsByLevel = [];
for ($level = 1; $level <= 6; $level++) {
    $staerkeDayMap = fetchTimeSeriesMap(
        $db,
        "DATE(datum)",
        buildStatsWhere($periodDay . " AND s.staerke = " . $level, $planFilterStats['sql']),
        $planFilterStats['params']
    );
    $staerkeWeekMap = fetchTimeSeriesMap(
        $db,
        "YEARWEEK(datum, 3)",
        buildStatsWhere($periodWeek . " AND s.staerke = " . $level, $planFilterStats['sql']),
        $planFilterStats['params']
    );
    $staerkeMonthMap = fetchTimeSeriesMap(
        $db,
        "DATE_FORMAT(datum, '%Y-%m')",
        buildStatsWhere($periodMonth . " AND s.staerke = " . $level, $planFilterStats['sql']),
        $planFilterStats['params']
    );
    $staerkeYearMap = fetchTimeSeriesMap(
        $db,
        "YEAR(datum)",
        buildStatsWhere("s.staerke = " . $level, $planFilterStats['sql']),
        $planFilterStats['params']
    );

    $staerkeDayItemsByLevel[$level] = buildTimeSeriesItems($dayLabels, $dayKeys, $staerkeDayMap);
    $staerkeWeekItemsByLevel[$level] = buildTimeSeriesItems($weekLabels, $weekKeys, $staerkeWeekMap);
    $staerkeMonthItemsByLevel[$level] = buildTimeSeriesItems($monthLabels, $monthKeys, $staerkeMonthMap);
    $staerkeYearItemsByLevel[$level] = buildTimeSeriesItems($yearLabels, $yearKeys, $staerkeYearMap);
}

$duftDayItems = buildTimeSeriesItems($dayLabels, $dayKeys, $duftDayMap);
$duftWeekItems = buildTimeSeriesItems($weekLabels, $weekKeys, $duftWeekMap);
$duftMonthItems = buildTimeSeriesItems($monthLabels, $monthKeys, $duftMonthMap);
$duftYearItems = buildTimeSeriesItems($yearLabels, $yearKeys, $duftYearMap);

$saunaDayItems = buildTimeSeriesItems($dayLabels, $dayKeys, $saunaDayMap);
$saunaWeekItems = buildTimeSeriesItems($weekLabels, $weekKeys, $saunaWeekMap);
$saunaMonthItems = buildTimeSeriesItems($monthLabels, $monthKeys, $saunaMonthMap);
$saunaYearItems = buildTimeSeriesItems($yearLabels, $yearKeys, $saunaYearMap);

$aufgussDayItems = buildTimeSeriesItems($dayLabels, $dayKeys, $aufgussDayMap);
$aufgussWeekItems = buildTimeSeriesItems($weekLabels, $weekKeys, $aufgussWeekMap);
$aufgussMonthItems = buildTimeSeriesItems($monthLabels, $monthKeys, $aufgussMonthMap);
$aufgussYearItems = buildTimeSeriesItems($yearLabels, $yearKeys, $aufgussYearMap);

$duftItemsById = [
    'day' => [],
    'week' => [],
    'month' => [],
    'year' => []
];
$saunaItemsById = [
    'day' => [],
    'week' => [],
    'month' => [],
    'year' => []
];
foreach ($duftmittelList as $duft) {
    $id = (int)$duft['id'];
    $duftDayMapId = fetchTimeSeriesMap($db, "DATE(datum)", buildStatsWhere($periodDay . " AND s.duftmittel_id = " . $id, $planFilterStats['sql']), $planFilterStats['params']);
    $duftWeekMapId = fetchTimeSeriesMap($db, "YEARWEEK(datum, 3)", buildStatsWhere($periodWeek . " AND s.duftmittel_id = " . $id, $planFilterStats['sql']), $planFilterStats['params']);
    $duftMonthMapId = fetchTimeSeriesMap($db, "DATE_FORMAT(datum, '%Y-%m')", buildStatsWhere($periodMonth . " AND s.duftmittel_id = " . $id, $planFilterStats['sql']), $planFilterStats['params']);
    $duftYearMapId = fetchTimeSeriesMap($db, "YEAR(datum)", buildStatsWhere("s.duftmittel_id = " . $id, $planFilterStats['sql']), $planFilterStats['params']);

    $duftItemsById['day'][$id] = buildTimeSeriesItems($dayLabels, $dayKeys, $duftDayMapId);
    $duftItemsById['week'][$id] = buildTimeSeriesItems($weekLabels, $weekKeys, $duftWeekMapId);
    $duftItemsById['month'][$id] = buildTimeSeriesItems($monthLabels, $monthKeys, $duftMonthMapId);
    $duftItemsById['year'][$id] = buildTimeSeriesItems($yearLabels, $yearKeys, $duftYearMapId);
}
foreach ($saunaList as $sauna) {
    $id = (int)$sauna['id'];
    $saunaDayMapId = fetchTimeSeriesMap($db, "DATE(datum)", buildStatsWhere($periodDay . " AND s.sauna_id = " . $id, $planFilterStats['sql']), $planFilterStats['params']);
    $saunaWeekMapId = fetchTimeSeriesMap($db, "YEARWEEK(datum, 3)", buildStatsWhere($periodWeek . " AND s.sauna_id = " . $id, $planFilterStats['sql']), $planFilterStats['params']);
    $saunaMonthMapId = fetchTimeSeriesMap($db, "DATE_FORMAT(datum, '%Y-%m')", buildStatsWhere($periodMonth . " AND s.sauna_id = " . $id, $planFilterStats['sql']), $planFilterStats['params']);
    $saunaYearMapId = fetchTimeSeriesMap($db, "YEAR(datum)", buildStatsWhere("s.sauna_id = " . $id, $planFilterStats['sql']), $planFilterStats['params']);

    $saunaItemsById['day'][$id] = buildTimeSeriesItems($dayLabels, $dayKeys, $saunaDayMapId);
    $saunaItemsById['week'][$id] = buildTimeSeriesItems($weekLabels, $weekKeys, $saunaWeekMapId);
    $saunaItemsById['month'][$id] = buildTimeSeriesItems($monthLabels, $monthKeys, $saunaMonthMapId);
    $saunaItemsById['year'][$id] = buildTimeSeriesItems($yearLabels, $yearKeys, $saunaYearMapId);
}

$seriesDays = [
    ['key' => 'base', 'label' => 'Aufguesse gesamt', 'items' => $byDayItems, 'strokeClass' => 'stroke-blue-500'],
    ['key' => 'aufguss', 'label' => 'Aufguesse', 'items' => $aufgussDayItems, 'strokeClass' => 'stroke-orange-500'],
];
foreach ($duftmittelList as $duft) {
    $seriesDays[] = [
        'key' => 'duft-' . $duft['id'],
        'label' => 'Duftmittel: ' . $duft['name'],
        'items' => $duftItemsById['day'][(int)$duft['id']] ?? [],
        'strokeClass' => 'stroke-sky-500'
    ];
}
foreach ($saunaList as $sauna) {
    $seriesDays[] = [
        'key' => 'sauna-' . $sauna['id'],
        'label' => 'Sauna: ' . $sauna['name'],
        'items' => $saunaItemsById['day'][(int)$sauna['id']] ?? [],
        'strokeClass' => 'stroke-emerald-500'
    ];
}
for ($level = 1; $level <= 6; $level++) {
    $seriesDays[] = [
        'key' => 'staerke-' . $level,
        'label' => 'Staerke ' . $level,
        'items' => $staerkeDayItemsByLevel[$level],
        'strokeClass' => 'stroke-slate-' . (300 + ($level * 100))
    ];
}

$seriesWeeks = [
    ['key' => 'base', 'label' => 'Aufguesse gesamt', 'items' => $byWeekItems, 'strokeClass' => 'stroke-indigo-500'],
    ['key' => 'aufguss', 'label' => 'Aufguesse', 'items' => $aufgussWeekItems, 'strokeClass' => 'stroke-orange-600'],
];
foreach ($duftmittelList as $duft) {
    $seriesWeeks[] = [
        'key' => 'duft-' . $duft['id'],
        'label' => 'Duftmittel: ' . $duft['name'],
        'items' => $duftItemsById['week'][(int)$duft['id']] ?? [],
        'strokeClass' => 'stroke-sky-600'
    ];
}
foreach ($saunaList as $sauna) {
    $seriesWeeks[] = [
        'key' => 'sauna-' . $sauna['id'],
        'label' => 'Sauna: ' . $sauna['name'],
        'items' => $saunaItemsById['week'][(int)$sauna['id']] ?? [],
        'strokeClass' => 'stroke-emerald-600'
    ];
}
for ($level = 1; $level <= 6; $level++) {
    $seriesWeeks[] = [
        'key' => 'staerke-' . $level,
        'label' => 'Staerke ' . $level,
        'items' => $staerkeWeekItemsByLevel[$level],
        'strokeClass' => 'stroke-slate-' . (300 + ($level * 100))
    ];
}

$seriesMonths = [
    ['key' => 'base', 'label' => 'Aufguesse gesamt', 'items' => $byMonthItems, 'strokeClass' => 'stroke-teal-500'],
    ['key' => 'aufguss', 'label' => 'Aufguesse', 'items' => $aufgussMonthItems, 'strokeClass' => 'stroke-orange-700'],
];
foreach ($duftmittelList as $duft) {
    $seriesMonths[] = [
        'key' => 'duft-' . $duft['id'],
        'label' => 'Duftmittel: ' . $duft['name'],
        'items' => $duftItemsById['month'][(int)$duft['id']] ?? [],
        'strokeClass' => 'stroke-sky-700'
    ];
}
foreach ($saunaList as $sauna) {
    $seriesMonths[] = [
        'key' => 'sauna-' . $sauna['id'],
        'label' => 'Sauna: ' . $sauna['name'],
        'items' => $saunaItemsById['month'][(int)$sauna['id']] ?? [],
        'strokeClass' => 'stroke-emerald-700'
    ];
}
for ($level = 1; $level <= 6; $level++) {
    $seriesMonths[] = [
        'key' => 'staerke-' . $level,
        'label' => 'Staerke ' . $level,
        'items' => $staerkeMonthItemsByLevel[$level],
        'strokeClass' => 'stroke-slate-' . (300 + ($level * 100))
    ];
}

$seriesYears = [
    ['key' => 'base', 'label' => 'Aufguesse gesamt', 'items' => $byYearItems, 'strokeClass' => 'stroke-emerald-500'],
    ['key' => 'aufguss', 'label' => 'Aufguesse', 'items' => $aufgussYearItems, 'strokeClass' => 'stroke-orange-800'],
];
foreach ($duftmittelList as $duft) {
    $seriesYears[] = [
        'key' => 'duft-' . $duft['id'],
        'label' => 'Duftmittel: ' . $duft['name'],
        'items' => $duftItemsById['year'][(int)$duft['id']] ?? [],
        'strokeClass' => 'stroke-sky-800'
    ];
}
foreach ($saunaList as $sauna) {
    $seriesYears[] = [
        'key' => 'sauna-' . $sauna['id'],
        'label' => 'Sauna: ' . $sauna['name'],
        'items' => $saunaItemsById['year'][(int)$sauna['id']] ?? [],
        'strokeClass' => 'stroke-emerald-800'
    ];
}
for ($level = 1; $level <= 6; $level++) {
    $seriesYears[] = [
        'key' => 'staerke-' . $level,
        'label' => 'Staerke ' . $level,
        'items' => $staerkeYearItemsByLevel[$level],
        'strokeClass' => 'stroke-slate-' . (300 + ($level * 100))
    ];
}

$datasets = [
    'days' => ['title' => 'Aufguesse pro Tag (7 Tage)', 'items' => $byDayItems],
    'weeks' => ['title' => 'Aufguesse pro Woche (8 Wochen)', 'items' => $byWeekItems],
    'months' => ['title' => 'Aufguesse pro Monat (12 Monate)', 'items' => $byMonthItems],
    'years' => ['title' => 'Aufguesse pro Jahr', 'items' => $byYearItems],
    'staerke' => ['title' => 'Aufguesse nach Staerke', 'items' => $staerkeItems],
    'aufguss' => ['title' => 'Wie oft welcher Aufguss', 'items' => $aufgussNameItems],
    'duftmittel' => ['title' => 'Wie oft Duftmittel verwendet', 'items' => $duftmittelItems],
    'sauna' => ['title' => 'Wie oft welche Sauna', 'items' => $saunaItems],
    'duft_day' => ['title' => 'Duftmittel heute', 'items' => $duftDayItems],
    'duft_week' => ['title' => 'Duftmittel letzte 7 Tage', 'items' => $duftWeekItems],
    'duft_month' => ['title' => 'Duftmittel letzter Monat', 'items' => $duftMonthItems],
    'duft_year' => ['title' => 'Duftmittel letztes Jahr', 'items' => $duftYearItems],
    'sauna_day' => ['title' => 'Sauna heute', 'items' => $saunaDayItems],
    'sauna_week' => ['title' => 'Sauna letzte 7 Tage', 'items' => $saunaWeekItems],
    'sauna_month' => ['title' => 'Sauna letzter Monat', 'items' => $saunaMonthItems],
    'sauna_year' => ['title' => 'Sauna letztes Jahr', 'items' => $saunaYearItems],
    'aufguss_day' => ['title' => 'Aufguesse heute', 'items' => $aufgussDayItems],
    'aufguss_week' => ['title' => 'Aufguesse letzte 7 Tage', 'items' => $aufgussWeekItems],
    'aufguss_month' => ['title' => 'Aufguesse letzter Monat', 'items' => $aufgussMonthItems],
    'aufguss_year' => ['title' => 'Aufguesse letztes Jahr', 'items' => $aufgussYearItems],
    'staerke_day_1' => ['title' => 'Staerke 1 heute', 'items' => $staerkeDayItemsByLevel[1]],
    'staerke_day_2' => ['title' => 'Staerke 2 heute', 'items' => $staerkeDayItemsByLevel[2]],
    'staerke_day_3' => ['title' => 'Staerke 3 heute', 'items' => $staerkeDayItemsByLevel[3]],
    'staerke_day_4' => ['title' => 'Staerke 4 heute', 'items' => $staerkeDayItemsByLevel[4]],
    'staerke_day_5' => ['title' => 'Staerke 5 heute', 'items' => $staerkeDayItemsByLevel[5]],
    'staerke_day_6' => ['title' => 'Staerke 6 heute', 'items' => $staerkeDayItemsByLevel[6]],
    'staerke_week_1' => ['title' => 'Staerke 1 letzte 7 Tage', 'items' => $staerkeWeekItemsByLevel[1]],
    'staerke_week_2' => ['title' => 'Staerke 2 letzte 7 Tage', 'items' => $staerkeWeekItemsByLevel[2]],
    'staerke_week_3' => ['title' => 'Staerke 3 letzte 7 Tage', 'items' => $staerkeWeekItemsByLevel[3]],
    'staerke_week_4' => ['title' => 'Staerke 4 letzte 7 Tage', 'items' => $staerkeWeekItemsByLevel[4]],
    'staerke_week_5' => ['title' => 'Staerke 5 letzte 7 Tage', 'items' => $staerkeWeekItemsByLevel[5]],
    'staerke_week_6' => ['title' => 'Staerke 6 letzte 7 Tage', 'items' => $staerkeWeekItemsByLevel[6]],
    'staerke_month_1' => ['title' => 'Staerke 1 letzter Monat', 'items' => $staerkeMonthItemsByLevel[1]],
    'staerke_month_2' => ['title' => 'Staerke 2 letzter Monat', 'items' => $staerkeMonthItemsByLevel[2]],
    'staerke_month_3' => ['title' => 'Staerke 3 letzter Monat', 'items' => $staerkeMonthItemsByLevel[3]],
    'staerke_month_4' => ['title' => 'Staerke 4 letzter Monat', 'items' => $staerkeMonthItemsByLevel[4]],
    'staerke_month_5' => ['title' => 'Staerke 5 letzter Monat', 'items' => $staerkeMonthItemsByLevel[5]],
    'staerke_month_6' => ['title' => 'Staerke 6 letzter Monat', 'items' => $staerkeMonthItemsByLevel[6]],
    'staerke_year_1' => ['title' => 'Staerke 1 letztes Jahr', 'items' => $staerkeYearItemsByLevel[1]],
    'staerke_year_2' => ['title' => 'Staerke 2 letztes Jahr', 'items' => $staerkeYearItemsByLevel[2]],
    'staerke_year_3' => ['title' => 'Staerke 3 letztes Jahr', 'items' => $staerkeYearItemsByLevel[3]],
    'staerke_year_4' => ['title' => 'Staerke 4 letztes Jahr', 'items' => $staerkeYearItemsByLevel[4]],
    'staerke_year_5' => ['title' => 'Staerke 5 letztes Jahr', 'items' => $staerkeYearItemsByLevel[5]],
    'staerke_year_6' => ['title' => 'Staerke 6 letztes Jahr', 'items' => $staerkeYearItemsByLevel[6]],
];

foreach ($duftmittelList as $duft) {
    $id = (int)$duft['id'];
    $datasets['duft_day_' . $id] = ['title' => 'Duftmittel ' . $duft['name'] . ' heute', 'items' => $duftItemsById['day'][$id]];
    $datasets['duft_week_' . $id] = ['title' => 'Duftmittel ' . $duft['name'] . ' letzte 7 Tage', 'items' => $duftItemsById['week'][$id]];
    $datasets['duft_month_' . $id] = ['title' => 'Duftmittel ' . $duft['name'] . ' letzter Monat', 'items' => $duftItemsById['month'][$id]];
    $datasets['duft_year_' . $id] = ['title' => 'Duftmittel ' . $duft['name'] . ' letztes Jahr', 'items' => $duftItemsById['year'][$id]];
}
foreach ($saunaList as $sauna) {
    $id = (int)$sauna['id'];
    $datasets['sauna_day_' . $id] = ['title' => 'Sauna ' . $sauna['name'] . ' heute', 'items' => $saunaItemsById['day'][$id]];
    $datasets['sauna_week_' . $id] = ['title' => 'Sauna ' . $sauna['name'] . ' letzte 7 Tage', 'items' => $saunaItemsById['week'][$id]];
    $datasets['sauna_month_' . $id] = ['title' => 'Sauna ' . $sauna['name'] . ' letzter Monat', 'items' => $saunaItemsById['month'][$id]];
    $datasets['sauna_year_' . $id] = ['title' => 'Sauna ' . $sauna['name'] . ' letztes Jahr', 'items' => $saunaItemsById['year'][$id]];
}

function renderDataAccordion($id, $title, array $items) {
    $panelId = 'accordion-' . $id;
    echo '<div class="relative">';
    echo '<button type="button" class="flex w-full items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm" data-accordion-toggle="' . htmlspecialchars($panelId) . '" aria-expanded="false">';
    echo '<span>' . htmlspecialchars($title) . '</span>';
    echo '<span class="text-gray-400 transition" data-accordion-icon="' . htmlspecialchars($panelId) . '">▼</span>';
    echo '</button>';
    echo '<div id="' . htmlspecialchars($panelId) . '" class="absolute left-0 right-0 z-50 mt-0 hidden w-full rounded-lg border border-gray-200 bg-white p-4 shadow-lg">';
    echo '<div class="mt-3 max-h-72 overflow-auto">';
    echo '<table class="min-w-full text-sm">';
    echo '<thead>';
    echo '<tr class="text-left text-gray-500">';
    echo '<th class="py-2 pr-4">Label</th>';
    echo '<th class="py-2">Wert</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody class="divide-y divide-gray-100">';
    foreach ($items as $item) {
        echo '<tr>';
        echo '<td class="py-2 pr-4 text-gray-700">' . htmlspecialchars($item['label']) . '</td>';
        echo '<td class="py-2 text-gray-700">' . (int)$item['value'] . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiken - Aufgussplan</title>
    <link rel="stylesheet" href="../dist/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-6">Statistiken</h2>

        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Zeitreihen (ein-/ausblenden)</h3>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-sm font-semibold text-gray-700">Zeitreihen anzeigen:</span>
                <button type="button" class="plan-select-btn" data-toggle-target="period-days" data-toggle-group="period" aria-pressed="true">
                    Tage
                </button>
                <button type="button" class="plan-select-btn" data-toggle-target="period-weeks" data-toggle-group="period" aria-pressed="false">
                    Wochen
                </button>
                <button type="button" class="plan-select-btn" data-toggle-target="period-months" data-toggle-group="period" aria-pressed="false">
                    Monate
                </button>
                <button type="button" class="plan-select-btn" data-toggle-target="period-years" data-toggle-group="period" aria-pressed="false">
                    Jahre
                </button>
                </div>
            </div>
        </div>

        <div id="period-days" class="mb-8" data-period="days">
            <div class="flex items-center gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Tage</h3>
                <div class="flex-1 border-t border-gray-200"></div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div id="apex-chart-days" class="apex-chart"></div>
            </div>
        </div>

        <div id="period-weeks" class="mb-8 hidden" data-period="weeks">
            <div class="flex items-center gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Wochen</h3>
                <div class="flex-1 border-t border-gray-200"></div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div id="apex-chart-weeks" class="apex-chart"></div>
            </div>
        </div>

        <div id="period-months" class="mb-8 hidden" data-period="months">
            <div class="flex items-center gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Monate</h3>
                <div class="flex-1 border-t border-gray-200"></div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div id="apex-chart-months" class="apex-chart"></div>
            </div>
        </div>

        <div id="period-years" class="mb-8 hidden" data-period="years">
            <div class="flex items-center gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Jahre</h3>
                <div class="flex-1 border-t border-gray-200"></div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div id="apex-chart-years" class="apex-chart"></div>
            </div>
        </div>

        <div class="my-8 border-t border-gray-200"></div>

        <h3 id="more-stats" class="text-lg font-semibold text-gray-900 mb-4">Weitere Statistiken (immer sichtbar)</h3>
        <?php if (!empty($planRows)) : ?>
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Plaene (ein-/ausblenden)</h3>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="text-sm font-semibold text-gray-700">Plaene anzeigen:</span>
                    <?php foreach ($planRows as $planRow) :
                        $planId = (int)$planRow['id'];
                        $isActive = empty($selectedPlanIds) || in_array($planId, $selectedPlanIds, true);
                    ?>
                        <button type="button" class="plan-select-btn<?php echo $isActive ? ' is-active' : ''; ?>" data-plan-id="<?php echo $planId; ?>" aria-pressed="<?php echo $isActive ? 'true' : 'false'; ?>">
                            <?php echo htmlspecialchars($planRow['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Aufguesse nach Staerke</h3>
                <div class="max-h-96 overflow-y-auto pr-2">
                    <?php renderColumnChart($staerkeItems, 'fill-slate-500'); ?>
                </div>
                <div class="mt-4">
                    <?php renderDataAccordion('staerke', $datasets['staerke']['title'], $datasets['staerke']['items']); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Wie oft welcher Aufguss</h3>
                <div class="max-h-96 overflow-y-auto pr-2">
                    <?php renderColumnChart($aufgussNameItems, 'fill-orange-500'); ?>
                </div>
                <div class="mt-4">
                    <?php renderDataAccordion('aufguss', $datasets['aufguss']['title'], $datasets['aufguss']['items']); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Wie oft Duftmittel verwendet</h3>
                <div class="max-h-96 overflow-y-auto pr-2">
                    <?php renderColumnChart($duftmittelItems, 'fill-amber-500'); ?>
                </div>
                <div class="mt-4">
                    <?php renderDataAccordion('duftmittel', $datasets['duftmittel']['title'], $datasets['duftmittel']['items']); ?>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">Wie oft welche Sauna</h3>
                <div class="max-h-96 overflow-y-auto pr-2">
                    <?php renderColumnChart($saunaItems, 'fill-rose-500'); ?>
                </div>
                <div class="mt-4">
                    <?php renderDataAccordion('sauna', $datasets['sauna']['title'], $datasets['sauna']['items']); ?>
                </div>
            </div>
        </div>
    </div>

    <div id="chart-tooltip" class="fixed z-50 hidden rounded bg-gray-900 text-white text-xs px-2 py-1 shadow pointer-events-none"></div>

    <script src="../assets/vendor/apexcharts.min.js"></script>
    <script>
        const chartData = <?php echo json_encode([
            'days' => [
                'categories' => $dayLabels,
                'series' => array_map(function ($set) {
                    return [
                        'key' => $set['key'],
                        'name' => $set['label'],
                        'data' => array_map(function ($item) {
                            return (int)$item['value'];
                        }, $set['items'] ?? [])
                    ];
                }, $seriesDays)
            ],
            'weeks' => [
                'categories' => $weekLabels,
                'series' => array_map(function ($set) {
                    return [
                        'key' => $set['key'],
                        'name' => $set['label'],
                        'data' => array_map(function ($item) {
                            return (int)$item['value'];
                        }, $set['items'] ?? [])
                    ];
                }, $seriesWeeks)
            ],
            'months' => [
                'categories' => $monthLabels,
                'series' => array_map(function ($set) {
                    return [
                        'key' => $set['key'],
                        'name' => $set['label'],
                        'data' => array_map(function ($item) {
                            return (int)$item['value'];
                        }, $set['items'] ?? [])
                    ];
                }, $seriesMonths)
            ],
            'years' => [
                'categories' => $yearLabels,
                'series' => array_map(function ($set) {
                    return [
                        'key' => $set['key'],
                        'name' => $set['label'],
                        'data' => array_map(function ($item) {
                            return (int)$item['value'];
                        }, $set['items'] ?? [])
                    ];
                }, $seriesYears)
            ]
        ], JSON_UNESCAPED_UNICODE); ?>;
        const datasets = <?php echo json_encode($datasets, JSON_UNESCAPED_UNICODE); ?>;
        let currentPeriod = 'days';
        const chartInstances = {};
        const seriesNameByKey = {};
        const seriesKeyByName = {};
        const chartPeriods = Object.keys(chartData).filter((period) => {
            return document.getElementById(`apex-chart-${period}`);
        });

        document.querySelectorAll('[data-toggle-target]').forEach((button) => {
            const targetId = button.getAttribute('data-toggle-target');
            const group = button.getAttribute('data-toggle-group');
            const target = document.getElementById(targetId);
            if (!target) return;

            const apply = () => {
                const isActive = button.getAttribute('aria-pressed') === 'true';
                target.classList.toggle('hidden', !isActive);
                button.classList.toggle('is-active', isActive);
            };

            button.addEventListener('click', () => {
                if (group) {
                    document.querySelectorAll(`[data-toggle-group="${group}"]`).forEach((btn) => {
                        btn.setAttribute('aria-pressed', btn === button ? 'true' : 'false');
                        const otherTargetId = btn.getAttribute('data-toggle-target');
                        const otherTarget = otherTargetId ? document.getElementById(otherTargetId) : null;
                        if (otherTarget) {
                            const isActive = btn === button;
                            otherTarget.classList.toggle('hidden', !isActive);
                            btn.classList.toggle('is-active', isActive);
                        }
                    });
                    if (group === 'period') {
                        const period = target.getAttribute('data-period') || 'days';
                        currentPeriod = period;
                        syncTableToChart();
                    }
                    return;
                }

                const isActive = button.getAttribute('aria-pressed') === 'true';
                button.setAttribute('aria-pressed', isActive ? 'false' : 'true');
                apply();
            });

            apply();
        });

        let lastSelectedSeries = 'base';
        const tableSelection = document.getElementById('table-selection');
        const tableTitle = document.getElementById('table-title');
        const tableBody = document.getElementById('table-body');

        const updateSelectedSeriesFromChart = (period, preferredKey = '') => {
            const chart = chartInstances[period];
            if (!chart) return;
            const globals = chart.w.globals;
            const hidden = new Set(globals.collapsedSeriesIndices || []);
            const names = globals.seriesNames || [];
            let nextKey = '';
            if (preferredKey) {
                const preferredName = seriesNameByKey[period] ? seriesNameByKey[period][preferredKey] : '';
                const preferredIndex = names.indexOf(preferredName);
                if (preferredIndex >= 0 && !hidden.has(preferredIndex)) {
                    nextKey = preferredKey;
                }
            }
            if (!nextKey) {
                for (let i = 0; i < names.length; i++) {
                    if (hidden.has(i)) continue;
                    const key = seriesKeyByName[period] ? seriesKeyByName[period][names[i]] : '';
                    if (key) {
                        nextKey = key;
                        break;
                    }
                }
            }
            lastSelectedSeries = nextKey || 'base';
            updateTable();
        };

        const buildChartOptions = (period) => {
            const data = chartData[period];
            return {
                chart: {
                    type: 'line',
                    height: 420,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        },
                        export: {
                            csv: {
                                filename: `statistik_${period}`,
                                headerCategory: 'Label',
                                headerValue: 'Wert'
                            },
                            svg: {
                                filename: `statistik_${period}`
                            },
                            png: {
                                filename: `statistik_${period}`
                            }
                        }
                    },
                    zoom: { enabled: false },
                    animations: { enabled: true },
                    events: {
                        legendClick: (chartContext, seriesIndex) => {
                            const names = chartContext.w.globals.seriesNames || [];
                            const name = names[seriesIndex] || '';
                            const key = seriesKeyByName[period] ? seriesKeyByName[period][name] : '';
                            setTimeout(() => {
                                updateSelectedSeriesFromChart(period, key);
                            }, 0);
                        }
                    }
                },
                series: data.series.map((item) => ({ name: item.name, data: item.data })),
                xaxis: {
                    categories: data.categories,
                    labels: { rotate: -35 }
                },
                stroke: { width: 3, curve: 'straight' },
                markers: { size: 3 },
                dataLabels: { enabled: false },
                grid: { strokeDashArray: 3 },
                legend: {
                    show: true,
                    position: 'top',
                    onItemClick: { toggleDataSeries: true }
                },
                tooltip: { shared: true, intersect: false }
            };
        };

        const initCharts = () => {
            let readyCount = 0;
            const total = chartPeriods.length;
            chartPeriods.forEach((period) => {
                const container = document.getElementById(`apex-chart-${period}`);
                const data = chartData[period];
                if (!container || !data) return;
                seriesNameByKey[period] = {};
                seriesKeyByName[period] = {};
                data.series.forEach((item) => {
                    seriesNameByKey[period][item.key] = item.name;
                    seriesKeyByName[period][item.name] = item.key;
                });
                const chart = new ApexCharts(container, buildChartOptions(period));
                chartInstances[period] = chart;
                chart.render().then(() => {
                    data.series.forEach((item) => {
                        if (item.key !== 'base') {
                            chart.hideSeries(item.name);
                        }
                    });
                    readyCount += 1;
                    if (readyCount === total) {
                        updateSelectedSeriesFromChart(currentPeriod, 'base');
                    }
                });
            });
        };

        const syncTableToChart = () => {
            updateSelectedSeriesFromChart(currentPeriod, lastSelectedSeries);
        };
        initCharts();

        const periodKeyMap = {
            days: 'day',
            weeks: 'week',
            months: 'month',
            years: 'year'
        };

        function buildTableKey() {
            const series = lastSelectedSeries || 'base';
            const periodKey = periodKeyMap[currentPeriod] || 'day';
            if (series === 'base') return currentPeriod;
            if (series.startsWith('duft-')) {
                const id = series.split('-')[1] || '';
                return id ? `duft_${periodKey}_${id}` : `duft_${periodKey}`;
            }
            if (series.startsWith('sauna-')) {
                const id = series.split('-')[1] || '';
                return id ? `sauna_${periodKey}_${id}` : `sauna_${periodKey}`;
            }
            if (series === 'aufguss') return `aufguss_${periodKey}`;
            if (series.startsWith('staerke-')) {
                const level = series.split('-')[1] || '1';
                return `staerke_${periodKey}_${level}`;
            }
            return currentPeriod;
        }

        function updateTable() {
            if (!tableBody || !tableTitle) return;
            const key = buildTableKey();
            const data = datasets[key];
            tableTitle.textContent = data && data.title ? data.title : 'Keine Daten';
            if (tableSelection) {
                tableSelection.textContent = data && data.title ? data.title : 'Keine Daten';
            }
            tableBody.innerHTML = '';
            const items = data && Array.isArray(data.items) ? data.items : [];
            if (items.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 2;
                cell.className = 'py-2 text-gray-500';
                cell.textContent = 'Keine Daten vorhanden.';
                row.appendChild(cell);
                tableBody.appendChild(row);
                return;
            }
            items.forEach((item) => {
                const row = document.createElement('tr');
                const labelCell = document.createElement('td');
                labelCell.className = 'py-2 pr-4 text-gray-700';
                labelCell.textContent = item.label ?? '';
                const valueCell = document.createElement('td');
                valueCell.className = 'py-2 text-gray-700';
                valueCell.textContent = item.value ?? 0;
                row.appendChild(labelCell);
                row.appendChild(valueCell);
                tableBody.appendChild(row);
            });
        }
        syncTableToChart();

        const tooltip = document.getElementById('chart-tooltip');
        if (tooltip) {
            const showTooltip = (event, label, value, seriesLabel) => {
                const head = seriesLabel ? `${seriesLabel} • ` : '';
                tooltip.textContent = `${head}${label}: ${value}`;
                tooltip.classList.remove('hidden');
                tooltip.style.left = `${event.clientX + 8}px`;
                tooltip.style.top = `${event.clientY - 8}px`;
            };

            const moveTooltip = (event) => {
                tooltip.style.left = `${event.clientX + 8}px`;
                tooltip.style.top = `${event.clientY - 8}px`;
            };

            document.querySelectorAll('.chart-bar').forEach((bar) => {
                bar.addEventListener('mouseenter', (event) => {
                    bar.style.opacity = '0.85';
                    showTooltip(event, bar.dataset.label || '-', bar.dataset.value || '0');
                });
                bar.addEventListener('mousemove', moveTooltip);
                bar.addEventListener('mouseleave', () => {
                    bar.style.opacity = '1';
                    tooltip.classList.add('hidden');
                });
            });
        }

        const planButtons = Array.from(document.querySelectorAll('[data-plan-id]'));
        if (planButtons.length > 0) {
            const syncPlans = () => {
                const allIds = Array.from(planButtons).map((btn) => btn.getAttribute('data-plan-id'));
                const selected = planButtons
                    .filter((btn) => btn.getAttribute('aria-pressed') === 'true')
                    .map((btn) => btn.getAttribute('data-plan-id'));
                const params = new URLSearchParams(window.location.search);
                if (selected.length === 0 || selected.length === allIds.length) {
                    params.delete('plans');
                } else {
                    params.set('plans', selected.join(','));
                }
                const next = params.toString();
                const base = window.location.pathname;
                const target = next ? `${base}?${next}` : base;
                window.location.href = `${target}#more-stats`;
            };

            planButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const isActive = button.getAttribute('aria-pressed') === 'true';
                    button.setAttribute('aria-pressed', isActive ? 'false' : 'true');
                    button.classList.toggle('is-active', !isActive);
                    syncPlans();
                });
            });
        }

        document.querySelectorAll('[data-accordion-toggle]').forEach((button) => {
            const targetId = button.getAttribute('data-accordion-toggle');
            const target = document.getElementById(targetId);
            const icon = document.querySelector(`[data-accordion-icon="${targetId}"]`);
            if (!target) return;

            button.addEventListener('click', () => {
                const isOpen = !target.classList.contains('hidden');
                target.classList.toggle('hidden', isOpen);
                button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                if (icon) {
                    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            });
        });
    </script>
</body>
</html>
