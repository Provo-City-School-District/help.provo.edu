<?php
require_once from_root('/../vendor/autoload.php');
require_once from_root("/new-controllers/base_variables.php");
require_once("ticket_utils.php");

$loader = new \Twig\Loader\FilesystemLoader(from_root('/../views'));
$twig = new \Twig\Environment($loader, [
    'cache' => from_root('/../twig-cache'),
    'auto_reload' => true
]);

// Check if the user has permission to view reports
$view_reports = get_user_setting(get_id_for_user($_SESSION['username']), "view_stats") ?? 0;
if ($view_reports != 1) {
    // User is not an admin
    echo 'You do not have permission to view this page.';
    exit;
}


// init variables and include necessary files
require_once('helpdbconnect.php');
require_once("swdbconnect.php");
$department = $_SESSION['department'] ?? null;
$department_location_code = get_sitenumber_from_location_id($_SESSION['department']) ?? null;
$tickets_per_year = [];
$resolved_per_year = [];
$last_year_date = date('Y-m-d', strtotime('-12 months'));

// Helper Functions
function get_tickets_per_year($query, $params = [], $key = 'year', $value = 'count', $db = null)
{
    $result = $db
        ? $db::get()->execute_query($query, $params)
        : HelpDB::get()->execute_query($query, $params);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row[$key]] = (int)$row[$value];
    }
    return $data;
}

function get_counts_per_year($query, $params = [], $key = 'year', $value = 'count', $db = null)
{
    $result = $db
        ? $db::get()->execute_query($query, $params)
        : HelpDB::get()->execute_query($query, $params);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row[$key]] = (int)$row[$value];
    }
    return $data;
}





// Tickets opened averages (last 12 months)
$open_avg_day_query = "SELECT COUNT(*)/COUNT(DISTINCT DATE(created)) AS avg_per_day FROM tickets WHERE department = ? AND created >= ?";
$open_avg_week_query = "SELECT COUNT(*)/COUNT(DISTINCT YEARWEEK(created, 1)) AS avg_per_week FROM tickets WHERE department = ? AND created >= ?";
$open_avg_month_query = "SELECT COUNT(*)/COUNT(DISTINCT DATE_FORMAT(created, '%Y-%m')) AS avg_per_month FROM tickets WHERE department = ? AND created >= ?";

$open_avg_per_day = HelpDB::get()->execute_query($open_avg_day_query, [$department_location_code, $last_year_date])->fetch_row()[0] ?? 0;
$open_avg_per_week = HelpDB::get()->execute_query($open_avg_week_query, [$department_location_code, $last_year_date])->fetch_row()[0] ?? 0;
$open_avg_per_month = HelpDB::get()->execute_query($open_avg_month_query, [$department_location_code, $last_year_date])->fetch_row()[0] ?? 0;





// Tickets resolved averages (last 12 months)
$resolve_avg_day_query = "
    SELECT COUNT(*)/COUNT(DISTINCT DATE(last_updated)) AS avg_per_day
    FROM tickets
    WHERE (LOWER(status) LIKE '%resolved%' OR LOWER(status) LIKE '%closed%')
      AND last_updated IS NOT NULL
      AND department = ?
      AND last_updated >= ?
";
$resolve_avg_week_query = "
    SELECT COUNT(*)/COUNT(DISTINCT YEARWEEK(last_updated, 1)) AS avg_per_week
    FROM tickets
    WHERE (LOWER(status) LIKE '%resolved%' OR LOWER(status) LIKE '%closed%')
      AND last_updated IS NOT NULL
      AND department = ?
      AND last_updated >= ?
";
$resolve_avg_month_query = "
    SELECT COUNT(*)/COUNT(DISTINCT DATE_FORMAT(last_updated, '%Y-%m')) AS avg_per_month
    FROM tickets
    WHERE (LOWER(status) LIKE '%resolved%' OR LOWER(status) LIKE '%closed%')
      AND last_updated IS NOT NULL
      AND department = ?
      AND last_updated >= ?
";

$resolve_avg_per_day = HelpDB::get()->execute_query($resolve_avg_day_query, [$department_location_code, $last_year_date])->fetch_row()[0] ?? 0;
$resolve_avg_per_week = HelpDB::get()->execute_query($resolve_avg_week_query, [$department_location_code, $last_year_date])->fetch_row()[0] ?? 0;
$resolve_avg_per_month = HelpDB::get()->execute_query($resolve_avg_month_query, [$department_location_code, $last_year_date])->fetch_row()[0] ?? 0;





// query tickets opened per year
if ($department == 56) {
    // Legacy + current
    $legacy_query = "SELECT DATE_FORMAT(report_date,'%Y') as year, COUNT(job_ticket_id) as num FROM job_ticket WHERE DELETED = 0 GROUP BY DATE_FORMAT(report_date,'%Y')";
    $legacy = get_tickets_per_year($legacy_query, [], 'year', 'num', 'SolarWindsDB');

    $current_query = "SELECT YEAR(created) AS year, COUNT(*) AS count FROM tickets GROUP BY year ORDER BY year ASC";
    $current = get_tickets_per_year($current_query);

    $all_years = array_unique(array_merge(array_keys($legacy), array_keys($current)));
    sort($all_years);

    foreach ($all_years as $year) {
        $tickets_per_year[] = [
            "label" => $year,
            "y" => ($legacy[$year] ?? 0) + ($current[$year] ?? 0)
        ];
    }
} else {
    // Only current
    $current_query = "SELECT YEAR(created) AS year, COUNT(*) AS count FROM tickets WHERE department = ? GROUP BY year ORDER BY year ASC";
    $current = get_tickets_per_year($current_query, [$department_location_code]);
    foreach ($current as $year => $count) {
        $tickets_per_year[] = [
            "label" => $year,
            "y" => $count
        ];
    }
}






