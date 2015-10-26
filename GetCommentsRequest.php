<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-26
 * Time: 10:38 AM
 */

namespace ShortCirquit\LinkoScopeApi;


class GetCommentsRequest
{
    public $linkId = null;
    public $maxResults = 100;
    public $offset = 0;
    public $sortDirection = 'desc';
    public $sortBy = 'score';
}
