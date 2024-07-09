<?php
// Ścieżki do plików JSON
define('USERS_FILE', 'data/users.json');
define('PUZZLES_FILE', 'data/puzzles.json');
define('SCORES_FILE', 'data/scores.json');

// Funkcje pomocnicze do obsługi plików JSON
function read_json_file($file_path) {
    if (!file_exists($file_path)) {
        $dir = dirname($file_path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($file_path, json_encode([]));
    }
    return json_decode(file_get_contents($file_path), true) ?? [];
}

function write_json_file($file_path, $data) {
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
}

// Funkcje związane z użytkownikami
function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

function verify_login($username, $password) {
    $users = read_json_file(USERS_FILE);
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password_hash'])) {
            return $user;
        }
    }
    return false;
}

function register_user($username, $email, $password) {
    $users = read_json_file(USERS_FILE);
    if (array_search($username, array_column($users, 'username')) !== false) {
        return false;
    }
    $new_user = [
        'id' => uniqid(),
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'is_admin' => false
    ];
    $users[] = $new_user;
    write_json_file(USERS_FILE, $users);
    return $new_user['id'];
}

function is_admin($user_id) {
    $users = read_json_file(USERS_FILE);
    foreach ($users as $user) {
        if ($user['id'] === $user_id) {
            return $user['is_admin'] ?? false;
        }
    }
    return false;
}

function get_all_users() {
    return read_json_file(USERS_FILE);
}

