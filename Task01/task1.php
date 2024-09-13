<?php

// Определяем путь к файлу README.md
$readmeFile = 'README.md';

// Проверяем, существует ли файл
if (!file_exists($readmeFile)) {
    echo "Файл README.md не найден: $readmeFile\n";
    exit(1);
}

// Считываем содержимое файла
$readmeContent = file_get_contents($readmeFile);

// Регулярное выражение для извлечения описания задачи (начиная со второй строки)
$pattern = '/## Задача для первого блока\s*### Вариант 5\s*.*?Игра проходит(.*?)(?=## |$)/s';

// Поиск соответствий
if (preg_match($pattern, $readmeContent, $matches)) {
    $taskDescription = trim($matches[1]);

    // Выводим на экран описание игры
    echo "Инструкция к игре \"Сапер\" (Minesweeper)\n";
    echo "-------------------------------------------\n";
    echo "Игра проходит ",$taskDescription;
} else {
    echo "Описание задачи не найдено в файле README.md\n";
    exit(1);
}

// Завершаем выполнение скрипта
exit(0);
