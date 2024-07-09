document.addEventListener('DOMContentLoaded', function() {
    const guessForm = document.getElementById('guess-form');
    if (guessForm) {
        guessForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const puzzleId = document.getElementById('puzzle-id').value;
            const guess = document.getElementById('guess').value;
            
            fetch('check_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `puzzle_id=${puzzleId}&guess=${encodeURIComponent(guess)}`
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('result').textContent = data.message;
                if (data.correct) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('result').textContent = 'Wystąpił błąd podczas sprawdzania odpowiedzi.';
            });
        });
    }
});