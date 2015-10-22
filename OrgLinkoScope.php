<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-28
 * Time: 1:13 PM
 */

namespace ShortCirquit\LinkoScopeApi;


use ShortCirquit\LinkoScopeApi\Models\UserProfile;
use ShortCirquit\WordPressApi\OrgWpApi;
use ShortCirquit\LinkoScopeApi\Models\Link;
use ShortCirquit\LinkoScopeApi\Models\Comment;

class OrgLinkoScope implements iLinkoScope
{
    private $linkEndpoint = 'linkolink';
    private $api;
    private $linkVoteMultiplier = 86400; //one day
    private $commentVoteMultiplier = 86400;
    private $userId;

    public function __construct(Array $cfg) {
        $this->api = new OrgWpApi($cfg);
        $this->userId = isset($cfg['userId']) ? $cfg['userId'] : null;
    }

    public function getConfig()
    {
        return $this->api->getConfig();
    }

    public function authorize($returnUrl) {
        return $this->api->getAuthorizeUrl($returnUrl);
    }

    public function access($token, $verifier) {
        return $this->api->getAccessToken($token, $verifier);
    }

    public function getLinks(GetLinksRequest $request = null) {
        $request = $request ?: new GetLinksRequest();

        $sortParams = [
            'filter' => [
                'meta_key' => 'linkoscope_score',
                'order' => 'DESC',
                'orderby' => 'meta_value_num',
                'posts_per_page' => $request->maxResults,
            ],
            'page' => ($request->offset / $request->maxResults) + 1,
        ];

        if ($request->authorId != null){
            $sortParams['filter']['author'] = $request->authorId;
        }

        $links = $this->api->listCustom($this->linkEndpoint,$sortParams);
        $headers = $this->api->getLastHeaders();

        $ret = new GetLinksResult();
        if (preg_match('/X-WP-Total: (\d+)/', $headers, $matches) == 1)
            $ret->totalResults = (int)$matches[1];
        $ret->offset = $request->offset;
        $ret->links = $this->apiToLinks($links);
        return $ret;
    }

    public function getLink($id) {
        $link = $this->api->getCustom($this->linkEndpoint, $id);
        return $this->apiToLink($link);
    }

    public function addLink(Link $link)
    {
        $link->voteList = [];
        $link->score = time();
        $body = $this->linkToApi($link);
        $p = $this->api->addCustom($this->linkEndpoint, $body);
        return $this->apiToLink($p);
    }

    public function updateLink(Link $link)
    {
        $link->voteList = array_unique($link->voteList);
        $link->votes = count($link->voteList);
        $link->score = strtotime($link->date) + $this->linkVoteMultiplier * $link->votes;
        $body = $this->linkToApi($link);
        $result = $this->api->updateCustom($this->linkEndpoint, $link->id, $body);
        return $this->apiToLink($result);
    }

    public function likeLink($id)
    {
        $link = $this->getLink($id);
        $link->voteList[] = $this->userId;
        return $this->updateLink($link)->votes;
    }

    public function unlikeLink($id)
    {
        $link = $this->getLink($id);
        $link->voteList = array_diff($link->voteList, [$this->userId]);
        return $this->updateLink($link)->votes;
    }

    public function deleteLink($id)
    {
        $l = $this->api->deleteCustom($this->linkEndpoint, $id);
        return $this->apiToLink($l);
    }

    public function getTypes()
    {
        return $this->api->listTypes();
    }

    public function getAccount($id = null)
    {
        $u = ($id === null) ? $this->api->getSelf()['body'] : $this->api->getUser($id);
        return $this->apiToUserProfile($u);
    }

    public function getAccounts()
    {
        return array_map(
            function($u){return $this->apiToUserProfile($u);},
            $this->api->getUsers()
        );
    }

