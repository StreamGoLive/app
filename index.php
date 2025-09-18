<?php
session_start();

// Protect the page from unauthorized access
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// Ensure the 'pro' directory exists to store event links
if (!is_dir('pro')) {
    mkdir('pro');
}

// --- Data Fetching and Processing ---

$category = isset($_GET['category']) ? $_GET['category'] : 'cricket';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$api_url = "https://www.sofascore.com/api/v1/sport/{$category}/scheduled-events/{$date}";

// Set options to mimic a web browser and fetch the API data
$opts = ['http' => ['header' => "User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36\r\n"]];
$context = stream_context_create($opts);
$json = @file_get_contents($api_url, false, $context);
$data = json_decode($json, true);

$events = $data['events'] ?? [];

// Process events for display and saving
$processed_events = [];
if (!empty($events)) {
    foreach ($events as $event) {
        // Construct logo URLs directly from the API, as per your request
        $team1_logo_url = "https://img.sofascore.com/api/v1/team/{$event['homeTeam']['id']}/image";
        $team2_logo_url = "https://img.sofascore.com/api/v1/team/{$event['awayTeam']['id']}/image";

        $processed_events[] = [
            "id" => $event['id'],
            "title" => $event['tournament']['name'] ?? '',
            "category_name" => $event['tournament']['category']['sport']['slug'] ?? '',
            "sports" => $event['tournament']['category']['sport']['slug'] ?? '',
            "team1_name" => $event['homeTeam']['name'] ?? '',
            "team1_logo" => $team1_logo_url,
            "team2_name" => $event['awayTeam']['name'] ?? '',
            "team2_logo" => $team2_logo_url,
            "event_datetime" => date("Y-m-d H:i:s", $event['startTimestamp']),
            "event_endtime" => !empty($event['endTimestamp']) ? date("Y-m-d H:i:s", $event['endTimestamp']) : null,
            "homeTeamId" => $event['homeTeam']['id'] ?? '',
            "awayTeamId" => $event['awayTeam']['id'] ?? ''
        ];
    }
}

