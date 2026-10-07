<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Security.php';

class Auth
{
    public static function start(): void
    {
        Security::sessionStart();
    }

    public static function regenerate(): void
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function setRole(string $role, string $name = '', ?int $userId = null): void
    {
        self::start();
        $allowed = ['admin', 'teacher', 'student'];
        $_SESSION['role'] = in_array($role, $allowed, true) ? $role : 'student';
        $_SESSION['name'] = $name !== '' ? $name : ($_SESSION['name'] ?? 'NEAC Portal');
        if ($userId !== null) {
            $_SESSION['user_id'] = $userId;
        }
    }

    public static function login(string $role, bool $allowAdmin = false): void
    {
        self::start();
        $allowed = $allowAdmin ? ['admin', 'teacher', 'student'] : ['teacher', 'student'];
        $_SESSION['role'] = in_array($role, $allowed, true) ? $role : 'student';
        $_SESSION['name'] = match ($_SESSION['role']) {
            'admin' => 'NEAC Control Desk',
            'teacher' => 'Instructor Portal',
            default => 'Student Portal',
        };
    }

    public static function loginAdmin(string $code): bool
    {
        self::start();
        $expected = ADMIN_ACCESS_CODE;
        if ($expected === '') {
            error_log('Admin access code is not configured. Set the NEAC_ADMIN_CODE environment variable.');
            return false;
        }
        $identifier = 'admin:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (Security::loginThrottle($identifier, 5, 900)) {
            return false;
        }
        if (self::ipThrottled(10, 900)) {
            return false;
        }
        if (!is_string($code) || !hash_equals($expected, trim($code))) {
            Security::recordLoginAttempt($identifier, false);
            return false;
        }
        Security::recordLoginAttempt($identifier, true);
        self::regenerate();

