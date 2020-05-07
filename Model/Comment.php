<?php

namespace Model;

require_once('./autoload.php');

use Ripple\Entity;

class Comment extends Entity
{
    public $id;
    public $author;
    public $content;
}
