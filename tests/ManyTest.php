<?php

require_once('./autoload.php');

use PHPUnit\Framework\TestCase;
use Model\Student;

class ManyTest extends TestCase
{
    /**
     * @test
     */
    public function testClasses()
    {
        $students = new Student();
        $student = $students->findById(1);
        $classes = $student->classes();
        $this->assertIsObject($classes);
    }
}
