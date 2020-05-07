<?php

require_once('./autoload.php');

use PHPUnit\Framework\TestCase;
use Model\Comment;

class CommentTest extends TestCase
{
    /**
     * @test
     */
    public function testSave()
    {
        $post = new Comment();
        $post->post_id = 1;
        $post->author = "Tola Blaze";
        $post->content = "Lorem ipsum dolor sit amet consectetur adipisicing elit. Recusandae voluptatibus fuga ullam nesciunt expedita assumenda nam";
        $saved = $post->save();
        $this->assertTrue($saved);
    }
}
