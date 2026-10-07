<?php

require_once __DIR__ . '/../core/Database.php';

class Quiz
{
    public static function create(int $courseId, string $title, ?string $instructions, float $totalMarks, ?string $opensAt, ?string $closesAt, ?int $timeLimit = null): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO quizzes (course_id, title, instructions, total_marks, opens_at, closes_at, time_limit)
              VALUES (:c, :t, :i, :m, :o, :cl, :tl)'
        );
        $stmt->execute(['c' => $courseId, 't' => $title, 'i' => $instructions, 'm' => $totalMarks, 'o' => $opensAt, 'cl' => $closesAt, 'tl' => $timeLimit]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function addQuestion(int $quizId, string $question, string $type, ?array $options, ?string $correctAnswer, float $marks, int $sortOrder): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO quiz_questions (quiz_id, question, question_type, options_json, correct_answer, marks, sort_order)
             VALUES (:q, :qu, :t, :o, :c, :m, :s)'
        );
        $stmt->execute([
            'q' => $quizId, 'qu' => $question, 't' => $type,
            'o' => $options ? json_encode($options) : null,
            'c' => $correctAnswer, 'm' => $marks, 's' => $sortOrder,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function generateAuto(int $quizId, string $topic, int $count = 5): void
    {
        $templates = [
            'multiple_choice' => [
                ['q' => 'What is the primary purpose of ' . $topic . '?', 'o' => ['To standardize processes', 'To reduce costs', 'To increase speed', 'To eliminate errors'], 'a' => 'To standardize processes'],
                ['q' => 'Which principle best applies to ' . $topic . '?', 'o' => ['Accountability', 'Efficiency', 'Transparency', 'Collaboration'], 'a' => 'Accountability'],
                ['q' => 'How does ' . $topic . ' impact organizational growth?', 'o' => ['Slows it down', 'Has no effect', 'Accelerates it', 'Only short-term'], 'a' => 'Accelerates it'],
            ],
            'true_false' => [
                ['q' => $topic . ' is essential for modern education.', 'o' => ['True', 'False'], 'a' => 'True'],
                ['q' => $topic . ' applies only to theoretical knowledge.', 'o' => ['True', 'False'], 'a' => 'False'],
            ],
            'short_answer' => [
                ['q' => 'Define ' . $topic . ' in one sentence.', 'o' => null, 'a' => ''],
                ['q' => 'List two key benefits of ' . $topic . '.', 'o' => null, 'a' => ''],
            ],
        ];
        $added = 0;
        $types = ['multiple_choice', 'true_false', 'short_answer'];
        shuffle($types);
        foreach ($types as $type) {
            $pool = $templates[$type];
            shuffle($pool);
            foreach ($pool as $item) {
                if ($added >= $count) break;
                self::addQuestion($quizId, $item['q'], $type, $item['o'], $item['a'], rand(5, 15), $added);
                $added++;
            }
            if ($added >= $count) break;
        }
    }

    public static function forCourse(int $courseId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT q.*, (SELECT COUNT(*) FROM quiz_attempts WHERE quiz_id = q.id) AS attempts
             FROM quizzes q WHERE q.course_id = :c ORDER BY q.created_at DESC'
        );
        $stmt->execute(['c' => $courseId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT quizzes.*, courses.title AS course_title
             FROM quizzes INNER JOIN courses ON courses.id = quizzes.course_id
             WHERE quizzes.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function questions(int $quizId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM quiz_questions WHERE quiz_id = :q ORDER BY sort_order'
        );
        $stmt->execute(['q' => $quizId]);
        $questions = $stmt->fetchAll();
        foreach ($questions as &$q) {
            $q['options'] = $q['options_json'] ? json_decode($q['options_json'], true) : null;
        }
        return $questions;
    }

    public static function startAttempt(int $quizId, int $studentId): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO quiz_attempts (quiz_id, student_id) VALUES (:q, :s)'
        );
        $stmt->execute(['q' => $quizId, 's' => $studentId]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function submitAttempt(int $attemptId, array $answers): float
    {
        $db = Database::connection();
        $attempt = $db->prepare('SELECT * FROM quiz_attempts WHERE id = :id');
        $attempt->execute(['id' => $attemptId]);
        $a = $attempt->fetch();
        if (!$a || $a['submitted_at']) return 0;

        $quiz = self::find((int) $a['quiz_id']);
        $questions = self::questions((int) $a['quiz_id']);
        $score = 0.0;
        $byQuestion = [];
        foreach ($questions as $q) {
            $answer = $answers['q_' . $q['id']] ?? '';
            $correct = false;
            if ($q['question_type'] === 'true_false' || $q['question_type'] === 'multiple_choice') {
                $correct = strtolower(trim($answer)) === strtolower(trim($q['correct_answer'] ?? ''));
            }
            if ($q['question_type'] === 'fill_blank') {
                $correct = self::gradeFillBlank($answer, $q['correct_answer'] ?? '');
            }
            if ($correct) $score += (float) $q['marks'];
            $byQuestion[] = ['question_id' => $q['id'], 'given' => $answer, 'correct' => $correct, 'marks_awarded' => $correct ? (float) $q['marks'] : 0, 'manually_graded' => false];
        }
        $stmt = $db->prepare('UPDATE quiz_attempts SET score = :s, answers_json = :a, submitted_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['s' => $score, 'a' => json_encode($byQuestion), 'id' => $attemptId]);
        return $score;
    }

    private static function gradeFillBlank(string $answer, string $expected): bool
    {
        $expectedParts = explode('|', $expected);
        $givenParts = explode('|', $answer);
        if (count($expectedParts) !== count($givenParts)) {
            $givenParts = array_map('trim', explode('||', $answer));
        }
        foreach ($expectedParts as $i => $exp) {
            $given = strtolower(trim($givenParts[$i] ?? ''));
            $exp = strtolower(trim($exp));
            $expAliases = explode(',', $exp);
            $matched = false;
            foreach ($expAliases as $alias) {
                if (trim($alias) === $given) { $matched = true; break; }
            }
            if (!$matched) return false;
        }
        return true;
    }

    public static function gradeManualQuestion(int $attemptId, int $questionId, ?float $score, ?string $feedback = null): bool
    {
        $db = Database::connection();
        $attempt = $db->prepare('SELECT answers_json, student_id, quiz_id FROM quiz_attempts WHERE id = :id');
        $attempt->execute(['id' => $attemptId]);
        $a = $attempt->fetch();
        if (!$a) return false;
        $answers = json_decode($a['answers_json'] ?: '[]', true);
        if (!is_array($answers)) $answers = [];
        $updated = false;
        foreach ($answers as &$ans) {
            if ((int) ($ans['question_id'] ?? 0) === $questionId) {
                $ans['marks_awarded'] = $score !== null ? (float) $score : 0;
                $ans['manually_graded'] = true;
                $ans['feedback'] = $feedback;
                $ans['correct'] = $score !== null && $score > 0;
                $updated = true;
                break;
            }
        }
        unset($ans);
        if ($updated) {
            $newScore = 0.0;
            foreach ($answers as $ans) {
                $newScore += (float) ($ans['marks_awarded'] ?? 0);
            }
            $stmt = $db->prepare('UPDATE quiz_attempts SET score = :s, answers_json = :a WHERE id = :id');
            $stmt->execute(['s' => $newScore, 'a' => json_encode($answers), 'id' => $attemptId]);
            $quiz = self::find((int) $a['quiz_id']);
            if ($quiz) {
                $gradeMap = [90=>'A', 75=>'B', 60=>'C', 50=>'D', 0=>'F'];
                $grade = 'F';
                foreach ($gradeMap as $min => $g) { if ($newScore >= $min * $quiz['total_marks'] / 100) { $grade = $g; break; } }
                $db->prepare('DELETE FROM results WHERE student_id = :s AND course_id = :c AND assessment_type = :q')->execute([
                    's' => (int) $a['student_id'], 'c' => (int) $quiz['course_id'], 'q' => 'quiz:' . $quiz['id'],
                ]);
                $resultStmt = $db->prepare(
                    'INSERT INTO results (student_id, course_id, assessment_type, score, grade, remarks, published_at)
                     VALUES (:s, :c, :q, :sc, :g, :r, CURRENT_TIMESTAMP)'
                );
                $resultStmt->execute([
                    's' => (int) $a['student_id'], 'c' => (int) $quiz['course_id'],
                    'q' => 'quiz:' . $quiz['id'], 'sc' => $newScore, 'g' => $grade, 'r' => 'Partially manually graded',
                ]);
            }
            return true;
        }
        return false;
    }

    public static function attemptDetails(int $attemptId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT a.*, q.title AS quiz_title, q.total_marks, q.instructions,
             c.id AS course_id, c.title AS course_title, u.full_name AS student_name, students.student_number
             FROM quiz_attempts a
             INNER JOIN quizzes q ON q.id = a.quiz_id
             INNER JOIN courses c ON c.id = q.course_id
             INNER JOIN students ON students.id = a.student_id
             INNER JOIN users u ON u.id = students.user_id
             WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $attemptId]);
        return $stmt->fetch() ?: null;
    }

    public static function attemptWithQuestions(int $attemptId): ?array
    {
        $attempt = self::attemptDetails($attemptId);
        if (!$attempt) return null;
        $questions = self::questions((int) $attempt['quiz_id']);
        $answers = json_decode($attempt['answers_json'] ?: '[]', true);
        if (!is_array($answers)) $answers = [];
        $answerByQuestion = [];
        foreach ($answers as $a) {
            $answerByQuestion[(int) ($a['question_id'] ?? 0)] = $a;
        }
        return [
            'attempt' => $attempt,
            'questions' => $questions,
            'answers' => $answerByQuestion,
        ];
    }

    public static function saveManualGrades(int $attemptId, array $grades): void
    {
        $db = Database::connection();
        $attempt = $db->prepare('SELECT answers_json, quiz_id, student_id FROM quiz_attempts WHERE id = :id');
        $attempt->execute(['id' => $attemptId]);
        $a = $attempt->fetch();
        if (!$a) return;
        $answers = json_decode($a['answers_json'] ?: '[]', true);
        if (!is_array($answers)) return;
        $quiz = self::find((int) $a['quiz_id']);
        $questions = self::questions((int) $a['quiz_id']);
        $questionMarks = [];
        foreach ($questions as $q) {
            $questionMarks[(int) $q['id']] = (float) $q['marks'];
        }
        foreach ($answers as &$ans) {
            $qid = (int) ($ans['question_id'] ?? 0);
            if (isset($grades[$qid])) {
                $ans['marks_awarded'] = (float) $grades[$qid]['score'];
                $ans['manually_graded'] = true;
                $ans['feedback'] = $grades[$qid]['feedback'] ?? null;
                $ans['correct'] = (float) $grades[$qid]['score'] > 0;
            }
        }
        unset($ans);
        $newScore = 0.0;
        foreach ($answers as $ans) {
            $newScore += (float) ($ans['marks_awarded'] ?? 0);
        }
        $stmt = $db->prepare('UPDATE quiz_attempts SET score = :s, answers_json = :a WHERE id = :id');
        $stmt->execute(['s' => $newScore, 'a' => json_encode($answers), 'id' => $attemptId]);
        if ($quiz) {
            $gradeMap = [90=>'A', 75=>'B', 60=>'C', 50=>'D', 0=>'F'];
            $grade = 'F';
            $percentage = $quiz['total_marks'] > 0 ? $newScore / $quiz['total_marks'] * 100 : 0;
            foreach ($gradeMap as $min => $g) { if ($percentage >= $min) { $grade = $g; break; } }
            $db->prepare('DELETE FROM results WHERE student_id = :s AND course_id = :c AND assessment_type = :q')->execute([
                's' => (int) $a['student_id'], 'c' => (int) $quiz['course_id'], 'q' => 'quiz:' . $quiz['id'],
            ]);
            $resultStmt = $db->prepare(
                'INSERT INTO results (student_id, course_id, assessment_type, score, grade, remarks, published_at)
                 VALUES (:s, :c, :q, :sc, :g, :r, CURRENT_TIMESTAMP)'
            );
            $resultStmt->execute([
                's' => (int) $a['student_id'], 'c' => (int) $quiz['course_id'],
                'q' => 'quiz:' . $quiz['id'], 'sc' => $newScore, 'g' => $grade, 'r' => 'Manually graded',
            ]);
        }
    }

    public static function attempts(int $quizId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT attempts.*, users.full_name AS student_name, students.student_number
             FROM quiz_attempts attempts
             INNER JOIN students ON students.id = attempts.student_id
             INNER JOIN users ON users.id = students.user_id
             WHERE attempts.quiz_id = :q ORDER BY attempts.submitted_at DESC'
        );
        $stmt->execute(['q' => $quizId]);
        return $stmt->fetchAll();
    }

    public static function studentAttempts(int $studentId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT attempts.*, quizzes.title AS quiz_title, quizzes.total_marks,
                    courses.title AS course_title, courses.id AS course_id
             FROM quiz_attempts attempts
             INNER JOIN quizzes ON quizzes.id = attempts.quiz_id
             INNER JOIN courses ON courses.id = quizzes.course_id
             WHERE attempts.student_id = :s ORDER BY attempts.submitted_at DESC'
        );
        $stmt->execute(['s' => $studentId]);
        return $stmt->fetchAll();
    }
}
