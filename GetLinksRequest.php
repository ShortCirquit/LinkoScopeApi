<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-21
 * Time: 12:56 PM
 */

namespace ShortCirquit\LinkoScopeApi;


class GetLinksRequest
{
    public $authorId = null;
    public $maxResults = 100;
    public $offset = 0;
    public $sortDirection = 'desc';
    public $sortBy = 'score';
    public $tags = [];
}
