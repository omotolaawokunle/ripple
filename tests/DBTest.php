<?php

require_once('./autoload.php');

use Model\Classes;
use PHPUnit\Framework\TestCase;
use Ripple\Morpher;
use Ripple\QueryBuilder\DB;

class DBTest extends TestCase
{
    /**
     * @test
     */
    public function testDB()
    {
        $classes = Classes::all();
        $morpher = new Morpher();
        $class = $morpher(Classes::class, [
            'id' => 1, 'name' => 'Geography'
        ]);
        print_r($class->students());
        $this->assertTrue(true);
    }
}
