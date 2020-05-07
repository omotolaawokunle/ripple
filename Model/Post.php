<?php

namespace Model;

require_once('./autoload.php');

use Ripple\Entity;

class Post extends Entity
{
    public $id;
    public $title;
    public $content;

    public function getPostById()
    {
        $post = $this->findById(1);
        //print_r($post);
        return true;
    }

    public function saveToDB()
    {
        $this->title = 'Lorem Ipsum';
        $this->content = 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Recusandae voluptatibus fuga ullam nesciunt expedita assumenda nam, ratione itaque debitis rem, repellat sint culpa velit. Modi recusandae praesentium corporis facilis inventore quas deserunt quo consequatur excepturi, repellat magni sed nesciunt mollitia non ipsam tenetur quod odio adipisci qui ut maiores aspernatur.';
        $saved = $this->save();

        return $saved;
    }

    public function update($id)
    {
        $this->id = $id;
        echo $this->id;
        $this->title = 'Lorem Ipsum';
        $this->content = 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Recusandae voluptatibus fuga ullam nesciunt expedita assumenda nam, ratione itaque debitis rem, repellat sint culpa velit. Modi recusandae praesentium corporis facilis inventore quas deserunt quo consequatur excepturi, repellat magni sed nesciunt mollitia non ipsam tenetur quod odio adipisci qui ut maiores aspernatur.';
        $saved = $this->save();

        return $saved;
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
