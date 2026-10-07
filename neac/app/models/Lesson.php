<?php

require_once __DIR__ . '/../core/Database.php';

class Lesson
{
    public const TYPES = ['video', 'document', 'live', 'text'];
    public const STATUSES = ['planned', 'published', 'in_progress', 'completed', 'cancelled'];

    public static function create(int $courseId, string $title, string $type, ?string $content, ?string $filePath, int $sortOrder, array $plan = []): int
    {
        Database::migrate();
        if (!in_array($type, self::TYPES, true)) {
            $type = 'document';
        }
        $status = in_array($plan['status'] ?? 'planned', self::STATUSES, true) ? $plan['status'] : 'planned';
        $published = ($status === 'published' || ($plan['is_published'] ?? 1)) ? 1 : 0;
        if ($status === 'published') {
            $published = 1;
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO lessons (course_id, title, lesson_type, content, file_path, sort_order, is_published,
              objectives, lesson_date, start_time, duration_minutes, venue, status, planned_by, updated_at)
             VALUES (:c, :t, :l, :co, :f, :s, :p, :o, :d, :st, :du, :v, :status, :pb, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'c' => $courseId,
            't' => $title,
            'l' => $type,
            'co' => $content,
            'f' => $filePath,
            's' => $sortOrder,
            'p' => $published,
            'o' => trim((string) ($plan['objectives'] ?? '')) ?: null,
            'd' => self::cleanDate($plan['lesson_date'] ?? null),
            'st' => self::cleanTime($plan['start_time'] ?? null),
            'du' => max(15, min(480, (int) ($plan['duration_minutes'] ?? 60))),
            'v' => trim((string) ($plan['venue'] ?? '')) ?: null,
            'status' => $status,
            'pb' => !empty($plan['planned_by']) ? (int) $plan['planned_by'] : null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function forCourse(int $courseId, string $orderBy = 'plan'): array
    {
        Database::migrate();
        $order = $orderBy === 'plan'
            ? 'CASE WHEN lesson_date IS NULL OR lesson_date = "" THEN 1 ELSE 0 END, lesson_date, start_time, sort_order, created_at'
            : 'sort_order, created_at';
        $stmt = Database::connection()->prepare(
            "SELECT lessons.*, courses.title AS course_title, courses.code AS course_code,
                    users.full_name AS planner_name
             FROM lessons
             INNER JOIN courses ON courses.id = lessons.course_id
             LEFT JOIN users ON users.id = lessons.planned_by
             WHERE lessons.course_id = :c ORDER BY {$order}"
        );
        $stmt->execute(['c' => $courseId]);
        return $stmt->fetchAll();
    }

    public static function upcomingForTeacher(?int $teacherId, bool $isAdmin, int $limit = 20): array
    {
        Database::migrate();
        $db = Database::connection();
        if ($isAdmin) {
            return $db->query(
                'SELECT lessons.*, courses.title AS course_title, courses.code AS course_code
                 FROM lessons INNER JOIN courses ON courses.id = lessons.course_id
                 WHERE lessons.status IN ("planned","published","in_progress")
                   AND lessons.lesson_date IS NOT NULL AND lessons.lesson_date <> ""
                 ORDER BY lessons.lesson_date, lessons.start_time LIMIT ' . (int) $limit
            )->fetchAll();
        }
        if (!$teacherId) {
            return [];
        }
        $stmt = $db->prepare(
            'SELECT lessons.*, courses.title AS course_title, courses.code AS course_code
             FROM lessons INNER JOIN courses ON courses.id = lessons.course_id
             WHERE courses.teacher_id = :tid
               AND lessons.status IN ("planned","published","in_progress")
               AND lessons.lesson_date IS NOT NULL AND lessons.lesson_date <> ""
             ORDER BY lessons.lesson_date, lessons.start_time LIMIT ' . (int) $limit
        );
        $stmt->execute(['tid' => $teacherId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        Database::migrate();
        $stmt = Database::connection()->prepare('SELECT * FROM lessons WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function update(int $id, string $title, string $type, ?string $content, ?string $filePath, array $plan = []): void
    {
        Database::migrate();
        if (!in_array($type, self::TYPES, true)) {
            $type = 'document';
        }
        $existing = self::find($id);
        $status = in_array($plan['status'] ?? ($existing['status'] ?? 'planned'), self::STATUSES, true)
            ? ($plan['status'] ?? $existing['status']) : 'planned';
        $published = array_key_exists('is_published', $plan)
            ? ((int) $plan['is_published'] ? 1 : 0)
            : ($status === 'published' ? 1 : (int) ($existing['is_published'] ?? 1));

        // Keep existing planned values when not supplied in edit form.
        $objectives = array_key_exists('objectives', $plan) ? (trim((string) $plan['objectives']) ?: null) : ($existing['objectives'] ?? null);
        $lessonDate = array_key_exists('lesson_date', $plan) ? self::cleanDate($plan['lesson_date']) : ($existing['lesson_date'] ?? null);
        $startTime = array_key_exists('start_time', $plan) ? self::cleanTime($plan['start_time']) : ($existing['start_time'] ?? null);
        $duration = array_key_exists('duration_minutes', $plan) ? max(15, min(480, (int) $plan['duration_minutes'])) : (int) ($existing['duration_minutes'] ?? 60);
        $venue = array_key_exists('venue', $plan) ? (trim((string) $plan['venue']) ?: null) : ($existing['venue'] ?? null);
        $plannedBy = array_key_exists('planned_by', $plan) && !empty($plan['planned_by']) ? (int) $plan['planned_by'] : ($existing['planned_by'] ?? null);

        $stmt = Database::connection()->prepare(
            'UPDATE lessons SET title = :t, lesson_type = :l, content = :c, file_path = :f,
              objectives = :o, lesson_date = :d, start_time = :st, duration_minutes = :du,
              venue = :v, status = :status, is_published = :p, planned_by = :pb, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            't' => $title, 'l' => $type, 'c' => $content, 'f' => $filePath,
            'o' => $objectives, 'd' => $lessonDate, 'st' => $startTime, 'du' => $duration,
            'v' => $venue, 'status' => $status, 'p' => $published, 'pb' => $plannedBy, 'id' => $id,
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::migrate();
        if (!in_array($status, self::STATUSES, true)) {
            return;
        }
        $published = $status === 'published' ? 1 : ($status === 'planned' ? 0 : null);
        if ($published === null) {
            // Keep current published flag for in_progress/completed/cancelled, but default completed lessons to published.
            $existing = self::find($id);
            $published = (int) ($existing['is_published'] ?? 0);
            if ($status === 'completed' && !$published) {
                $published = 1;
            }
        }
        $stmt = Database::connection()->prepare(
            'UPDATE lessons SET status = :s, is_published = :p, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute(['s' => $status, 'p' => $published, 'id' => $id]);
    }

    public static function move(int $id, int $direction): void
    {
        Database::migrate();
        $current = self::find($id);
        if (!$current) {
            return;
        }
        $db = Database::connection();
        $stmt = $db->prepare(
            $direction > 0
                ? 'SELECT * FROM lessons WHERE course_id = :c AND sort_order > :s ORDER BY sort_order ASC LIMIT 1'
                : 'SELECT * FROM lessons WHERE course_id = :c AND sort_order < :s ORDER BY sort_order DESC LIMIT 1'
        );
        $stmt->execute(['c' => (int) $current['course_id'], 's' => (int) $current['sort_order']]);
        $neighbour = $stmt->fetch();
        if (!$neighbour) {
            return;
        }
        $db->beginTransaction();
        try {
            $swap = $db->prepare('UPDATE lessons SET sort_order = :s WHERE id = :id');
            $swap->execute(['s' => (int) $neighbour['sort_order'], 'id' => (int) $current['id']]);
            $swap->execute(['s' => (int) $current['sort_order'], 'id' => (int) $neighbour['id']]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
        }
    }

    public static function delete(int $id): void
    {
        Database::migrate();
        Database::connection()->exec('DELETE FROM lessons WHERE id = ' . (int) $id);
    }

    /** Courses the given user is allowed to plan lessons for. */
    public static function plannableCourses(string $role, ?int $teacherId): array
    {
        Database::migrate();
        $db = Database::connection();
        if ($role === 'admin') {
            return $db->query(
                'SELECT c.*, users.full_name AS teacher FROM courses c
                 LEFT JOIN teachers t ON t.id = c.teacher_id
                 LEFT JOIN users ON users.id = t.user_id
                 ORDER BY c.created_at DESC'
            )->fetchAll();
        }
        if ($role === 'teacher' && $teacherId) {
            $stmt = $db->prepare(
                'SELECT c.*, users.full_name AS teacher FROM courses c
                 LEFT JOIN teachers t ON t.id = c.teacher_id
                 LEFT JOIN users ON users.id = t.user_id
                 WHERE c.teacher_id = :tid ORDER BY c.created_at DESC'
            );
            $stmt->execute(['tid' => $teacherId]);
            return $stmt->fetchAll();
        }
        return [];
    }

    public static function canPlanCourse(string $role, ?int $teacherId, int $courseId): bool
    {
        Database::migrate();
        if ($role === 'admin') {
            return true;
        }
        if ($role !== 'teacher' || !$teacherId || !$courseId) {
            return false;
        }
        $stmt = Database::connection()->prepare('SELECT 1 FROM courses WHERE id = :id AND teacher_id = :tid');
        $stmt->execute(['id' => $courseId, 'tid' => $teacherId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Teaching materials previously uploaded by a lecturer — reusable as lesson content. */
    public static function uploadedMaterials(string $role, ?int $userId, int $limit = 50): array
    {
        Database::migrate();
        $db = Database::connection();
        if ($role === 'admin') {
            return $db->query(
                'SELECT files.*, users.full_name AS uploader FROM files
                 LEFT JOIN users ON users.id = files.uploaded_by
                 WHERE files.upload_context LIKE "teaching_material:%"
                 ORDER BY files.created_at DESC LIMIT ' . (int) $limit
            )->fetchAll();
        }
        if ($userId) {
            $stmt = $db->prepare(
                'SELECT files.*, users.full_name AS uploader FROM files
                 LEFT JOIN users ON users.id = files.uploaded_by
                 WHERE files.upload_context LIKE "teaching_material:%" AND files.uploaded_by = :u
                 ORDER BY files.created_at DESC LIMIT ' . (int) $limit
            );
            $stmt->execute(['u' => $userId]);
            return $stmt->fetchAll();
        }
        return [];
    }

    public static function markProgress(int $studentId, int $courseId): void
    {
        $db = Database::connection();
        $enrolled = $db->prepare('SELECT 1 FROM enrollments WHERE student_id = :s AND course_id = :c');
        $enrolled->execute(['s' => $studentId, 'c' => $courseId]);
        if (!$enrolled->fetchColumn()) return;
        $stmt = $db->prepare('SELECT COUNT(*) FROM lessons WHERE course_id = :c AND is_published = 1');
        $stmt->execute(['c' => $courseId]);
        $total = (int) $stmt->fetchColumn();
        if ($total === 0) return;
        $progress = min(100, round(($total / $total) * 100));
        $db->prepare('UPDATE enrollments SET progress_percent = :p WHERE student_id = :s AND course_id = :c')
            ->execute(['p' => $progress, 's' => $studentId, 'c' => $courseId]);
    }

    public static function createFromClasswork(int $classworkId): ?int
    {
        $cw = Database::connection()->prepare(
            'SELECT cw.*, classrooms.course_id FROM classroom_classwork cw
             INNER JOIN classrooms ON classrooms.id = cw.classroom_id
             WHERE cw.id = :id LIMIT 1'
        );
        $cw->execute(['id' => $classworkId]);
        $item = $cw->fetch();
        if (!$item) return null;
        return self::create((int) $item['course_id'], $item['title'], 'document', $item['description'], null, 0);
    }

    private static function cleanDate($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));
        return checkdate($m, $d, $y) ? $value : null;
    }

    private static function cleanTime($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return null;
        }
        return substr($value, 0, 5);
    }
}
