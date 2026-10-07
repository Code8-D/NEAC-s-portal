<?php

require_once __DIR__ . '/../core/Database.php';

class Attendance
{
    public static function record(int $studentId, string $status, ?int $courseId = null, ?int $recordedBy = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO attendance (student_id, course_id, attendance_date, status, recorded_by)
             VALUES (:s, :c, CURRENT_DATE, :st, :r)'
        );
        $stmt->execute(['s' => $studentId, 'c' => $courseId, 'st' => $status, 'r' => $recordedBy]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function todayExists(int $studentId, ?int $courseId = null): bool
    {
        $sql = 'SELECT 1 FROM attendance WHERE student_id = :s AND attendance_date = CURRENT_DATE';
        if ($courseId) $sql .= ' AND course_id = :c';
        $stmt = Database::connection()->prepare($sql);
        $params = ['s' => $studentId];
        if ($courseId) $params['c'] = $courseId;
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public static function forStudent(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, courses.title AS course_title FROM attendance a
             LEFT JOIN courses ON courses.id = a.course_id
             WHERE a.student_id = :s ORDER BY a.attendance_date DESC'
        );
        $stmt->execute(['s' => $studentId]);
        return $stmt->fetchAll();
    }

    public static function summary(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT status, COUNT(*) AS count FROM attendance WHERE student_id = :s GROUP BY status"
        );
        $stmt->execute(['s' => $studentId]);
        $rows = $stmt->fetchAll();
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $summary[$r['status']] = (int) $r['count'];
            $summary['total'] += (int) $r['count'];
        }
        $summary['percent'] = $summary['total'] > 0 ? round(($summary['present'] / $summary['total']) * 100) : 0;
        return $summary;
    }

    public static function recentByCourse(int $courseId, int $limit = 20): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, users.full_name AS student_name FROM attendance a
             INNER JOIN students ON students.id = a.student_id
             INNER JOIN users ON users.id = students.user_id
             WHERE a.course_id = :c ORDER BY a.attendance_date DESC LIMIT :l'
        );
        $stmt->execute(['c' => $courseId, 'l' => $limit]);
        return $stmt->fetchAll();
    }
}
