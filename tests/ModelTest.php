<?php

require_once('./autoload.php');

use PHPUnit\Framework\TestCase;
use Model\Post;

class ModelTest extends TestCase
{
    /**
     * @test
     */
    public function testfindById()
    {
        $posts = new Post();
        $post = $posts->findById(1);
        $this->assertIsObject($post);
    }

    /**
     * 
     * @test
     */
    public function testFindAll()
    {
        $post = new Post();
        $posts = $post->findAll();
        $this->assertIsObject($posts);
    }

    /**
     * 
     * @test
     */
    public function testComments()
    {
        $posts = new Post();
        $post = $posts->findById(1);
        $comments = $post->comments();
        $this->assertIsObject($comments);
    }
}
