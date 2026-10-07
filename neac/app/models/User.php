<?php

class User
{
    public static function roles(): array
    {
        return [
            'admin' => 'Super Admin',
            'teacher' => 'Teacher / Instructor',
            'student' => 'Student',
        ];
    }
}
