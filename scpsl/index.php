<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

function safelyLoadJson($filename) {
    if (!file_exists($filename)) {
        throw new Exception("Plik $filename nie istnieje.");
    }
    
    $json_data = file_get_contents($filename);
    if ($json_data === false) {
        throw new Exception("Nie można odczytać pliku $filename.");
    }
    
    $decoded_data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Błąd parsowania JSON: " . json_last_error_msg());
    }
    
    if (!isset($decoded_data['categories']) || !is_array($decoded_data['categories'])) {
        throw new Exception("Nieprawidłowa struktura JSON. Brak klucza 'categories' lub nie jest on tablicą.");
    }
    
    return $decoded_data['categories'];
}

function isCorrectGuess($guess, $item) {
    return in_array($guess, $item['aliases']);
}

function getCategoryOptions($categories, $categoryName) {
    $options = [];
    foreach ($categories as $category) {
        if ($category['name'] === $categoryName) {
            foreach ($category['items'] as $item) {
                $options = array_merge($options, $item['aliases']);
            }
            break;
        }
    }
    return array_unique($options);
}

function renderItemList($categories) {
    $output = '<div class="item-list">';
    foreach ($categories as $category) {
        $output .= '<h3>' . htmlspecialchars($category['name']) . '</h3>';
        $output .= '<ul>';
        foreach ($category['items'] as $item) {
            $output .= '<li>' . htmlspecialchars($item['name']) . '</li>';
        }
        $output .= '</ul>';
    }
    $output .= '</div>';
    return $output;
}

try {
    $categories = safelyLoadJson('stuff.json');

    if (isset($_POST['new_game']) || isset($_POST['back_to_category'])) {
        unset($_SESSION['current_item']);
        unset($_SESSION['category']);
    }

    if (isset($_POST['select_category'])) {
        $_SESSION['category'] = $_POST['category'];
        $category = array_filter($categories, function($cat) {
            return $cat['name'] === $_SESSION['category'];
        });
        $category = reset($category);
        $item = $category['items'][array_rand($category['items'])];
        $_SESSION['current_item'] = $item;
        $_SESSION['attempts'] = 0;
        $_SESSION['hints'] = [];
    }

    $message = '';
    $game_over = false;

    $hints_html = '';
    if (!empty($_SESSION['hints'])) {
        $hints_html .= '<div class="hints-container"><div class="hints">';
        $hints_html .= '<p>Wskazówki:</p>';
        foreach ($_SESSION['hints'] as $hint) {
            $hints_html .= '<span class="hint">' . $hint . '</span>';
        }
        $hints_html .= '</div></div>';
    }

    if (isset($_POST['guess'])) {
        $guess = $_POST['guess'];
        
        if (isCorrectGuess($guess, $_SESSION['current_item'])) {
            $message = "Gratulacje! Zgadłeś poprawnie: " . $_SESSION['current_item']['name'];
            $game_over = true;
        } else {
            $_SESSION['attempts']++;
            if ($_SESSION['attempts'] >= 5) {
                $message = "Niestety, nie udało się odgadnąć. Poprawna odpowiedź to: " . $_SESSION['current_item']['name'];
                $game_over = true;
            } else {
                $hint_index = $_SESSION['attempts'] - 1;
                $_SESSION['hints'][] = $_SESSION['current_item']['hints'][$hint_index];
                $message = "Niepoprawna odpowiedź. Spróbuj ponownie!";
            }
        }
    }
} catch (Exception $e) {
    $error_message = "Wystąpił błąd: " . $e->getMessage();
}

$category_options = isset($_SESSION['category']) ? getCategoryOptions($categories, $_SESSION['category']) : [];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zgadnij element z SCP:SL</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Zgadnij element z SCP:SL</h1>
        
        <?php if (isset($_SESSION['category'])): ?>
            <form method="post" class="back-form">
                <button type="submit" name="back_to_category" class="back-button">Powrót do wyboru kategorii</button>
            </form>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php elseif (!isset($_SESSION['category'])): ?>
            <form method="post" class="category-selection">
                <select name="category" required>
                    <option value="" disabled selected>Wybierz kategorię</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['name']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="select_category">Rozpocznij grę</button>
            </form>
        <?php elseif (isset($_SESSION['current_item']) && !$game_over): ?>
            <p class="category">Kategoria: <?php echo $_SESSION['category']; ?></p>
            <p>Próba <?php echo $_SESSION['attempts'] + 1; ?> z 5</p>
            <form method="post" id="guess-form">
                <div class="input-container">
                    <input type="text" id="guess-input" placeholder="Wpisz swoją odpowiedź" autocomplete="off">
                    <div id="guess-options" class="guess-options"></div>
                </div>
                <input type="hidden" name="guess" id="guess-hidden">
                <button type="submit" id="submit-guess">Zgadnij</button>
            </form>
            
            <?php if (!empty($_SESSION['hints'])): ?>
                <?php echo $hints_html; ?>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
        
        <?php if ($game_over): ?>
            <div class="game-controls">
                <form method="post" style="display: inline;">
                    <button type="submit" name="new_game">Nowa gra</button>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if (!isset($_SESSION['current_item']) || $game_over): ?>
            <button id="show-items">Lista rzeczy do zgadnięcia</button>
        <?php endif; ?>
    </div>

    <div id="item-list-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <?php echo renderItemList($categories); ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const guessInput = document.getElementById('guess-input');
        const guessOptions = document.getElementById('guess-options');
        const guessHidden = document.getElementById('guess-hidden');
        const submitButton = document.getElementById('submit-guess');
        const guessForm = document.getElementById('guess-form');
        const categoryOptions = <?php echo json_encode($category_options); ?>;

        function updateOptions(value) {
            const matchingOptions = categoryOptions.filter(option => 
                option.toLowerCase().includes(value.toLowerCase())
            );

            guessOptions.innerHTML = '';
            matchingOptions.forEach(option => {
                const div = document.createElement('div');
                div.textContent = option;
                div.addEventListener('click', function() {
                    guessInput.value = this.textContent;
                    guessHidden.value = this.textContent;
                    guessOptions.style.display = 'none';
                    submitButton.disabled = false;
                });
                guessOptions.appendChild(div);
            });

            if (matchingOptions.length > 0) {
                guessOptions.style.display = 'block';
                guessOptions.style.maxHeight = '150px';
                guessOptions.style.overflowY = 'auto';
            } else {
                guessOptions.style.display = 'none';
            }
        }

        if (guessInput) {
            guessInput.addEventListener('input', function() {
                updateOptions(this.value);
            });

            guessInput.addEventListener('focus', function() {
                if (this.value) {
                    updateOptions(this.value);
                } else {
                    updateOptions('');
                }
            });

            document.addEventListener('click', function(e) {
                if (e.target !== guessInput && !guessOptions.contains(e.target)) {
                    guessOptions.style.display = 'none';
                }
            });

            guessForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (guessHidden.value || categoryOptions.includes(guessInput.value)) {
                    guessHidden.value = guessHidden.value || guessInput.value;
                    this.submit();
                } else {
                    alert('Proszę wybrać odpowiedź z listy podpowiedzi.');
                }
            });
        }

        const modal = document.getElementById('item-list-modal');
        const btn = document.getElementById('show-items');
        const span = document.getElementsByClassName('close')[0];

        if (btn) {
            btn.onclick = function() {
                modal.style.display = "block";
            }
        }

        if (span) {
            span.onclick = function() {
                modal.style.display = "none";
            }
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });
    </script>
</body>
</html>