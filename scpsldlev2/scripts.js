document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const elements = {
        categoryButtons: document.querySelectorAll('.category-btn'),
        gameContainer: document.getElementById('game-container'),
        guessSection: document.getElementById('guess-section'),
        guessInput: document.getElementById('guess-input'),
        guessButton: document.getElementById('guess-button'),
        attemptsCount: document.getElementById('attempts-count'),
        guessesList: document.getElementById('guesses-list'),
        autocompleteList: document.getElementById('autocomplete-list'),
        playButton: document.getElementById('play-button'),
        statsContainer: document.getElementById('stats-container'),
        homeButton: document.getElementById('home-button'),
        categoryButtonsContainer: document.getElementById('category-buttons')
    };

    // Game State
    const gameState = {
        currentCategory: '',
        attemptsLeft: 6,
        correctAnswer: '',
        subcategories: {},
        stats: { gamesPlayed: 0, gamesWon: 0 },
        gameData: {}
    };

    // Game Functions
    const game = {
        async init() {
            try {
                await this.loadGameData();
                await this.loadStats();
                this.attachEventListeners();
                console.log('Game initialized successfully');
            } catch (error) {
                console.error('Error initializing game:', error);
                alert('There was an error initializing the game. Please refresh the page and try again.');
            }
        },

        async loadGameData() {
            try {
                const response = await fetch('data.json');
                gameState.gameData = await response.json();
            } catch (error) {
                console.error('Error loading game data:', error);
                throw new Error('Failed to load game data');
            }
        },

        async loadStats() {
            try {
                const response = await fetch('stats.json');
                gameState.stats = await response.json();
                this.updateStatsDisplay();
            } catch (error) {
                console.error('Error loading stats:', error);
                gameState.stats = { gamesPlayed: 0, gamesWon: 0 };
                this.updateStatsDisplay();
            }
        },

        async saveStats(gameWon) {
            gameState.stats.gamesPlayed++;
            if (gameWon) gameState.stats.gamesWon++;
            
            try {
                const response = await fetch('save_stats.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(gameState.stats)
                });
                const data = await response.json();
                if (data.success) {
                    console.log('Stats saved successfully');
                    this.updateStatsDisplay();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error saving stats:', error);
                alert('There was an error saving your stats. Your progress may not be recorded.');
            }
        },

        updateStatsDisplay() {
            document.getElementById('games-played').textContent = gameState.stats.gamesPlayed;
            document.getElementById('games-won').textContent = gameState.stats.gamesWon;
            const winPercentage = gameState.stats.gamesPlayed > 0 
                ? (gameState.stats.gamesWon / gameState.stats.gamesPlayed * 100).toFixed(2) 
                : 0;
            document.getElementById('win-percentage').textContent = `${winPercentage}%`;
        },

        startNewGame(category) {
            gameState.currentCategory = category;
            gameState.attemptsLeft = 6;
            elements.attemptsCount.textContent = gameState.attemptsLeft;
            elements.guessesList.innerHTML = '';
            
            const categoryData = gameState.gameData[category];
            if (!categoryData || categoryData.length === 0) {
                console.error('Invalid category or no data for category:', category);
                alert('There was an error starting the game. Please try a different category.');
                return;
            }

            const randomIndex = Math.floor(Math.random() * categoryData.length);
            const selectedObject = categoryData[randomIndex];
            gameState.correctAnswer = selectedObject.name;
            gameState.subcategories = selectedObject.subcategories;

            elements.guessButton.disabled = false;
            elements.guessInput.disabled = false;
            elements.guessInput.value = '';
            elements.guessInput.focus();
            elements.guessSection.classList.remove('hidden');
            elements.categoryButtonsContainer.classList.add('hidden');

            this.removePlayAgainButton();
        },

        checkGuess() {
            const guess = elements.guessInput.value.trim().toUpperCase();
            if (guess === '') return;
        
            gameState.attemptsLeft--;
            elements.attemptsCount.textContent = gameState.attemptsLeft;
        
            const resultElement = document.createElement('div');
            resultElement.className = 'p-2 rounded bg-gray-700 flex flex-wrap items-center gap-2';
        
            const guessName = document.createElement('span');
            guessName.textContent = guess;
            guessName.className = 'font-bold';
            resultElement.appendChild(guessName);
        
            const guessedObject = gameState.gameData[gameState.currentCategory].find(obj => obj.name.toUpperCase() === guess);
            
            let correctSubcategories = 0;
            let totalSubcategories = Object.keys(gameState.subcategories).length;
        
            Object.entries(gameState.subcategories).forEach(([key, value]) => {
                const subcategoryElement = document.createElement('span');
                const isCorrect = guessedObject && guessedObject.subcategories[key] === value;
                if (isCorrect) correctSubcategories++;
                subcategoryElement.className = `px-2 py-1 rounded ${isCorrect ? 'bg-green-600' : 'bg-red-600'}`;
                subcategoryElement.textContent = `${key}: ${guessedObject ? guessedObject.subcategories[key] : 'N/A'}`;
                resultElement.appendChild(subcategoryElement);
            });
        
            elements.guessesList.prepend(resultElement);
        
            const isCorrectGuess = guess === gameState.correctAnswer;
            const isOutOfAttempts = gameState.attemptsLeft === 0;
        
            if (isCorrectGuess || isOutOfAttempts) {
                this.endGame(isCorrectGuess);
            }
        
            elements.guessInput.value = '';
            elements.autocompleteList.innerHTML = '';
            elements.autocompleteList.classList.add('hidden');
        },
        
        endGame(isWin) {
            const messageElement = document.createElement('div');
            messageElement.className = `p-2 rounded mt-2 ${isWin ? 'bg-green-600' : 'bg-yellow-600'}`;
            messageElement.textContent = isWin 
                ? 'Gratulacje! Zgadłeś poprawnie!' 
                : `Koniec gry. Poprawna odpowiedź to: ${gameState.correctAnswer}`;
            elements.guessesList.prepend(messageElement);
        
            elements.guessButton.disabled = true;
            elements.guessInput.disabled = true;
            
            this.saveStats(isWin);
            this.addPlayAgainButton();
        },

        resetGame() {
            elements.guessesList.innerHTML = '';
            elements.guessSection.classList.add('hidden');
            elements.guessButton.disabled = false;
            elements.guessInput.disabled = false;
            gameState.attemptsLeft = 6;
            elements.attemptsCount.textContent = gameState.attemptsLeft;
            gameState.currentCategory = '';
            gameState.correctAnswer = '';
            gameState.subcategories = {};

            this.removePlayAgainButton();
            elements.categoryButtons.forEach(btn => btn.classList.remove('bg-green-600'));
            elements.categoryButtonsContainer.classList.remove('hidden');
        },

        addPlayAgainButton() {
            this.removePlayAgainButton(); // Ensure there's no existing button
            const playAgainButton = document.createElement('button');
            playAgainButton.textContent = 'Zagraj ponownie';
            playAgainButton.className = 'play-again-button bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition-colors w-full mt-4';
            playAgainButton.addEventListener('click', () => this.resetGame());
            elements.gameContainer.appendChild(playAgainButton);
        },

        removePlayAgainButton() {
            const playAgainButton = elements.gameContainer.querySelector('.play-again-button');
            if (playAgainButton) {
                playAgainButton.remove();
            }
        },

        showAutocomplete() {
            const input = elements.guessInput.value.toLowerCase();
            let filteredItems = gameState.gameData[gameState.currentCategory].map(item => item.name);

            if (input !== '') {
                filteredItems = filteredItems.filter(item => item.toLowerCase().includes(input));
            }

            elements.autocompleteList.innerHTML = '';

            if (filteredItems.length > 0) {
                filteredItems.slice(0, 7).forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item;
                    li.className = 'p-2 hover:bg-gray-600 cursor-pointer';
                    li.onclick = () => {
                        elements.guessInput.value = item;
                        elements.autocompleteList.innerHTML = '';
                        elements.autocompleteList.classList.add('hidden');
                    };
                    elements.autocompleteList.appendChild(li);
                });
                elements.autocompleteList.classList.remove('hidden');
            } else {
                elements.autocompleteList.classList.add('hidden');
            }
        },

        attachEventListeners() {
            elements.categoryButtons.forEach(button => {
                button.addEventListener('click', () => this.startNewGame(button.dataset.category));
            });

            elements.guessButton.addEventListener('click', () => this.checkGuess());
            elements.guessInput.addEventListener('input', () => this.showAutocomplete());
            elements.guessInput.addEventListener('focus', () => this.showAutocomplete());

            document.addEventListener('click', (e) => {
                if (!elements.autocompleteList.contains(e.target) && e.target !== elements.guessInput) {
                    elements.autocompleteList.classList.add('hidden');
                }
            });

            elements.guessInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.checkGuess();
                }
            });

            elements.playButton.addEventListener('click', () => {
                elements.statsContainer.classList.add('hidden');
                elements.gameContainer.classList.remove('hidden');
            });

            elements.homeButton.addEventListener('click', () => {
                elements.gameContainer.classList.add('hidden');
                elements.guessSection.classList.add('hidden');
                elements.statsContainer.classList.remove('hidden');
                this.loadStats();
            });
        }
    };

    // Initialize the game
    game.init();
});