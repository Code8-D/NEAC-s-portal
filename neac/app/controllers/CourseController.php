<?php

require_once __DIR__ . '/../models/Course.php';

class CourseController
{
    public function index(): array
    {
        return Course::all();
    }
}
