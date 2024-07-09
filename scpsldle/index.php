<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCP Guessing Game</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col relative overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div id="animated-bg" class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-700"></div>
    </div>
    <div class="relative z-10 flex flex-col min-h-screen">
        <header class="bg-gray-800 bg-opacity-80 p-4 backdrop-filter backdrop-blur-md">
            <div class="container mx-auto flex justify-between items-center">
                <h1 class="text-2xl font-bold">SCP Guessing Game</h1>
                <nav>
                    <ul class="flex space-x-4">
                        <li><a href="#" id="home-button" class="hover:text-yellow-400 transition-colors">Home</a></li>
                        <li><a href="#" id="play-button" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition-colors">Graj</a></li>
                        <li><a href="https://discord.gg/scpsl-pl" target="_blank" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded transition-colors"><i class="fab fa-discord mr-2"></i>Discord</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <main class="flex-grow container mx-auto p-4">
            <div id="stats-container" class="bg-gray-800 bg-opacity-80 rounded-lg p-6 shadow-lg mb-6 backdrop-filter backdrop-blur-md">
                <h2 class="text-xl mb-4">Statystyki</h2>
                <div id="stats-grid" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="stat-item">
                        <h3>Rozegrane gry</h3>
                        <p id="games-played">0</p>
                    </div>
                    <div class="stat-item">
                        <h3>Wygrane gry</h3>
                        <p id="games-won">0</p>
                    </div>
                    <div class="stat-item">
                        <h3>Procent wygranych</h3>
                        <p id="win-percentage">0%</p>
                    </div>
                </div>
            </div>

            <div id="game-container" class="bg-gray-800 bg-opacity-80 rounded-lg p-6 shadow-lg hidden backdrop-filter backdrop-blur-md">
                <h2 class="text-xl mb-4">Wybierz kategorię:</h2>
                <div id="category-buttons" class="mb-6 flex flex-wrap gap-2">
                    <button class="category-btn bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition-colors" data-category="scp">SCP</button>
                    <button class="category-btn bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition-colors" data-category="cards">Karty</button>
                    <button class="category-btn bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition-colors" data-category="player-class">Klasa Gracza</button>
                    <button class="category-btn bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition-colors" data-category="weapon">Broń</button>
                    <button class="category-btn bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition-colors" data-category="item">Item</button>
                    <button class="category-btn bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition-colors" data-category="upgrade">Upgrade</button>
                </div>
                <div id="guess-section" class="space-y-4 hidden">
                    <h2 class="text-xl mb-4">Zgadnij obiekt:</h2>
                    <div id="guess-input-container" class="relative">
                        <input type="text" id="guess-input" placeholder="Wpisz swoją odpowiedź" class="w-full p-2 rounded bg-gray-700" disabled>
                        <ul id="autocomplete-list" class="absolute w-full bg-gray-700 rounded mt-1 hidden"></ul>
                    </div>
                    <button id="guess-button" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition-colors w-full" disabled>Zgaduj</button>
                    <div id="attempts-left" class="text-lg">Pozostałe próby: <span id="attempts-count">6</span></div>
                    <div id="guesses-container" class="mt-4">
                        <h3 class="text-lg mb-2">Twoje próby:</h3>
                        <div id="guesses-list" class="space-y-2"></div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-gray-800 bg-opacity-80 p-4 mt-8 backdrop-filter backdrop-blur-md">
            <div class="container mx-auto text-center">
                <p>&copy; <?php echo date("Y"); ?> SCP Guessing Game. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <script src="scripts.js"></script>
</body>
</html>