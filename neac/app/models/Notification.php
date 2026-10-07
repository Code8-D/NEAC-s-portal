<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../helpers/Mail.php';

class Notification
{
    public static function create(int $userId, string $title, string $message, string $type = 'general', ?string $email = null): int
    {
        $db = Database::connection();
        $stmt = $db->prepare('INSERT INTO notifications (user_id, title, message, notification_type) VALUES (:u, :t, :m, :n)');
        $stmt->execute(['u' => $userId, 't' => $title, 'm' => $message, 'n' => $type]);
        $id = (int) $db->lastInsertId();
        if ($email) Mail::sendNotification($email, $title, $message);
        return $id;
    }

    public static function forUser(int $userId, ?int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM notifications WHERE user_id = :u ORDER BY created_at DESC LIMIT :lim'
        );
        $stmt->execute(['u' => $userId, 'lim' => $limit]);
        return $stmt->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :u AND read_at IS NULL'
        );
        $stmt->execute(['u' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markRead(int $id, int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
    }

    public static function markAllRead(int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE user_id = :u AND read_at IS NULL'
        );
        $stmt->execute(['u' => $userId]);
    }

    public static function broadcastToClassroom(int $classroomId, string $title, string $message, string $type = 'classroom', ?string $link = null): void
    {
        $members = Database::connection()->prepare(
            'SELECT users.id, users.email FROM classroom_members
             INNER JOIN users ON users.id = classroom_members.user_id
             WHERE classroom_members.classroom_id = :c AND classroom_members.status = "active"'
        );
        $members->execute(['c' => $classroomId]);
        foreach ($members as $m) {
            self::create((int) $m['id'], $title, $message, $type, $m['email']);
        }
    }
}
