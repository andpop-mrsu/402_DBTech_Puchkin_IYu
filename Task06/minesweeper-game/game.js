// game.js
import { saveGame, getAllGames, clearDatabase, getGameById } from './db.js';

document.addEventListener('DOMContentLoaded', function() {
    let width, height, mines, gameBoard, cells, gameStatus, gameOver = false, playerName;
    let gameSaved = false;
    let moves = [];

    const startBtn = document.getElementById('startBtn');
    startBtn.addEventListener('click', startNewGame);

    const viewPastGamesBtn = document.getElementById('view-past-games');
    viewPastGamesBtn.addEventListener('click', loadPastGames);

    const clearDbBtn = document.getElementById('clear-db');
    clearDbBtn.addEventListener('click', clearDatabaseHandler);

    const closeReplayBtn = document.getElementById('close-replay');
    closeReplayBtn.addEventListener('click', () => {
        document.getElementById('replay-container').style.display = 'none';
    });

    function startNewGame() {
        playerName = document.getElementById('player-name').value.trim();
        if (!playerName) {
            alert('Please enter your name before starting the game.');
            return;
        }

        width = parseInt(document.getElementById('width').value);
        height = parseInt(document.getElementById('height').value);
        mines = parseInt(document.getElementById('mines').value);

        gameBoard = document.getElementById('game-board');
        gameStatus = document.getElementById('game-status');
        gameOver = false;
        gameSaved = false;
        moves = [];

        gameBoard.innerHTML = '';
        gameStatus.textContent = '';

        cells = [];
        const allCells = width * height;
        const minePositions = generateMines(allCells, mines);

        for (let i = 0; i < height; i++) {
            for (let j = 0; j < width; j++) {
                const cell = document.createElement('div');
                cell.classList.add('cell');
                cell.dataset.index = i * width + j;
                gameBoard.appendChild(cell);
                cells.push(cell);

                cell.addEventListener('click', () => {
                    if (gameOver) return;
                    handleCellClick(cell, minePositions);
                });
            }
        }

        gameBoard.style.gridTemplateColumns = `repeat(${width}, 40px)`;
        gameBoard.style.gridTemplateRows = `repeat(${height}, 40px)`;

        document.querySelector('.game-container').style.display = 'block';
    }

    function generateMines(totalCells, mineCount) {
        const minePositions = new Set();
        while (minePositions.size < mineCount) {
            minePositions.add(Math.floor(Math.random() * totalCells));
        }
        return minePositions;
    }

    function handleCellClick(cell, minePositions) {
        const index = parseInt(cell.dataset.index);
        moves.push(index);

        if (minePositions.has(index)) {
            cell.classList.add('revealed', 'mine');
            gameOver = true;
            gameStatus.textContent = 'Game Over!';
            if (!gameSaved) {
                saveGameData(false);
                gameSaved = true;
            }
            return;
        }

        revealCell(cell, minePositions);
    }

    function revealCell(cell, minePositions) {
        const index = parseInt(cell.dataset.index);
        if (cell.classList.contains('revealed')) return;

        cell.classList.add('revealed');
        const adjacentMines = countAdjacentMines(index, minePositions);

        if (adjacentMines > 0) {
            cell.textContent = adjacentMines;
        } else {
            revealAdjacentCells(index, minePositions);
        }

        if (checkForWin(minePositions) && !gameSaved) {
            gameStatus.textContent = 'You Win!';
            gameOver = true;
            saveGameData(true);
            gameSaved = true;
        }
    }

    function revealAdjacentCells(index, minePositions) {
        const neighbors = [
            -1, 1, -width, width,
            -width - 1, -width + 1, width - 1, width + 1
        ];

        neighbors.forEach(offset => {
            const neighborIndex = index + offset;
            if (neighborIndex < 0 || neighborIndex >= width * height) return;

            const neighborCell = cells[neighborIndex];
            if (!neighborCell.classList.contains('revealed') && !minePositions.has(neighborIndex)) {
                revealCell(neighborCell, minePositions);
            }
        });
    }

    function countAdjacentMines(index, minePositions) {
        const neighbors = [
            -1, 1, -width, width,
            -width - 1, -width + 1, width - 1, width + 1
        ];
        let mineCount = 0;

        neighbors.forEach(offset => {
            const neighborIndex = index + offset;
            if (minePositions.has(neighborIndex)) mineCount++;
        });

        return mineCount;
    }

    function checkForWin(minePositions) {
        return cells.every(cell => {
            const index = parseInt(cell.dataset.index);
            return minePositions.has(index) || cell.classList.contains('revealed');
        });
    }

    async function saveGameData(winStatus) {
        const gameData = {
            playerName: playerName,
            size: `${width}x${height}`,
            mines: mines,
            winStatus: winStatus ? 'Won' : 'Lost',
            timestamp: new Date().toISOString(),
            moves: moves
        };
        await saveGame(gameData);
    }

    async function loadPastGames() {
        const games = await getAllGames();
        const gameList = document.getElementById('game-list');
        gameList.innerHTML = '';

        games.forEach(game => {
            const listItem = document.createElement('li');
            const timestamp = new Date(game.timestamp).toLocaleString('ru-RU', { timeZone: 'Europe/Moscow' });
            listItem.textContent = `Player: ${game.playerName} - Game on ${timestamp} - Size: ${game.size} - Mines: ${game.mines} - Result: ${game.winStatus}`;

            const replayBtn = document.createElement('button');
            replayBtn.textContent = 'Replay';
            replayBtn.addEventListener('click', () => replayGame(game.id));
            listItem.appendChild(replayBtn);

            gameList.appendChild(listItem);
        });
    }

    async function replayGame(gameId) {
        const game = await getGameById(gameId);
        const replayList = document.getElementById('replay-list');
        replayList.innerHTML = '';

        game.moves.forEach(move => {
            const listItem = document.createElement('li');
            listItem.textContent = `Move: ${move}`;
            replayList.appendChild(listItem);
        });

        document.getElementById('replay-container').style.display = 'block';
    }

    async function clearDatabaseHandler() {
        await clearDatabase();
        alert('Database cleared!');
        loadPastGames();
    }
});
