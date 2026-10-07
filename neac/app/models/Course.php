<?php

require_once __DIR__ . '/../core/Database.php';

class Course
{
    public static function all(): array
    {
        Database::migrate();

        return Database::connection()
            ->query(
                'SELECT courses.id, courses.title, courses.code, courses.status,
                        COALESCE(users.full_name, "Unassigned") AS teacher,
                        COUNT(DISTINCT enrollments.student_id) AS students,
                        COUNT(DISTINCT lessons.id) AS lessons,
                        COALESCE(
                            (SELECT ROUND(AVG(enrollments.progress_percent)) FROM enrollments WHERE enrollments.course_id = courses.id),
                            0
                        ) AS progress
                 FROM courses
                 LEFT JOIN teachers ON teachers.id = courses.teacher_id
                 LEFT JOIN users ON users.id = teachers.user_id
                 LEFT JOIN enrollments ON enrollments.course_id = courses.id
                 LEFT JOIN lessons ON lessons.course_id = courses.id
                 GROUP BY courses.id
                 ORDER BY courses.id DESC'
            )
            ->fetchAll();
    }

    public static function create(array $course): int
    {
        Database::migrate();

        $statement = Database::connection()->prepare(
            'INSERT INTO courses (program_id, teacher_id, title, code, description, status)
             VALUES (:program_id, :teacher_id, :title, :code, :description, :status)'
        );
        $statement->execute([
            'program_id' => (int) ($course['program_id'] ?? 1),
            'teacher_id' => !empty($course['teacher_id']) ? (int) $course['teacher_id'] : null,
            'title' => trim($course['title'] ?? ''),
            'code' => strtoupper(trim($course['code'] ?? '')),
            'description' => trim($course['description'] ?? ''),
            'status' => trim($course['status'] ?? 'draft'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        Database::migrate();

        $statement = Database::connection()->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public static function delete(int $id): bool
    {
        Database::migrate();

        $statement = Database::connection()->prepare('DELETE FROM courses WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }
}
