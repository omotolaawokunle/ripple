<?php

namespace Model;

require_once('./autoload.php');

use Ripple\Entity;

class Comment extends Entity
{
    public $id;
    public $author;
    public $content;

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
