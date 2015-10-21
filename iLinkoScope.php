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
use ShortCirquit\LinkoScopeApi\Models\UserProfile;

interface iLinkoScope
{
    function getConfig();

    /**
     * @param $request GetLinksRequest
     * @return Link[]
     */
    function getLinks(GetLinksRequest $request = null);

    /**
     * @param $id
     * @return Link
     */
    function getLink($id);

    /**
     * @param Link $link
     * @return Link Updated link
     */
    function addLink(Link $link);

    /**
     * @param Link $link
     * @return Link
     */
    function updateLink(Link $link);

    /**
     * @param $id
     * @return Link
     */
    function deleteLink($id);

    /**
     * @param      $id
     * @return int number of likes
     */
    function likeLink($id);

    /**
     * @param      $id
     * @return int number of likes
     */
    function unlikeLink($id);

    /**
     * @param      $id
     * @return int number of likes
     */
    function likeComment($id);

    /**
     * @param      $id
     * @return int number of likes
     */
    function unlikeComment($id);

    /**
     * @return UserProfile
     */
    function getAccount();

    /**
     * @param $commentId
     * @return Comment
     */
    function getComment($commentId);

    /**
     * @param $postId
     * @return Comment[]
     */
    function getComments($postId);

    /**
     * @param Comment $comment
     * @return Comment
     */
    function addComment(Comment $comment);

    /**
     * @param Comment $comment
     * @return Comment
     */
    function updateComment(Comment $comment);

    /**
     * @param $id
     * @return Comment
     */
    function deleteComment($id);
}
