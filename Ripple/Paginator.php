<?php

namespace Ripple;



class Paginator
{
    public $total = 0;
    private $per_page, $current_page, $last_page, $first_item, $last_item, $url_type;
    private $simplePaginate = false;
    protected $items;

    public function __construct($per_page, $url_type, $simplePaginate = false)
    {
        $this->per_page = $per_page;
        $this->url_type = $url_type;
        $this->simplePaginate = $simplePaginate;
        $this->set_instance();
    }

    public function get_start()
    {
        return ($this->current_page * $this->per_page) - $this->per_page;
    }


    private function set_instance()
    {
        if ($this->url_type == "GET") {
            $this->current_page = (int) (!isset($_GET['page']) ? 1 : $_GET['page']);
            $this->current_page = ($this->current_page == 0 ? 1 : $this->current_page);
        } else {
            $url = explode('page/', $_SERVER['REQUEST_URI']);
            $this->current_page = (int) (isset($url[1])) ? $url[1] : 1;
        }
    }

    public function get_limit()
    {
        return "LIMIT " . $this->get_start() . ",$this->per_page";
    }

    public function getOffset()
    {
        return $this->get_start();
    }

    public function set_total($total)
    {
        $this->total = $total;
    }

    public function links()
    {
        $adjacents = 2;
        $prev = $this->current_page - 1;
        $next = $this->current_page + 1;
        $lastpage = ceil($this->total / $this->per_page);
        $lpm1 = $lastpage - 1;
        $url = $this->url_type == 'GET' ? getFullUrl() . "?page=" : getFullUrl() . "/page/";
        $html = "";
        if ($lastpage > 1) {
            $html .= "<ul class='pagination'>";
            if ($this->current_page > 1) {
                $html .= "<li><a href='$url$prev'><i class='icon ion-md-arrow-round-back'></i></a></li>";
            } else {
                $html .= "<li class='disabled'><a><i class='icon ion-md-arrow-round-back'></i></a></li>";
            }
            if (!$this->simplePaginate) {
                if ($lastpage < 7 + ($adjacents * 2)) {
                    for ($counter = 1; $counter <= $lastpage; $counter++) {
                        if ($counter == $this->current_page) {
                            $html .= "<li class='active'><a>$counter</a></li>";
                        } else {
                            $html .= "<li><a href='$url$counter'>$counter</a></li>";
                            //$html .= "<li><a href='$url" . $counter . "'>$counter</a></li>";
                        }
                    }
                } elseif ($lastpage > 5 + ($adjacents * 2)) {
                    if ($this->current_page < 1 + ($adjacents * 2)) {
                        for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++) {
                            if ($counter == $this->current_page) {
                                $html .= "<li class='active'><a>$counter</a></li>";
                            } else {
                                $html .= "<li><a href='$url$counter'>$counter</a></li>";
                            }
                        }
                        $html .= "...";
                        $html .= "<li><a href='" . $url . $lpm1 . "'>$lpm1</a></li>";
                        $html .= "<li><a href='" . $url . $lastpage . "'>$lastpage</a></li>";
                    } elseif ($lastpage - ($adjacents * 2) > $this->current_page && $this->current_page > ($adjacents * 2)) {
                        $html .= "<li><a href='" . $url . "1'>1</a></li>";
                        $html .= "<li><a href='" . $url . 2 . "'>2</a></li>";
                        $html .= "...";
                        for ($counter = $this->current_page - $adjacents; $counter <= $this->current_page + $adjacents; $counter++) {
                            if ($counter == $this->current_page) {
                                $html .= "<li class='active'><a>$counter</a></li>";
                            } else {
                                $html .= "<li><a href='$url$counter'>$counter</a></li>";
                            }
                        }
                        $html .= "..";
                        $html .= "<li><a href='" . $url . $lpm1 . "'>$lpm1</a></li>";
                        $html .= "<li><a href='" . $url . $lastpage . "'>$lastpage</a></li>";
                    } else {
                        $html .= "<li><a href='" . $url . "1'>1</a></li>";
                        $html .= "<li><a href='" . $url . 2 . "'>2</a></li>";
                        $html .= "..";
                        for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                            if ($counter == $this->current_page) {
                                $html .= "<li class='active'><a>$counter</a></li>";
                            } else {
                                $html .= "<li><a href='$url$counter'>$counter</a></li>";
                            }
                        }
                    }
                }
            }
            if ($this->current_page < $lastpage) {
                $html .= "<li><a href='$url$next'><i class='icon ion-md-arrow-round-forward'></i></a></li>";
            } else {
                $html .= "<li class='disabled'><a><i class='icon ion-md-arrow-round-forward'></i></a></li>";
            }
            $html .= "</ul>";
        }

        return $html;
    }
}
