document.addEventListener('DOMContentLoaded', () => {
    const categoryButtons = document.querySelectorAll('.category-btn');
    const gameContainer = document.getElementById('game-container');
    const guessSection = document.getElementById('guess-section');
    const guessInput = document.getElementById('guess-input');
    const guessButton = document.getElementById('guess-button');
    const attemptsCount = document.getElementById('attempts-count');
    const guessesList = document.getElementById('guesses-list');
    const autocompleteList = document.getElementById('autocomplete-list');
    const playButton = document.getElementById('play-button');
    const statsContainer = document.getElementById('stats-container');
    const homeButton = document.getElementById('home-button');

    let currentCategory = '';
    let attemptsLeft = 6;
    let correctAnswer = '';
    let subcategories = {};
    let stats = { gamesPlayed: 0, gamesWon: 0 };
    let currentGameWon = false;
    let gameData = {};

    function loadGameData() {
        return fetch('data.json')
            .then(response => response.json())
            .then(data => {
                gameData = data;
                console.log('Game data loaded successfully');
            })
            .catch(error => console.error('Error loading game data:', error));
    }

    function loadStats() {
        return fetch('stats.json')
            .then(response => response.json())
            .then(data => {
                stats = data;
                updateStatsDisplay();
            })
            .catch(error => {
                console.error('Error loading stats:', error);
                stats = { gamesPlayed: 0, gamesWon: 0 }; // Domyślne wartości w przypadku błędu
                updateStatsDisplay();
            });
    }

    function saveStats(gameWon) {
        stats.gamesPlayed++;
        if (gameWon) {
            stats.gamesWon++;
        }
        
        fetch('save_stats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(stats),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Stats saved successfully');
                updateStatsDisplay();
            } else {
                console.error('Error saving stats:', data.message);
            }
        })
        .catch(error => console.error('Error saving stats:', error));
    }

    function updateStatsDisplay() {
        document.getElementById('games-played').textContent = stats.gamesPlayed;
        document.getElementById('games-won').textContent = stats.gamesWon;
        const winPercentage = stats.gamesPlayed > 0 ? (stats.gamesWon / stats.gamesPlayed * 100).toFixed(2) : 0;
        document.getElementById('win-percentage').textContent = `${winPercentage}%`;
    }

    function startNewGame(category) {
        currentCategory = category;
        attemptsLeft = 6;
        attemptsCount.textContent = attemptsLeft;
        guessesList.innerHTML = '';
        const categoryData = gameData[currentCategory];
        const randomIndex = Math.floor(Math.random() * categoryData.length);
        const selectedObject = categoryData[randomIndex];
        correctAnswer = selectedObject.name;
        subcategories = selectedObject.subcategories;
        console.log('New game started. Category:', currentCategory, 'Correct answer:', correctAnswer);
        guessButton.disabled = false;
        guessInput.disabled = false;
        guessInput.value = '';
        guessInput.focus();
        guessSection.classList.remove('hidden');
        currentGameWon = false;
    }

    function checkGuess() {
        const guess = guessInput.value.trim().toUpperCase();
        if (guess === '') return;

        attemptsLeft--;
        attemptsCount.textContent = attemptsLeft;

        const resultElement = document.createElement('div');
        resultElement.className = 'p-2 rounded bg-gray-700 flex flex-wrap items-center gap-2';

        const guessName = document.createElement('span');
        guessName.textContent = guess;
        guessName.className = 'font-bold';
        resultElement.appendChild(guessName);

        const guessedObject = gameData[currentCategory].find(obj => obj.name.toUpperCase() === guess);
        
        Object.entries(subcategories).forEach(([key, value]) => {
            const subcategoryElement = document.createElement('span');
            const isCorrect = guessedObject && guessedObject.subcategories[key] === value;
            subcategoryElement.className = `px-2 py-1 rounded ${isCorrect ? 'bg-green-600' : 'bg-red-600'}`;
            subcategoryElement.textContent = `${key}: ${guessedObject ? guessedObject.subcategories[key] : 'N/A'}`;
            resultElement.appendChild(subcategoryElement);
        });

        guessesList.prepend(resultElement);

        if (guess === correctAnswer) {
            const winMessage = document.createElement('div');
            winMessage.className = 'p-2 rounded bg-green-600 mt-2';
            winMessage.textContent = 'Gratulacje! Zgadłeś poprawnie!';
            guessesList.prepend(winMessage);
            guessButton.disabled = true;
            guessInput.disabled = true;
            saveStats(true);
        } else if (attemptsLeft === 0) {
            const gameOverElement = document.createElement('div');
            gameOverElement.className = 'p-2 rounded bg-yellow-600 mt-2';
            gameOverElement.textContent = `Koniec gry. Poprawna odpowiedź to: ${correctAnswer}`;
            guessesList.prepend(gameOverElement);
            guessButton.disabled = true;
            guessInput.disabled = true;
            saveStats(false);
        }

        guessInput.value = '';
        autocompleteList.innerHTML = '';
        autocompleteList.classList.add('hidden');
    }

    function showAutocomplete() {
        const input = guessInput.value.toLowerCase();
        let filteredItems = gameData[currentCategory].map(item => item.name);

        if (input !== '') {
            filteredItems = filteredItems.filter(item => item.toLowerCase().includes(input));
        }

        autocompleteList.innerHTML = '';

        if (filteredItems.length > 0) {
            filteredItems.slice(0, 7).forEach(item => {
                const li = document.createElement('li');
                li.textContent = item;
                li.className = 'p-2 hover:bg-gray-600 cursor-pointer';
                li.onclick = () => {
                    guessInput.value = item;
                    autocompleteList.innerHTML = '';
                    autocompleteList.classList.add('hidden');
                };
                autocompleteList.appendChild(li);
            });
            autocompleteList.classList.remove('hidden');
        } else {
            autocompleteList.classList.add('hidden');
        }
    }

    categoryButtons.forEach(button => {
        button.addEventListener('click', () => {
            startNewGame(button.dataset.category);
            categoryButtons.forEach(btn => btn.classList.remove('bg-green-600'));
            button.classList.add('bg-green-600');
        });
    });

    guessButton.addEventListener('click', checkGuess);

    guessInput.addEventListener('input', showAutocomplete);
    guessInput.addEventListener('focus', showAutocomplete);

    document.addEventListener('click', (e) => {
        if (!autocompleteList.contains(e.target) && e.target !== guessInput) {
            autocompleteList.classList.add('hidden');
        }
    });

    guessInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            checkGuess();
        }
    });

    playButton.addEventListener('click', () => {
        statsContainer.classList.add('hidden');
        gameContainer.classList.remove('hidden');
    });

    homeButton.addEventListener('click', () => {
        gameContainer.classList.add('hidden');
        guessSection.classList.add('hidden');
        statsContainer.classList.remove('hidden');
        loadStats(); // Odśwież statystyki przy powrocie na stronę główną
    });

    // Inicjalizacja gry
    Promise.all([loadGameData(), loadStats()])
        .then(() => {
            console.log('Game initialized successfully');
        })
        .catch(error => console.error('Error initializing game:', error));
});