        Database::migrate();
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM users WHERE role = "admin" LIMIT 1');
        $stmt->execute();
        $adminId = $stmt->fetchColumn();
        if ($adminId === false) {
            $stmt = $db->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, status)
                 VALUES (:full_name, :email, :password_hash, "admin", "active")'
            );
            $stmt->execute([
                'full_name' => 'NEAC Administrator',
                'email' => 'admin@neac-bb.org',
                'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            ]);
            $adminId = (int) $db->lastInsertId();
        } else {
            $adminId = (int) $adminId;
        }

        $_SESSION['user_id'] = $adminId;
        $_SESSION['role'] = 'admin';
        $_SESSION['name'] = 'NEAC Control Desk';
        $_SESSION['admin_gate'] = true;
        return true;
    }

    public static function registerStudent(array $student): void
    {
        self::start();
        Database::migrate();

        $db = Database::connection();
        $fullName = trim($student['full_name'] ?? '') ?: 'Student Portal';
        $email = strtolower(trim($student['email'] ?? ''));
        $phone = trim($student['phone'] ?? '');
        $program = trim($student['program'] ?? 'Training for Trainers Program');
        $password = (string) ($student['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please enter a valid email address.');
        }
        if (!Security::isStrongPassword($password)) {
            throw new InvalidArgumentException('Password must be at least 8 characters and include uppercase, lowercase, and a number.');
        }
        $programId = self::programId($program);

        $db->beginTransaction();

        try {
            $user = $db->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, status)
                 VALUES (:full_name, :email, :password_hash, "student", "active")'
            );
            $user->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $userId = (int) $db->lastInsertId();
            $studentRecord = $db->prepare(
                'INSERT INTO students (user_id, program_id, phone, admission_status)
                 VALUES (:user_id, :program_id, :phone, "admitted")'
            );
            $studentRecord->execute([
                'user_id' => $userId,
                'program_id' => $programId,
                'phone' => $phone,
            ]);

            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }

        self::regenerate();
        $db2 = Database::connection();
        $sidStmt = $db2->prepare('SELECT id FROM students WHERE user_id = :uid LIMIT 1');
        $sidStmt->execute(['uid' => $userId]);
        $actualStudentId = (int) $sidStmt->fetchColumn();
        $_SESSION['user_id'] = $userId;
        $_SESSION['student_id'] = $actualStudentId;
        $_SESSION['role'] = 'student';
        $_SESSION['name'] = $fullName;
        $_SESSION['student_profile'] = [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'program' => $program,
        ];
    }

    public static function loginStudent(string $email, string $password): bool
    {
        self::start();
        Database::migrate();

        $email = strtolower(trim($email));
        $identifier = 'student:' . $email;
        if (Security::loginThrottle($identifier)) {
            return false;
        }
        if (self::ipThrottled()) {
            return false;
        }

        $statement = Database::connection()->prepare(
            'SELECT users.*, programs.name AS program_name, students.phone, students.id AS student_id
             FROM users
             LEFT JOIN students ON students.user_id = users.id
             LEFT JOIN programs ON programs.id = students.program_id
             WHERE users.email = :email AND users.role = "student" AND users.status = "active"
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Security::recordLoginAttempt($identifier, false);
            return false;
        }

        Security::recordLoginAttempt($identifier, true);
        self::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['student_id'] = (int) ($user['student_id'] ?? 0);
        $_SESSION['role'] = 'student';
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['student_profile'] = [
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
            'program' => $user['program_name'] ?? '',
        ];
        return true;
    }

    public static function loginTeacher(string $email, string $password): bool
    {
        self::start();
        Database::migrate();

        $email = strtolower(trim($email));
        $identifier = 'teacher:' . $email;
        if (Security::loginThrottle($identifier)) {
            return false;
        }
        if (self::ipThrottled()) {
            return false;
        }

        $statement = Database::connection()->prepare(
            'SELECT users.*, teachers.title, teachers.phone, teachers.speciality, teachers.id AS teacher_id
             FROM users
             INNER JOIN teachers ON teachers.user_id = users.id
             WHERE users.email = :email AND users.role = "teacher" AND users.status = "active"
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Security::recordLoginAttempt($identifier, false);
            return false;
        }

        Security::recordLoginAttempt($identifier, true);
        self::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['teacher_id'] = (int) ($user['teacher_id'] ?? 0);
        $_SESSION['role'] = 'teacher';
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['teacher_profile'] = [
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'title' => $user['title'] ?? '',
            'phone' => $user['phone'] ?? '',
            'speciality' => $user['speciality'] ?? '',
        ];
        return true;
    }

    public static function createTeacher(array $teacher): int
    {
        Database::migrate();

        $db = Database::connection();
        $fullName = trim($teacher['full_name'] ?? '');
        $email = strtolower(trim($teacher['email'] ?? ''));
        $password = (string) ($teacher['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }
        if (!Security::isStrongPassword($password)) {
            throw new InvalidArgumentException('Password must be at least 8 characters and include uppercase, lowercase, and a number.');
        }

        $db->beginTransaction();

        try {
            $user = $db->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, status)
                 VALUES (:full_name, :email, :password_hash, "teacher", "active")'
            );
            $user->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $userId = (int) $db->lastInsertId();
            $record = $db->prepare(
                'INSERT INTO teachers (user_id, title, phone, speciality, bio)
                 VALUES (:user_id, :title, :phone, :speciality, :bio)'
            );
            $record->execute([
                'user_id' => $userId,
                'title' => trim($teacher['title'] ?? ''),
                'phone' => trim($teacher['phone'] ?? ''),
                'speciality' => trim($teacher['speciality'] ?? ''),
                'bio' => trim($teacher['bio'] ?? ''),
            ]);

            $teacherId = (int) $db->lastInsertId();
            $db->commit();
            return $teacherId;
        } catch (Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    private static function programId(string $program): int
    {
        $statement = Database::connection()->prepare('SELECT id FROM programs WHERE name = :name LIMIT 1');
        $statement->execute(['name' => $program]);
        $programId = $statement->fetchColumn();

        if ($programId === false) {
            $fallback = Database::connection()->query('SELECT id FROM programs ORDER BY id LIMIT 1')->fetchColumn();
            return (int) $fallback;
        }

        return (int) $programId;
    }

    private static function ipThrottled(int $maxAttempts = 12, int $windowSeconds = 900): bool
    {
        return Security::loginThrottle('ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), $maxAttempts, $windowSeconds);
    }

    public static function role(): string
    {
        self::start();
        return $_SESSION['role'] ?? 'guest';
    }

    public static function isAdmin(): bool
    {
        self::start();
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    public static function check(): bool
    {
        self::start();
        return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'teacher', 'student'], true);
    }

    public static function userId(): ?int
    {
        self::start();
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function teacherId(): ?int
    {
        self::start();
        return isset($_SESSION['teacher_id']) ? (int) $_SESSION['teacher_id'] : null;
    }

    public static function studentId(): ?int
    {
        self::start();
        return isset($_SESSION['student_id']) ? (int) $_SESSION['student_id'] : null;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
