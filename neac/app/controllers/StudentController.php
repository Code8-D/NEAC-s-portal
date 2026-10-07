<?php

require_once __DIR__ . '/../models/Student.php';

class StudentController
{
    public function index(): array
    {
        return Student::all();
    }
}