    private function apiToUserProfile($u)
    {
        return new UserProfile([
            'id'       => $u['id'],
            'username' => $u['name'],
            'name' => $u['name'],
            'url' => $u['url'],
        ]);
    }

    public function getComments($postId)
    {
        $sort = ['orderby' => 'karma'];
        $results = $this->api->listComments($postId, $sort);
        return $this->apiToComments($results);
    }

    public function getComment($id)
    {
        $c = $this->api->getComment($id);
        return $this->apiToComment($c);
    }

    public function addComment(Comment $comment)
    {
        $comment->likeList = [];
        $comment->votes = 0;
        $comment->score = time();
        $body = $this->commentToApi($comment);
        $c = $this->api->addComment($body);
        return $this->apiToComment($c);
    }

    public function updateComment(Comment $comment)
    {
        $comment->likeList = array_unique($comment->likeList);
        $comment->votes = count($comment->likeList);
        $oldScore = $comment->score;
        $comment->score = strtotime($comment->date) +
            $this->commentVoteMultiplier * $comment->votes;
        if ($oldScore == $comment->score) $comment->score++; // API throws an error if nothing changes.
        $body = $this->commentToApi($comment);
        $c = $this->api->updateComment($comment->id, $body);
        return $this->apiToComment($c);
    }

    public function likeComment($id)
    {
        $comment = $this->getComment($id);
        $comment->likeList[] = $this->userId;
        return $this->updateComment($comment)->votes;
    }

    public function unlikeComment($id)
    {
        $comment = $this->getComment($id);
        $comment->likeList = array_diff_key($comment->likeList, [$this->userId]);
        $comment = $this->updateComment($comment);
        return $this->updateComment($comment)->votes;
    }

    public function deleteComment($id)
    {
        $c = $this->api->deleteComment($id);
        return $this->apiToComments($c);
    }

    private function apiToLinks($items) {
        $result = [];
        foreach ($items as $item)
            $result[] = $this->apiToLink($item);
        return $result;
    }

    private function apiToLink($item)
    {
        $voteList = empty($item['linkoscope_likes']) ? [] : explode(';', $item['linkoscope_likes']);
        return new Link([
            'id' => $item['id'],
            'authorId' => $item['author'],
            'authorName' => $item['author_name'],
            'date' => $item['date'],
            'title' => $item['title']['raw'],
            'url' => $item['content']['raw'],
            'score' => $item['linkoscope_score'] ?: 0,
            'voteList' => $voteList,
            'votes' => count($voteList),
            'hasVoted' => in_array($this->userId, $voteList),
            'comments' => $item['comment_count'],
        ]);
    }

    private function linkToApi(Link $link)
    {
        return [
            'title' => $link->title,
            'content' => $link->url,
            'linkoscope_score' => $link->score,
            'linkoscope_likes' => implode(';', $link->voteList),
            'status' => 'publish',
            'author' => $link->authorId,
        ];
    }

    private function apiToComments($items){
        $result = [];
        foreach ($items as $item)
            $result[] = $this->apiToComment($item);
        return $result;
    }

    private function apiToComment($c){
        $likeList = empty($c['linkoscope_likes']) ? [] : explode(';', $c['linkoscope_likes']);
        return new Comment([
            'id' => $c['id'],
            'date' => $c['date'],
            'postId' => $c['post'],
            'content' => $c['content']['raw'],
            'votes' => count($likeList),
            'hasVoted' => in_array($this->userId, $likeList),
            'likeList' => $likeList,
            'score' => $c['karma'],
            'authorId' => $c['author'],
            'authorName' => $c['author_name'],
        ]);
    }

    private function commentToApi(Comment $comment){
        return [
            'post' => $comment->postId,
            'content' => $comment->content,
            'author' => $comment->authorId,
            'author_name' => $comment->authorName,
            'status' => 'publish',
            'karma' =>  $comment->score,
            'linkoscope_likes' => implode(';', $comment->likeList ?: []),
        ];
    }
}
