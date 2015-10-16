<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-29
 * Time: 5:37 PM
 */

namespace ShortCirquit\LinkoScopeApi;

use ShortCirquit\LinkoScopeApi\Models\Link;
use ShortCirquit\LinkoScopeApi\Models\Comment;

interface iLinkoScope
{
    function getConfig();
    function getLinks();
    function getLink($id);
    function addLink(Link $link);
    function updateLink(Link $link);
    function deleteLink($id);
    function likeLink($id, $userId = null);
    function unlikeLink($id, $userId = null);
    function likeComment($id, $userId = null);
    function unlikeComment($id, $userId = null);
    function getAccount();
    function getComments($postId);
    function addComment(Comment $comment);
    function deleteComment($id);
}
