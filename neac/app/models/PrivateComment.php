<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Notification.php';

class PrivateComment
{
    public static function create(int $classroomId, int $studentId, int $teacherId, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO classroom_private_comments (classroom_id, student_id, teacher_id, body)
             VALUES (:c, :s, :t, :b)'
        );
        $stmt->execute(['c' => $classroomId, 's' => $studentId, 't' => $teacherId, 'b' => $body]);
        $id = (int) Database::connection()->lastInsertId();
        $userStmt = Database::connection()->prepare('SELECT user_id FROM students WHERE id = :s');
        $userStmt->execute(['s' => $studentId]);
        $userId = $userStmt->fetchColumn();
        if ($userId) {
            Notification::create((int) $userId, 'New private comment', 'Your teacher left you a private comment.', 'private_comment');
        }
        return $id;
    }

    public static function forStudent(int $classroomId, int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT pc.*, users.full_name AS teacher_name
             FROM classroom_private_comments pc
             INNER JOIN users ON users.id = pc.teacher_id
             WHERE pc.classroom_id = :c AND pc.student_id = :s
             ORDER BY pc.created_at DESC'
        );
        $stmt->execute(['c' => $classroomId, 's' => $studentId]);
        return $stmt->fetchAll();
    }

    public static function forTeacher(int $classroomId, int $teacherId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT pc.*, users.full_name AS student_name
             FROM classroom_private_comments pc
             INNER JOIN users ON users.id = pc.student_id
             WHERE pc.classroom_id = :c AND pc.teacher_id = :t
             ORDER BY pc.created_at DESC'
        );
        $stmt->execute(['c' => $classroomId, 't' => $teacherId]);
        return $stmt->fetchAll();
    }
}
