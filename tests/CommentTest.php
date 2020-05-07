<?php

require_once('./autoload.php');

use PHPUnit\Framework\TestCase;
use Model\Comment;

class CommentTest extends TestCase
{
    /**
     * @test
     */
    public function testPost()
    {
        $comments = new Comment();
        $comment = $comments->findById(1);
        print_r($comment->post());
        $this->assertTrue(true);
    }
}