function update_user($user_id, $data) {
    $users = read_json_file(USERS_FILE);
    foreach ($users as &$user) {
        if ($user['id'] === $user_id) {
            $user = array_merge($user, $data);
            if (isset($data['password'])) {
                $user['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
                unset($user['password']);
            }
            write_json_file(USERS_FILE, $users);
            return true;
        }
    }
    return false;
}

function delete_user($user_id) {
    $users = read_json_file(USERS_FILE);
    $users = array_filter($users, fn($user) => $user['id'] !== $user_id);
    write_json_file(USERS_FILE, array_values($users));
}

// Funkcje związane z zagadkami
function get_all_puzzles() {
    return read_json_file(PUZZLES_FILE);
}

function get_random_puzzle() {
    $puzzles = get_all_puzzles();
    if (empty($puzzles)) {
        return null;
    }
    $puzzle = $puzzles[array_rand($puzzles)];
    
    // Upewnij się, że 'answers' jest zawsze tablicą
    if (!isset($puzzle['answers']) || !is_array($puzzle['answers'])) {
        $puzzle['answers'] = [$puzzle['answers'] ?? 'Brak odpowiedzi'];
    }
    
    return $puzzle;
}
function add_or_update_puzzle($puzzle_data, $puzzle_id = null) {
    $puzzles = get_all_puzzles();
    if ($puzzle_id) {
        foreach ($puzzles as &$puzzle) {
            if ($puzzle['id'] === $puzzle_id) {
                $puzzle = array_merge($puzzle, $puzzle_data);
                $puzzle['id'] = $puzzle_id;
                write_json_file(PUZZLES_FILE, $puzzles);
                return $puzzle_id;
            }
        }
    } else {
        $puzzle_data['id'] = uniqid();
        $puzzles[] = $puzzle_data;
        write_json_file(PUZZLES_FILE, $puzzles);
        return $puzzle_data['id'];
    }
    return false;
}

function delete_puzzle($puzzle_id) {
    $puzzles = get_all_puzzles();
    $puzzles = array_filter($puzzles, fn($puzzle) => $puzzle['id'] !== $puzzle_id);
    write_json_file(PUZZLES_FILE, array_values($puzzles));
}

function get_puzzle_by_id($puzzle_id) {
    $puzzles = get_all_puzzles();
    foreach ($puzzles as $puzzle) {
        if ($puzzle['id'] === $puzzle_id) {
            return $puzzle;
        }
    }
    return null;
}

// Funkcje związane z rankingiem i punktacją
function get_ranking() {
    $scores = read_json_file(SCORES_FILE);
    usort($scores, fn($a, $b) => $b['score'] - $a['score']);
    $users = get_all_users();
    $user_map = array_column($users, null, 'id');
    foreach ($scores as &$score) {
        $user = $user_map[$score['user_id']] ?? null;
        $score['username'] = $user ? $user['username'] : 'Nieznany użytkownik';
        $score['accuracy'] = $score['total_attempts'] > 0 
            ? round(($score['correct_answers'] / $score['total_attempts']) * 100, 2) 
            : 0;
    }
    return array_slice($scores, 0, 10);
}

function update_score($user_id, $points) {
    $scores = read_json_file(SCORES_FILE);
    $user_score = null;
    foreach ($scores as &$score) {
        if ($score['user_id'] == $user_id) {
            $user_score = &$score;
            break;
        }
    }
    if ($user_score === null) {
        $user_score = ['user_id' => $user_id, 'score' => 0, 'total_attempts' => 0, 'correct_answers' => 0];
        $scores[] = &$user_score;
    }
    $user_score['score'] += $points;
    $user_score['total_attempts']++;
    if ($points > 0) {
        $user_score['correct_answers']++;
    }
    write_json_file(SCORES_FILE, $scores);
}

// Funkcje związane z logiką gry
function handle_game_actions($post_data, &$session_data) {
    $puzzle = $session_data['current_puzzle'] ?? null;
    $current_hint = $session_data['current_hint'] ?? 0;
    $possible_points = $session_data['possible_points'] ?? 5;
    $show_next_puzzle = false;
    $message = '';

    // Sprawdź, czy mamy aktualną zagadkę
    if (!$puzzle) {
        $puzzle = get_random_puzzle();
        if (!$puzzle) {
            return [
                'error' => 'Nie udało się pobrać zagadki. Proszę spróbować później.',
                'show_next_puzzle' => true
            ];
        }
    }

    // Upewnij się, że 'answers' jest zawsze tablicą
    if (!isset($puzzle['answers']) || !is_array($puzzle['answers'])) {
        $puzzle['answers'] = [$puzzle['answers'] ?? 'Brak odpowiedzi'];
    }

    if (isset($post_data['surrender'])) {
        $message = "Poddałeś się. Poprawne odpowiedzi to: " . implode(', ', $puzzle['answers']);
        update_score($session_data['user_id'], 0);
        $show_next_puzzle = true;
    } elseif (isset($post_data['guess'])) {
        $guess = strtolower(trim($post_data['guess']));
        $answers = array_map('strtolower', $puzzle['answers']);
        if (in_array($guess, $answers)) {
            $message = "Gratulacje! Poprawna odpowiedź. Zdobywasz $possible_points punktów.";
            update_score($session_data['user_id'], $possible_points);
            $show_next_puzzle = true;
        } else {
            $current_hint++;
            $possible_points = max(5 - $current_hint, 0);
            if ($current_hint >= 4 || $current_hint >= count($puzzle['hints'] ?? [])) {
                $message = "Niestety, nie udało się odgadnąć. Poprawne odpowiedzi to: " . implode(', ', $puzzle['answers']);
                update_score($session_data['user_id'], 0);
                $show_next_puzzle = true;
            } else {
                $message = "Niestety, to nie jest poprawna odpowiedź. Oto podpowiedź: " . ($puzzle['hints'][$current_hint - 1] ?? 'Brak podpowiedzi');
            }
        }
    }

    if ($show_next_puzzle) {
        $puzzle = get_random_puzzle();
        $current_hint = 0;
        $possible_points = 5;
    }

    $session_data['current_puzzle'] = $puzzle;
    $session_data['current_hint'] = $current_hint;
    $session_data['possible_points'] = $possible_points;

    return [
        'puzzle' => $puzzle,
        'message' => $message,
        'show_next_puzzle' => $show_next_puzzle,
        'current_hint' => $current_hint,
        'possible_points' => $possible_points
    ];
}

// Funkcje do obsługi akcji w panelu admina
function handle_admin_action($action, $data) {
    switch ($action) {
        case 'add_puzzle':
        case 'edit_puzzle':
            return add_or_update_puzzle($data, $data['puzzle_id'] ?? null);
        case 'delete_puzzle':
            return delete_puzzle($data['puzzle_id']);
        case 'edit_user':
            return update_user($data['user_id'], $data);
        case 'delete_user':
            return delete_user($data['user_id']);
        case 'edit_ranking':
            // Implementacja edycji rankingu, jeśli jest potrzebna
            break;
    }
    return false;
}

// Funkcje pomocnicze
function redirect_to($location) {
    header("Location: $location");
    exit();
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function render_puzzle_content($puzzle) {
    switch ($puzzle['type']) {
        case 'image':
            return '<img src="' . sanitize_input($puzzle['content']) . '" alt="Zagadka SCP">';
        case 'audio':
            return '<audio controls><source src="' . sanitize_input($puzzle['content']) . '" type="audio/mpeg">Twoja przeglądarka nie obsługuje elementu audio.</audio>';
        case 'video':
            return '<video controls><source src="' . sanitize_input($puzzle['content']) . '" type="video/mp4">Twoja przeglądarka nie obsługuje elementu wideo.</video>';
        default:
            return '<p>' . sanitize_input($puzzle['content']) . '</p>';
    }
}

// Funkcja do obsługi przesyłania plików
function handle_file_upload($file, $allowed_types = ['image/jpeg', 'image/png', 'audio/mpeg', 'video/mp4']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return false;
    }

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_name = uniqid() . '_' . basename($file['name']);
    $upload_path = $upload_dir . $file_name;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $upload_path;
    }

    return false;
}