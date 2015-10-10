<?php

namespace ShortCirquit\LinkoScopeApi\Models;

use yii\base\Object;

class Link extends Object
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