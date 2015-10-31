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
    public $hasVoted;
    public $voteList;
    public $comments;
    public $score;
    public $tags = [1 => 'tag1', 2 => 'tag2'];
}
