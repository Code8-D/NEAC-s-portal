<?php

require_once __DIR__ . '/../core/Database.php';

class HomePost
{
    public const TYPES = ['event', 'status', 'announcement'];

    public static function all(): array
    {
        return Database::connection()
            ->query(
                'SELECT hp.*, users.full_name AS author_name
                 FROM home_posts hp
                 INNER JOIN users ON users.id = hp.created_by
                 ORDER BY hp.created_at DESC, hp.id DESC'
            )
            ->fetchAll();
    }

    public static function published(int $limit = 6): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT hp.*, users.full_name AS author_name
             FROM home_posts hp
             INNER JOIN users ON users.id = hp.created_by
             WHERE hp.is_published = 1
             ORDER BY
                 CASE WHEN hp.post_type = \'event\' AND hp.event_date IS NOT NULL AND hp.event_date >= date(\'now\') THEN 0 ELSE 1 END,
                 hp.created_at DESC, hp.id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', max(1, (int) $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM home_posts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $type, string $title, string $body, ?string $eventDate, ?string $imagePath, int $createdBy): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO home_posts (post_type, title, body, event_date, image_path, is_published, created_by)
             VALUES (:t, :ti, :b, :d, :img, 1, :c)'
        );
        $stmt->execute([
            't' => in_array($type, self::TYPES, true) ? $type : 'announcement',
            'ti' => $title,
            'b' => $body,
            'd' => $eventDate ?: null,
            'img' => $imagePath ?: null,
            'c' => $createdBy,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, string $type, string $title, string $body, ?string $eventDate, ?string $imagePath, bool $published): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE home_posts
             SET post_type = :t, title = :ti, body = :b, event_date = :d, image_path = :img, is_published = :p, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            't' => in_array($type, self::TYPES, true) ? $type : 'announcement',
            'ti' => $title,
            'b' => $body,
            'd' => $eventDate ?: null,
            'img' => $imagePath ?: null,
            'p' => $published ? 1 : 0,
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM home_posts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
