<?php

namespace Shindasayonara\Minesweeper;

class Database
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = new \PDO('sqlite:minesweeper.db');
        $this->createTables();
    }

    private function createTables()
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS games (
            id INTEGER PRIMARY KEY,
            date TEXT,
            player_name TEXT UNIQUE, -- добавлено уникальное ограничение
            width INTEGER,
            height INTEGER,
            mines INTEGER,
            board TEXT,
            revealed TEXT,
            gameOver INTEGER
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS moves (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER,
            move_number INTEGER,
            x INTEGER,
            y INTEGER,
            result TEXT,
            FOREIGN KEY (game_id) REFERENCES games(id)
        )");
        // $this->resetAutoIncrement();
    }
    //public function resetAutoIncrement() {
    //    $this->pdo->exec("DELETE FROM sqlite_sequence WHERE name='games'");
    //}

    public function saveGame($gameState, $playerName)
    {
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO games (date, player_name, width, height, mines, board, revealed, gameOver) 
                                     VALUES (:date, :player_name, :width, :height, :mines, :board, :revealed, :gameOver)");
        $stmt->execute([
            ':date' => date('Y-m-d H:i:s'),
            ':player_name' => $playerName,
            ':width' => $gameState['width'],
            ':height' => $gameState['height'],
            ':mines' => $gameState['mines'],
            ':board' => json_encode($gameState['board']),
            ':revealed' => json_encode($gameState['revealed']),
            ':gameOver' => $gameState['gameOver']
        ]);

        if ($this->pdo->lastInsertId() == 0) {
            $stmt = $this->pdo->prepare("UPDATE games SET date = :date, width = :width, height = :height, mines = :mines, 
                                          board = :board, revealed = :revealed, gameOver = :gameOver WHERE player_name = :player_name");
            $stmt->execute([
                ':date' => date('Y-m-d H:i:s'),
                ':player_name' => $playerName,
                ':width' => $gameState['width'],
                ':height' => $gameState['height'],
                ':mines' => $gameState['mines'],
                ':board' => json_encode($gameState['board']),
                ':revealed' => json_encode($gameState['revealed']),
                ':gameOver' => $gameState['gameOver']
            ]);
        }

        return $this->pdo->lastInsertId();
    }

    public function loadGame($gameId)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM games WHERE id = :id");
        $stmt->execute([':id' => $gameId]);
        $game = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($game) {
            $game['board'] = json_decode($game['board'], true);
            $game['revealed'] = json_decode($game['revealed'], true);
        }

        return $game;
    }

    public function saveMove($gameId, $moveNumber, $x, $y, $result)
    {
        if (!$this->gameExists($gameId)) {
            \cli\line("Error: Game with ID $gameId does not exist.");
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO moves (game_id, move_number, x, y, result) 
                                     VALUES (:game_id, :move_number, :x, :y, :result)");
        $stmt->execute([
            ':game_id' => $gameId,
            ':move_number' => $moveNumber,
            ':x' => $x,
            ':y' => $y,
            ':result' => $result
        ]);

        \cli\line("Move saved: Game ID: $gameId, Move Number: $moveNumber, Coordinates: ($x, $y), Result: $result");
    }

    private function gameExists($gameId)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM games WHERE id = :id");
        $stmt->execute([':id' => $gameId]);
        return $stmt->fetchColumn() > 0;
    }

    public function listGames()
    {
        $stmt = $this->pdo->query("SELECT id, date, player_name, width, height, mines, gameOver FROM games");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function replayGame($gameId)
    {
        $gameData = $this->loadGame($gameId);
        if (!$gameData) {
            echo "Game not found!\n";
            return;
        }

        echo "Stories of {$gameData['player_name']} game\n";
        echo "ID: {$gameData['id']} | Date: {$gameData['date']} | Player: {$gameData['player_name']} | Size: {$gameData['width']}x{$gameData['height']} | Mines: {$gameData['mines']} | Status: " . ($gameData['gameOver'] ? 'Finished' : 'In Progress') . "\n";

        $moves = $this->pdo->prepare("SELECT * FROM moves WHERE game_id = :game_id ORDER BY move_number");
        $moves->execute([':game_id' => $gameId]);

        foreach ($moves->fetchAll(\PDO::FETCH_ASSOC) as $index => $move) {
            echo "Move #" . ($index + 1) . ": ({$move['x']}, {$move['y']}) - {$move['result']}\n";
        }
    }
}