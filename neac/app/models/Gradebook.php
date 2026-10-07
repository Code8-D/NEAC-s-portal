<?php

require_once __DIR__ . '/../core/Database.php';

class Gradebook
{
    public static function publishResult(int $studentId, int $courseId, string $assessmentType, float $score, ?string $grade = null, ?string $remarks = null): int
    {
        $curve = [90 => 'A', 75 => 'B', 60 => 'C', 50 => 'D', 0 => 'F'];
        if (!$grade) {
            foreach ($curve as $min => $g) {
                if ($score >= $min) { $grade = $g; break; }
            }
        }
        $stmt = Database::connection()->prepare(
            'INSERT INTO results (student_id, course_id, assessment_type, score, grade, remarks, published_at)
             VALUES (:s, :c, :a, :sc, :g, :r, CURRENT_TIMESTAMP)'
        );
        $stmt->execute(['s' => $studentId, 'c' => $courseId, 'a' => $assessmentType, 'sc' => $score, 'g' => $grade, 'r' => $remarks]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function forStudent(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*, courses.title AS course_title, courses.code AS course_code
             FROM results r INNER JOIN courses ON courses.id = r.course_id
             WHERE r.student_id = :s ORDER BY r.published_at DESC'
        );
        $stmt->execute(['s' => $studentId]);
        return $stmt->fetchAll();
    }

    public static function forCourse(int $courseId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*, users.full_name AS student_name, students.student_number
             FROM results r INNER JOIN students ON students.id = r.student_id
             INNER JOIN users ON users.id = students.user_id
             WHERE r.course_id = :c ORDER BY r.published_at DESC'
        );
        $stmt->execute(['c' => $courseId]);
        return $stmt->fetchAll();
    }

    public static function courseGradebook(int $courseId): array
    {
        $db = Database::connection();
        $enrolled = $db->prepare(
            'SELECT students.id AS student_id, users.full_name AS student_name, students.student_number
             FROM enrollments e INNER JOIN students ON students.id = e.student_id
             INNER JOIN users ON users.id = students.user_id
             WHERE e.course_id = :c AND e.status = "active" ORDER BY users.full_name'
        );
        $enrolled->execute(['c' => $courseId]);
        $students = $enrolled->fetchAll();
        $results = $db->prepare('SELECT * FROM results WHERE course_id = :c AND student_id = :s ORDER BY published_at DESC');
        foreach ($students as &$s) {
            $results->execute(['c' => $courseId, 's' => $s['student_id']]);
            $s['results'] = $results->fetchAll();
            $avg = 0;
            $count = count($s['results']);
            if ($count > 0) {
                $sum = array_sum(array_column($s['results'], 'score'));
                $avg = round($sum / $count, 1);
            }
            $s['average'] = $avg;
        }
        return $students;
    }

    public static function fromClassroomSubmission(int $submissionId): void
    {
        $db = Database::connection();
        $sub = $db->prepare(
            'SELECT s.*, cw.classroom_id, classrooms.course_id FROM classroom_submissions s
             INNER JOIN classroom_classwork cw ON cw.id = s.classwork_id
             INNER JOIN classrooms ON classrooms.id = cw.classroom_id
             WHERE s.id = :id'
        );
        $sub->execute(['id' => $submissionId]);
        $s = $sub->fetch();
        if (!$s || !$s['course_id']) return;
        $total = $db->prepare('SELECT COALESCE(SUM(points),0) FROM classroom_grades WHERE submission_id = :s');
        $total->execute(['s' => $submissionId]);
        $score = (float) $total->fetchColumn();
        if ($score > 0) {
            self::publishResult((int) $s['student_id'], (int) $s['course_id'], 'assignment', $score, null, 'Graded from classroom submission');
        }
    }
}