// Handle saving selected events from the POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_events') {
    $selected_event_ids = $_POST['selected_events'] ?? [];
    
    if (!empty($selected_event_ids)) {
        $master_json_file = 'events.json';
        $current_events = file_exists($master_json_file) ? json_decode(file_get_contents($master_json_file), true) : [];

        // Read the category logos from the JSON file
        $category_logos_file = 'cat_logos.json';
        $category_logos_json = file_exists($category_logos_file) ? file_get_contents($category_logos_file) : '[]';
        $category_logos = json_decode($category_logos_json, true);
        
        // Convert the array of objects to a key-value pair for easy lookup
        $logo_map = [];
        foreach ($category_logos as $logo_item) {
            $logo_map[strtolower($logo_item['name'])] = $logo_item['logo_url'];
        }

        foreach ($selected_event_ids as $event_id) {
            foreach ($processed_events as $processed_event) {
                if ($processed_event['id'] == $event_id) {
                    // Create a unique, file-safe name for the links file
                    $event_name_safe = preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $processed_event['title'] ?? 'event'));
                    $team_a_name_safe = preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $processed_event['team1_name'] ?? 'teamA'));
                    $team_b_name_safe = preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', $processed_event['team2_name'] ?? 'teamB'));
                    $links_file = "pro/{$event_name_safe}-{$team_a_name_safe}-vs-{$team_b_name_safe}.json";
                    
                    // Create the links JSON file with an empty array
                    file_put_contents($links_file, '[]');

                    // Get the sports slug from SofaScore API data and find the matching logo
                    $sports_name_from_api = $processed_event['sports'] ?? '';
                    $logo_url = isset($logo_map[strtolower($sports_name_from_api)]) ? $logo_map[strtolower($sports_name_from_api)] : '';

                    // Construct the event object for events.json
                    $new_event = [
                        "visible" => true,
                        "show_noti" => false,
                        "category" => $processed_event['category_name'] ?? 'Football',
                        "categoryLogo" => $logo_url,
                        "eventName" => $processed_event['title'] ?? 'Event Name',
                        "eventLogo" => $logo_url,
                        "teamAName" => $processed_event['team1_name'] ?? 'Team A',
                        "teamBName" => $processed_event['team2_name'] ?? 'Team B',
                        "teamAFlag" => "https://img.sofascore.com/api/v1/team-flag/{$processed_event['homeTeamId']}/image",
                        "teamBFlag" => "https://img.sofascore.com/api/v1/team-flag/{$processed_event['awayTeamId']}/image",
                        "date" => date('d/m/Y', strtotime($processed_event['event_datetime'])),
                        "time" => date('H:i', strtotime($processed_event['event_datetime'])),
                        "end_date" => date('d/m/Y', strtotime($processed_event['event_endtime'])),
                        "end_time" => date('H:i', strtotime($processed_event['event_endtime'])),
                        "links" => $links_file
                    ];
                    $current_events[] = $new_event;
                    break;
                }
            }
        }
        // Save the updated master events file
        file_put_contents($master_json_file, json_encode($current_events, JSON_PRETTY_PRINT));
        $message = "Selected events added successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SofaScore Events</title>
    <style>
        body { background-color: #2c2c2c; color: white; font-family: sans-serif; padding: 20px; }
        .form-container { background-color: #383838; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .form-container .header { display: flex; justify-content: space-between; align-items: center; }
        .form-container input[type="text"], .form-container select, .form-container input[type="date"] { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #007bff; border-radius: 5px; background-color: #444; color: white; box-sizing: border-box; }
        .refresh-btn { background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .event-card { background-color: #383838; border-radius: 10px; margin-bottom: 10px; padding: 15px; display: flex; align-items: center; justify-content: space-between; }
        .event-card img { width: 40px; height: 40px; }
        .event-card .details { flex-grow: 1; margin: 0 10px; }
        .message { margin-top: 20px; text-align: center; }
        .add-btn { background-color: #007bff; color: white; border: none; padding: 15px; border-radius: 8px; width: 100%; font-size: 18px; cursor: pointer; margin-top: 20px; }
    </style>
</head>
<button onclick="window.history.back()" style="background-color: #555; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none; display: inline-block; margin-bottom: 20px;">
    ← Back
</button>
<body>
    <div class="form-container">
        <div class="header">
            <h2>Api</h2>
            
            <form action="sofascore.php" method="GET" style="display:inline;">
                <button type="submit" class="refresh-btn">REFRESH</button>
            </div>
            <p><input type="text" name="url" value="https://www.sofascore.com/api/v1/sport" placeholder="API URL"></p>
            <p><input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>"></p>
            <select name="category">
                <option value="football" <?php echo ($category === 'football') ? 'selected' : ''; ?>>Football</option>
                <option value="cricket" <?php echo ($category === 'cricket') ? 'selected' : ''; ?>>Cricket</option>
                <option value="basketball" <?php echo ($category === 'basketball') ? 'selected' : ''; ?>>Basketball</option>
                <option value="tennis" <?php echo ($category === 'tennis') ? 'selected' : ''; ?>>Tennis</option>
            </select>
        </form>
    </div>

    <form action="sofascore.php" method="POST">
        <input type="hidden" name="action" value="save_events">
        <?php if (isset($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (!empty($processed_events)): ?>
            <?php foreach ($processed_events as $event): ?>
                <div class="event-card">
                    <input type="checkbox" name="selected_events[]" value="<?php echo htmlspecialchars($event['id']); ?>">
                    <img src="<?php echo htmlspecialchars($event['team1_logo'] ?? ''); ?>" alt="Team A Logo">
                    <div class="details">
                        <p><?php echo htmlspecialchars($event['title'] ?? 'No Category'); ?> | <?php echo htmlspecialchars($event['sports'] ?? 'No Event Name'); ?></p>
                        <p><?php echo htmlspecialchars($event['team1_name'] ?? ''); ?> vs <?php echo htmlspecialchars($event['team2_name'] ?? ''); ?></p>
                        <p><?php echo date('h:i a d/m/Y', strtotime($event['event_datetime'])); ?></p>
                    </div>
                    <img src="<?php echo htmlspecialchars($event['team2_logo'] ?? ''); ?>" alt="Team B Logo">
                </div>
            <?php endforeach; ?>
            <button type="submit" class="add-btn">ADD</button>
        <?php else: ?>
            <p style="text-align: center;">No events to display. Please refresh to fetch data.</p>
        <?php endif; ?>
    </form>
</body>
</html>
