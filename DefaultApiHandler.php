<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-20
 * Time: 8:10 PM
 */

namespace ShortCirquit\LinkoScopeApi;


class DefaultApiHandler implements iApiHandler
{
    private $api;

    public function __construct(iLinkoScope $api)
    {
        $this->api = $api;
    }

    public function refreshLink($id)
    {
        $link = $this->api->getLink($id);
        $this->api->updateLink($link);
    }

    public function refreshComment($id)
    {
        $comment = $this->api->getComment($id);
        $this->api->updateComment($comment);
    }
}