// query tickets resolved per year
if ($department == 56) {
    // Legacy + current
    $legacy_resolved_query = "SELECT DATE_FORMAT(close_date,'%Y') as year, COUNT(job_ticket_id) as num FROM job_ticket WHERE DELETED = 0 AND close_date IS NOT NULL GROUP BY DATE_FORMAT(close_date,'%Y')";
    $legacy_resolved = get_counts_per_year($legacy_resolved_query, [], 'year', 'num', 'SolarWindsDB');

    // Use last_updated and status for current tickets
    $current_resolved_query = "
        SELECT YEAR(last_updated) AS year, COUNT(*) AS count
        FROM tickets
        WHERE (LOWER(status) LIKE '%resolved%' OR LOWER(status) LIKE '%closed%')
          AND last_updated IS NOT NULL
        GROUP BY year
        ORDER BY year ASC
    ";
    $current_resolved = get_counts_per_year($current_resolved_query);

    $all_years_resolved = array_unique(array_merge(array_keys($legacy_resolved), array_keys($current_resolved)));
    sort($all_years_resolved);

    foreach ($all_years_resolved as $year) {
        $resolved_per_year[] = [
            "label" => $year,
            "y" => ($legacy_resolved[$year] ?? 0) + ($current_resolved[$year] ?? 0)
        ];
    }
} else {
    // Use last_updated and status for current tickets, filtered by department
    $current_resolved_query = "
        SELECT YEAR(last_updated) AS year, COUNT(*) AS count
        FROM tickets
        WHERE (LOWER(status) LIKE '%resolved%' OR LOWER(status) LIKE '%closed%')
          AND last_updated IS NOT NULL
          AND department = ?
        GROUP BY year
        ORDER BY year ASC
    ";
    $current_resolved = get_counts_per_year($current_resolved_query, [$department_location_code]);
    foreach ($current_resolved as $year => $count) {
        $resolved_per_year[] = [
            "label" => $year,
            "y" => $count
        ];
    }
}





// Feedback statistics for the current department (last 12 months)
$feedback_stats_query = "
    SELECT
        COUNT(*) AS total_feedback,
        AVG(rating) AS avg_rating
    FROM feedback
    INNER JOIN tickets ON feedback.ticket_id = tickets.id
    WHERE tickets.department = ?
      AND feedback.created_at >= ?
";
$feedback_stats_result = HelpDB::get()->execute_query($feedback_stats_query, [$department_location_code, $last_year_date]);
$feedback_stats = $feedback_stats_result->fetch_assoc() ?: [
    'total_feedback' => 0,
    'avg_rating' => 0,
];




// Average business days to resolve tickets
$resolve_times_query = "
    SELECT created, last_updated
    FROM tickets
    WHERE (LOWER(status) LIKE '%resolved%' OR LOWER(status) LIKE '%closed%')
      AND last_updated IS NOT NULL
      AND created IS NOT NULL
      AND department = ?
      AND last_updated >= ?
";
$resolve_times_result = HelpDB::get()->execute_query($resolve_times_query, [$department_location_code, $last_year_date]);
// function to calculate business days between dates to exclude weekends
function business_days_between($start, $end)
{
    $start = new DateTime($start);
    $end = new DateTime($end);
    if ($start > $end) return 0;
    $business_days = 0;
    while ($start <= $end) {
        $dayOfWeek = $start->format('N'); // 1 (Mon) to 7 (Sun)
        if ($dayOfWeek < 6) { // Mon-Fri
            $business_days++;
        }
        $start->modify('+1 day');
    }
    return $business_days;
}

$total_business_days = 0;
$ticket_count = 0;
while ($row = $resolve_times_result->fetch_assoc()) {
    $days = business_days_between($row['created'], $row['last_updated']);
    if ($days > 0) {
        $total_business_days += $days;
        $ticket_count++;
    }
}
$avg_resolve_business_days = $ticket_count > 0 ? round($total_business_days / $ticket_count, 2) : "N/A";




// Render Display View
echo $twig->render('stats.twig', [
    // base variables
    'color_scheme' => $color_scheme,
    'current_year' => $current_year,
    'user_permissions' => $permissions,
    'wo_time' => $wo_time,
    'user_pref' => $user_pref,
    'ticket_limit' => $ticket_limit,
    'status_alert_type' => $status_alert_type,
    'status_alert_message' => $status_alert_message,
    'app_version' => $app_version,

    // Reports variables
    'location_name' => location_name_from_id($department_location_code),
    'department_location_code' => $department_location_code,
    'department' => $department,
    'tickets_per_year' => $tickets_per_year,

    // Tickets opened averages
    'resolved_per_year' => $resolved_per_year,
    'open_avg_per_day' => round($open_avg_per_day, 2),
    'open_avg_per_week' => round($open_avg_per_week, 2),
    'open_avg_per_month' => round($open_avg_per_month, 2),
    'resolve_avg_per_day' => round($resolve_avg_per_day, 2),
    'resolve_avg_per_week' => round($resolve_avg_per_week, 2),
    'resolve_avg_per_month' => round($resolve_avg_per_month, 2),
    'feedback_stats' => $feedback_stats,
    'avg_resolve_business_days' => $avg_resolve_business_days,
]);
