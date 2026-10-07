<?php

require_once __DIR__ . '/../core/Database.php';

class Student
{
    public static function all(): array
    {
        Database::migrate();

        return Database::connection()
            ->query(
                'SELECT users.full_name AS name,
                        COALESCE(students.student_number, printf("NEAC/%04d", students.id)) AS id,
                        programs.name AS program,
                        COALESCE(
                            (SELECT ROUND(
                                CAST(SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) AS REAL) /
                                CAST(COUNT(*) AS REAL) * 100
                            ) FROM attendance WHERE student_id = students.id),
                            0
                        ) || "%" AS attendance,
                        COALESCE(MAX(payments.payment_status), "Pending") AS fees
                 FROM students
                 INNER JOIN users ON users.id = students.user_id
                 INNER JOIN programs ON programs.id = students.program_id
                 LEFT JOIN payments ON payments.student_id = students.id
                 GROUP BY students.id
                 ORDER BY students.id DESC'
            )
            ->fetchAll();
    }
}
