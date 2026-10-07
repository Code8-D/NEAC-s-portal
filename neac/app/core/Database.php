<?php

class Database
{
    private static ?PDO $connection = null;
    private static bool $migrated = false;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            require_once __DIR__ . '/../../config/config.php';

            $directory = dirname(DB_PATH);
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            self::$connection = new PDO('sqlite:' . DB_PATH);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$connection->exec('PRAGMA foreign_keys = ON');
        }

        return self::$connection;
    }

    public static function migrate(): void
    {
        if (self::$migrated) {
            return;
        }
        $schema = file_get_contents(__DIR__ . '/../../database/schema.sql');

        if ($schema === false) {
            throw new RuntimeException('Database schema file could not be loaded.');
        }

        self::connection()->exec($schema);
        foreach (self::columnMigrations() as $sql) {
            try {
                self::connection()->exec($sql);
            } catch (Throwable $e) {
                // Column already exists on this database.
            }
        }
        self::$migrated = true;
    }

    private static function columnMigrations(): array
    {
        return [
            'ALTER TABLE students ADD COLUMN place_of_birth TEXT',
            'ALTER TABLE home_posts ADD COLUMN image_path TEXT',
            'ALTER TABLE quizzes ADD COLUMN time_limit INTEGER DEFAULT NULL',
            // Lesson planner: scheduling / planning fields (idempotent — ignored if already present).
            'ALTER TABLE lessons ADD COLUMN objectives TEXT',
            'ALTER TABLE lessons ADD COLUMN lesson_date TEXT',
            'ALTER TABLE lessons ADD COLUMN start_time TEXT',
            'ALTER TABLE lessons ADD COLUMN duration_minutes INTEGER NOT NULL DEFAULT 60',
            'ALTER TABLE lessons ADD COLUMN venue TEXT',
            'ALTER TABLE lessons ADD COLUMN status TEXT NOT NULL DEFAULT \'planned\'',
            'ALTER TABLE lessons ADD COLUMN planned_by INTEGER',
            'ALTER TABLE lessons ADD COLUMN updated_at TEXT',
            "DROP TABLE IF EXISTS quiz_questions_new;
CREATE TABLE quiz_questions_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    question TEXT NOT NULL,
    question_type TEXT NOT NULL DEFAULT 'multiple_choice' CHECK (question_type IN ('multiple_choice', 'true_false', 'short_answer', 'fill_blank', 'essay')),
    options_json TEXT,
    correct_answer TEXT,
    marks REAL NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);
INSERT INTO quiz_questions_new SELECT id, quiz_id, question, question_type, options_json, correct_answer, marks, sort_order FROM quiz_questions;
DROP TABLE quiz_questions;
ALTER TABLE quiz_questions_new RENAME TO quiz_questions;",
        ];
    }

    public static function programs(): array
    {
        self::migrate();

        return self::connection()
            ->query('SELECT id, name FROM programs WHERE is_active = 1 ORDER BY id')
            ->fetchAll();
    }

    public static function status(): string
    {
        return file_exists(DB_PATH) ? 'SQLite database active' : 'Database pending setup';
    }
}

