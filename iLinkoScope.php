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
     * @return Link[]
     */
    function getLinks();

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
     * @param int|null $userId
     * @return int number of likes
     */
    function likeLink($id, $userId = null);

    /**
     * @param      $id
     * @param int|null $userId
     * @return int number of likes
     */
    function unlikeLink($id, $userId = null);

    /**
     * @param      $id
     * @param int|null $userId
     * @return int number of likes
     */
    function likeComment($id, $userId = null);

    /**
     * @param      $id
     * @param int|null $userId
     * @return int number of likes
     */
    function unlikeComment($id, $userId = null);

    /**
     * @return UserProfile
     */
    function getAccount();

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
     * @param $id
     * @return Comment
     */
    function deleteComment($id);
}
