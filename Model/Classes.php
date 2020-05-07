<?php

namespace Model;

require_once('./autoload.php');

use Ripple\Entity;

class Classes extends Entity
{
    protected $table = 'classes';
    public $id, $name;
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_class', 'class_id', 'student_id');
    }
}
