<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Security.php';

class Classroom
{
    public static function createForCourse(int $courseId, int $teacherId = 0): int
    {
        Database::migrate();
        $code = self::generateUniqueCode();
        $stmt = Database::connection()->prepare(
            'INSERT INTO classrooms (course_id, code) VALUES (:cid, :code)'
        );
        $stmt->execute(['cid' => $courseId, 'code' => $code]);
        $classroomId = (int) Database::connection()->lastInsertId();
        if ($teacherId > 0) {
            $teacher = Database::connection()->prepare('SELECT user_id FROM teachers WHERE id = :id');
            $teacher->execute(['id' => $teacherId]);
            $userId = (int) ($teacher->fetchColumn() ?: 0);
            if ($userId > 0) {
                self::addMember($classroomId, $userId, 'teacher');
            }
        }
        return $classroomId;
    }

    public static function generateUniqueCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $code = Security::randomCode(7);
            $stmt = Database::connection()->prepare('SELECT 1 FROM classrooms WHERE code = :c');
            $stmt->execute(['c' => $code]);
            if (!$stmt->fetchColumn()) {
                return $code;
            }
        }
        return Security::randomCode(9);
    }

    public static function addMember(int $classroomId, int $userId, string $role): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO classroom_members (classroom_id, user_id, role, status)
             VALUES (:cid, :uid, :role, "active")
             ON CONFLICT(classroom_id, user_id) DO UPDATE SET role = excluded.role, status = "active"'
        );
        $stmt->execute(['cid' => $classroomId, 'uid' => $userId, 'role' => $role]);
        if ($role === 'student') {
            $course = Database::connection()->prepare('SELECT course_id FROM classrooms WHERE id = :c LIMIT 1');
            $course->execute(['c' => $classroomId]);
            $courseId = (int) ($course->fetchColumn() ?: 0);
            $student = Database::connection()->prepare('SELECT id FROM students WHERE user_id = :u LIMIT 1');
            $student->execute(['u' => $userId]);
            $studentId = (int) ($student->fetchColumn() ?: 0);
            if ($courseId > 0 && $studentId > 0) {
                $enroll = Database::connection()->prepare(
                    'INSERT INTO enrollments (student_id, course_id, status)
                     VALUES (:s, :c, "active")
                     ON CONFLICT(student_id, course_id) DO UPDATE SET status = "active"'
                );
                $enroll->execute(['s' => $studentId, 'c' => $courseId]);
            }
        }
        return true;
    }

    public static function removeMember(int $classroomId, int $userId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE classroom_members SET status = "removed" WHERE classroom_id = :cid AND user_id = :uid'
        );
        $stmt->execute(['cid' => $classroomId, 'uid' => $userId]);
        $course = Database::connection()->prepare('SELECT course_id FROM classrooms WHERE id = :c LIMIT 1');
        $course->execute(['c' => $classroomId]);
        $courseId = (int) ($course->fetchColumn() ?: 0);
        $student = Database::connection()->prepare('SELECT id FROM students WHERE user_id = :u LIMIT 1');
        $student->execute(['u' => $userId]);
        $studentId = (int) ($student->fetchColumn() ?: 0);
        if ($courseId > 0 && $studentId > 0) {
            $enroll = Database::connection()->prepare(
                'UPDATE enrollments SET status = "withdrawn" WHERE student_id = :s AND course_id = :c'
            );
            $enroll->execute(['s' => $studentId, 'c' => $courseId]);
        }
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT classrooms.*, courses.title, courses.code AS course_code, programs.name AS program_name
             FROM classrooms
             INNER JOIN courses ON courses.id = classrooms.course_id
             INNER JOIN programs ON programs.id = courses.program_id
             WHERE classrooms.code = :c
             LIMIT 1'
        );
        $stmt->execute(['c' => strtoupper(trim($code))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT classrooms.*, courses.title, courses.code AS course_code, courses.description,
                    programs.name AS program_name, users.full_name AS teacher_name
             FROM classrooms
             INNER JOIN courses ON courses.id = classrooms.course_id
             INNER JOIN programs ON programs.id = courses.program_id
             LEFT JOIN teachers ON teachers.id = courses.teacher_id
             LEFT JOIN users ON users.id = teachers.user_id
             WHERE classrooms.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function forUser(int $userId, string $role): array
    {
        if ($role === 'admin') {
            return Database::connection()
                ->query(
                    'SELECT classrooms.id, classrooms.code, classrooms.banner_color, classrooms.section_label,
                            classrooms.archived_at, courses.title, courses.code AS course_code,
                            programs.name AS program_name,
                            (SELECT COUNT(*) FROM classroom_members WHERE classroom_id = classrooms.id AND status = "active") AS member_count
                     FROM classrooms
                     INNER JOIN courses ON courses.id = classrooms.course_id
                     INNER JOIN programs ON programs.id = courses.program_id
                     ORDER BY classrooms.created_at DESC'
                )
                ->fetchAll();
        }
        $stmt = Database::connection()->prepare(
            'SELECT classrooms.id, classrooms.code, classrooms.banner_color, classrooms.section_label,
                    classrooms.archived_at, courses.title, courses.code AS course_code,
                    programs.name AS program_name,
                    classroom_members.role AS member_role,
                    (SELECT COUNT(*) FROM classroom_members WHERE classroom_id = classrooms.id AND status = "active") AS member_count
             FROM classroom_members
             INNER JOIN classrooms ON classrooms.id = classroom_members.classroom_id
             INNER JOIN courses ON courses.id = classrooms.course_id
             INNER JOIN programs ON programs.id = courses.program_id
             WHERE classroom_members.user_id = :uid AND classroom_members.status = "active"
             ORDER BY classrooms.created_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public static function isMember(int $classroomId, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM classroom_members WHERE classroom_id = :c AND user_id = :u AND status = "active" LIMIT 1'
        );
        $stmt->execute(['c' => $classroomId, 'u' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function isTeacher(int $classroomId, int $userId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM classroom_members WHERE classroom_id = :c AND user_id = :u AND role IN ("teacher", "co_teacher") AND status = "active" LIMIT 1'
        );
        $stmt->execute(['c' => $classroomId, 'u' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function members(int $classroomId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT users.id, users.full_name, users.email, users.status,
                    classroom_members.role, classroom_members.joined_at,
                    students.admission_status, students.student_number,
                    teachers.title, teachers.speciality
             FROM classroom_members
             INNER JOIN users ON users.id = classroom_members.user_id
             LEFT JOIN students ON students.user_id = users.id
             LEFT JOIN teachers ON teachers.user_id = users.id
             WHERE classroom_members.classroom_id = :c AND classroom_members.status = "active"
             ORDER BY classroom_members.role DESC, users.full_name'
        );
        $stmt->execute(['c' => $classroomId]);
        return $stmt->fetchAll();
    }

    public static function stream(int $classroomId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT announcements.id, announcements.body, announcements.created_at,
                    users.full_name AS author_name, "announcement" AS type
             FROM classroom_announcements announcements
             INNER JOIN users ON users.id = announcements.author_id
             WHERE announcements.classroom_id = :c
             ORDER BY announcements.created_at DESC
             LIMIT 30'
        );
        $stmt->execute(['c' => $classroomId]);
        return $stmt->fetchAll();
    }

    public static function postAnnouncement(int $classroomId, int $authorId, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO classroom_announcements (classroom_id, author_id, body) VALUES (:c, :a, :b)'
        );
        $stmt->execute(['c' => $classroomId, 'a' => $authorId, 'b' => $body]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function classwork(int $classroomId, ?string $type = null): array
    {
        $sql =
            'SELECT classwork.*, users.full_name AS author_name,
                    (SELECT COUNT(*) FROM classroom_submissions WHERE classwork_id = classwork.id) AS submission_count
             FROM classroom_classwork classwork
             INNER JOIN users ON users.id = classwork.author_id
             WHERE classwork.classroom_id = :c';
        $params = ['c' => $classroomId];
        if ($type !== null) {
            $sql .= ' AND classwork.work_type = :t';
            $params['t'] = $type;
        }
        $sql .= ' ORDER BY classwork.created_at DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findClasswork(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT classwork.*, classrooms.code AS classroom_code, courses.title AS course_title,
                    users.full_name AS author_name
             FROM classroom_classwork classwork
             INNER JOIN classrooms ON classrooms.id = classwork.classroom_id
             INNER JOIN courses ON courses.id = classrooms.course_id
             INNER JOIN users ON users.id = classwork.author_id
             WHERE classwork.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function attachments(int $classworkId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT attachments.*, files.original_name, files.stored_path, files.file_size, files.mime_type
             FROM classroom_classwork_attachments attachments
             LEFT JOIN files ON files.id = attachments.file_id
             WHERE attachments.classwork_id = :c
             ORDER BY attachments.sort_order'
        );
        $stmt->execute(['c' => $classworkId]);
        return $stmt->fetchAll();
    }

    public static function createClasswork(
        int $classroomId,
        int $authorId,
        string $type,
        string $title,
        string $description,
        ?string $instructions,
        ?string $dueAt,
        float $maxScore
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO classroom_classwork (classroom_id, author_id, work_type, title, description, instructions, due_at, max_score)
             VALUES (:c, :a, :t, :title, :d, :i, :due, :m)'
        );
        $stmt->execute([
            'c' => $classroomId, 'a' => $authorId, 't' => $type, 'title' => $title,
            'd' => $description, 'i' => $instructions, 'due' => $dueAt, 'm' => $maxScore,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function createRubric(int $classworkId, string $title, array $criteria): int
    {
        $db = Database::connection();
        $stmt = $db->prepare('INSERT INTO classroom_rubrics (classwork_id, title) VALUES (:c, :t)');
        $stmt->execute(['c' => $classworkId, 't' => $title]);
        $rubricId = (int) $db->lastInsertId();
        $cStmt = $db->prepare(
            'INSERT INTO classroom_rubric_criteria (rubric_id, label, description, max_points, sort_order)
             VALUES (:r, :l, :d, :m, :s)'
        );
        $i = 0;
        foreach ($criteria as $criterion) {
            $cStmt->execute([
                'r' => $rubricId,
                'l' => $criterion['label'],
                'd' => $criterion['description'],
                'm' => (float) $criterion['max_points'],
                's' => $i++,
            ]);
        }
        return $rubricId;
    }

    public static function rubricForClasswork(int $classworkId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM classroom_rubrics WHERE classwork_id = :c LIMIT 1');
        $stmt->execute(['c' => $classworkId]);
        $rubric = $stmt->fetch();
        if (!$rubric) {
            return null;
        }
        $cStmt = Database::connection()->prepare(
            'SELECT * FROM classroom_rubric_criteria WHERE rubric_id = :r ORDER BY sort_order'
        );
        $cStmt->execute(['r' => $rubric['id']]);
        $rubric['criteria'] = $cStmt->fetchAll();
        return $rubric;
    }

    public static function submissionsForClasswork(int $classworkId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT submissions.*, users.full_name AS student_name, students.student_number,
                    files.original_name, files.stored_path, files.mime_type, files.file_size,
                    (SELECT COALESCE(SUM(points), 0) FROM classroom_grades WHERE submission_id = submissions.id) AS total_points
             FROM classroom_submissions submissions
             INNER JOIN students ON students.id = submissions.student_id
             INNER JOIN users ON users.id = students.user_id
             LEFT JOIN files ON files.id = submissions.file_id
             WHERE submissions.classwork_id = :c
             ORDER BY submissions.submitted_at DESC'
        );
        $stmt->execute(['c' => $classworkId]);
        return $stmt->fetchAll();
    }

    public static function findSubmission(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT submissions.*, users.full_name AS student_name, students.student_number,
                    files.original_name, files.stored_path, files.mime_type, files.file_size,
                    classroom_classwork.title AS classwork_title, classroom_classwork.max_score,
                    classroom_classwork.classroom_id
             FROM classroom_submissions submissions
             INNER JOIN students ON students.id = submissions.student_id
             INNER JOIN users ON users.id = students.user_id
             LEFT JOIN files ON files.id = submissions.file_id
             INNER JOIN classroom_classwork ON classroom_classwork.id = submissions.classwork_id
             WHERE submissions.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function gradesForSubmission(int $submissionId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT grades.*, criteria.label, criteria.max_points
             FROM classroom_grades grades
             INNER JOIN classroom_rubric_criteria criteria ON criteria.id = grades.criterion_id
             WHERE grades.submission_id = :s'
        );
        $stmt->execute(['s' => $submissionId]);
        return $stmt->fetchAll();
    }

    public static function saveGrade(int $submissionId, int $criterionId, int $graderId, float $points, ?string $comment): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO classroom_grades (submission_id, criterion_id, points, comment, graded_by)
             VALUES (:s, :c, :p, :cm, :g)
             ON CONFLICT(submission_id, criterion_id) DO UPDATE SET
                points = excluded.points,
                comment = excluded.comment,
                graded_by = excluded.graded_by,
                graded_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            's' => $submissionId, 'c' => $criterionId, 'p' => $points, 'cm' => $comment, 'g' => $graderId,
        ]);
        Database::connection()->exec(
            'UPDATE classroom_submissions SET status = "graded" WHERE id = ' . (int) $submissionId
        );
    }

    public static function studentSubmission(int $classworkId, int $studentId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT submissions.*, files.original_name, files.stored_path, files.mime_type, files.file_size
             FROM classroom_submissions submissions
             LEFT JOIN files ON files.id = submissions.file_id
             WHERE submissions.classwork_id = :c AND submissions.student_id = :s LIMIT 1'
        );
        $stmt->execute(['c' => $classworkId, 's' => $studentId]);
        return $stmt->fetch() ?: null;
    }

    public static function findUserByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, full_name, email, role FROM users WHERE email = :e LIMIT 1');
        $stmt->execute(['e' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function streamActivity(int $classroomId, int $limit = 30): array
    {
        $db = Database::connection();
        $items = [];
        $announcements = $db->prepare(
            "SELECT a.id, a.body AS body, a.created_at, u.full_name AS author_name, 'announcement' AS type
             FROM classroom_announcements a INNER JOIN users u ON u.id = a.author_id
             WHERE a.classroom_id = :c ORDER BY a.created_at DESC LIMIT :l"
        );
        $announcements->execute(['c' => $classroomId, 'l' => $limit]);
        $items = array_merge($items, $announcements->fetchAll());

        $classworkItems = $db->prepare(
            "SELECT cw.id, CONCAT(cw.title, ' (', cw.work_type, ')') AS body, cw.created_at, u.full_name AS author_name, 'classwork' AS type
             FROM classroom_classwork cw INNER JOIN users u ON u.id = cw.author_id
             WHERE cw.classroom_id = :c ORDER BY cw.created_at DESC LIMIT :l"
        );
        $classworkItems->execute(['c' => $classroomId, 'l' => $limit]);
        $items = array_merge($items, $classworkItems->fetchAll());

        usort($items, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return array_slice($items, 0, $limit);
    }

    public static function courseId(int $classroomId): ?int
    {
        $stmt = Database::connection()->prepare('SELECT course_id FROM classrooms WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $classroomId]);
        $v = $stmt->fetchColumn();
        return $v ? (int) $v : null;
    }

    public static function saveOverallGrade(int $submissionId, ?string $feedback): void
    {
        if ($feedback !== null) {
            $stmt = Database::connection()->prepare(
                'INSERT INTO classroom_submission_comments (submission_id, author_id, body)
                 VALUES (:s, 0, :b)'
            );
            $stmt->execute(['s' => $submissionId, 'b' => $feedback]);
        }
    }
}
