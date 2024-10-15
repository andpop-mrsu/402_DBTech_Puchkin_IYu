<?php

namespace Shindasayonara\Minesweeper\Controller;

use Shindasayonara\Minesweeper\Game;
use Shindasayonara\Minesweeper\View;

function startGame($width, $height, $mines, $saveToDatabase = false) {
    if ($saveToDatabase) {
        \cli\line("Note: The game is currently not saving to the database.");
    }
    $game = new Game($width, $height, $mines);
    $game->play();
}
