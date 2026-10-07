# NEAC Database

The MVP uses a local SQLite database at:

`database/neac_platform.sqlite`

Run this from the project root to create or refresh the schema:

```powershell
php database/setup.php
```

The schema includes users, students, teachers, admissions, programmes, courses, lessons, enrollments, assignments, submissions, quizzes, attempts, attendance, payments, results, certificates, notifications, discussions, files, contact messages, and audit logs.
