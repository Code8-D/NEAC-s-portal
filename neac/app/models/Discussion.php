<?php

require_once __DIR__ . '/../core/Database.php';

class Discussion
{
    public static function createThread(int $courseId, int $userId, string $title): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO discussion_threads (course_id, created_by, title) VALUES (:c, :u, :t)'
        );
        $stmt->execute(['c' => $courseId, 'u' => $userId, 't' => $title]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function post(int $threadId, int $userId, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO discussion_posts (thread_id, user_id, body) VALUES (:t, :u, :b)'
        );
        $stmt->execute(['t' => $threadId, 'u' => $userId, 'b' => $body]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function threads(int $courseId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT threads.*, users.full_name AS author_name,
                    (SELECT COUNT(*) FROM discussion_posts WHERE thread_id = threads.id) AS post_count,
                    (SELECT MAX(created_at) FROM discussion_posts WHERE thread_id = threads.id) AS last_post_at
             FROM discussion_threads threads
             INNER JOIN users ON users.id = threads.created_by
             WHERE threads.course_id = :c
             ORDER BY last_post_at DESC NULLS LAST, threads.created_at DESC'
        );
        $stmt->execute(['c' => $courseId]);
        return $stmt->fetchAll();
    }

    public static function findThread(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT threads.*, users.full_name AS author_name, courses.title AS course_title
             FROM discussion_threads threads
             INNER JOIN users ON users.id = threads.created_by
             INNER JOIN courses ON courses.id = threads.course_id
             WHERE threads.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function posts(int $threadId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT posts.*, users.full_name AS author_name
             FROM discussion_posts posts
             INNER JOIN users ON users.id = posts.user_id
             WHERE posts.thread_id = :t ORDER BY posts.created_at'
        );
        $stmt->execute(['t' => $threadId]);
        return $stmt->fetchAll();
    }
}
