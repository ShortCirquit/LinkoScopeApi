<?php

namespace ShortCirquit\LinkoScopeApi\Models;

class Link extends BaseModel
{
    public $id;
    public $date;
    public $authorId;
    public $authorName;
    public $title;
    public $url;
    public $votes;
    public $voteList;
    public $comments;
    public $score;
}
