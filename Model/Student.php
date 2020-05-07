<?php

namespace Model;

require_once('./autoload.php');

use Ripple\Entity;

class Student extends Entity
{
    public $id, $name;
    public function classes()
    {
        return $this->belongsToMany(Classes::class, 'student_class', 'student_id', 'class_id');
    }
}
