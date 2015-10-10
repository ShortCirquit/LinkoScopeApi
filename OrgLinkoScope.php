<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-28
 * Time: 1:13 PM
 */

namespace ShortCirquit\LinkoScopeApi;


use ShortCirquit\WordPressApi\OrgWpApi;
use ShortCirquit\LinkoScopeApi\Models\Link;
use ShortCirquit\LinkoScopeApi\Models\Comment;

class OrgLinkoScope
{
    public $type = 'org';
    private $linkEndpoint = 'linkolink';
    private $api;
    private $linkVoteMultiplier = 86400; //one day
    private $commentVoteMultiplier = 86400;

    public function __construct(OrgWpApi $api) {
        $this->api = $api;
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

    public function getLinks() {
        $sortParams = [
            'filter' => [
                'meta_key' => 'linkoscope_score',
                'order' => 'DESC',
                'orderby' => 'meta_value_num',
        ]];

        $links = $this->api->listCustom($this->linkEndpoint,$sortParams);
        return $this->apiToLinks($links);
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
        return $this->api->addCustom($this->linkEndpoint, $body);
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

    public function likeLink($id, $userId)
    {
        $link = $this->getLink($id);
        $link->voteList[] = $userId;
        return $this->updateLink($link);
    }

    public function unlikeLink($id, $userId)
    {
        $link = $this->getLink($id);
        $link->voteList = array_diff($link->voteList, [$userId]);
        return $this->updateLink($link);
    }

    public function deleteLink($id)
    {
        return $this->api->deleteCustom($this->linkEndpoint, $id);
    }

    public function getTypes()
    {
        return $this->api->listTypes();
    }

    public function getAccount()
    {
        return $this->api->getSelf();
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
        return $this->api->addComment($body);
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
        return $this->api->updateComment($comment->id, $body);
    }

    public function likeComment($id, $userId)
    {
        $comment = $this->getComment($id);
        $comment->likeList[] = $userId;
        return $this->updateComment($comment);
    }

    public function unlikeComment($id, $userId)
    {
        $comment = $this->getComment($id);
        $comment->likeList = array_diff_key($comment->likeList, [$userId]);
        return $this->updateComment($comment);
    }

    public function deleteComment($id)
    {
        return $this->api->deleteComment($id);
    }

    private function apiToLinks($items) {
        $result = [];
        foreach ($items as $item)
            $result[] = $this->apiToLink($item);
        return $result;
    }

    private function apiToLink($item)
    {
        return new Link([
            'id' => $item['id'],
            'authorId' => $item['author'],
            'authorName' => $item['author_name'],
            'date' => $item['date'],
            'title' => $item['title']['raw'],
            'url' => $item['content']['raw'],
            'score' => $item['linkoscope_score'] ?: 0,
            'voteList' => empty($item['linkoscope_likes']) ? [] : explode(';', $item['linkoscope_likes']),
            'votes' => empty($item['linkoscope_likes']) ? 0 : count(explode(';', $item['linkoscope_likes'])),
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
        $likes = count($likeList);
        return new Comment([
            'id' => $c['id'],
            'date' => $c['date'],
            'postId' => $c['post'],
            'content' => $c['content']['raw'],
            'votes' => $likes,
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
            'author_name' => $comment->authorName,
            'karma' =>  $comment->score,
            'linkoscope_likes' => implode(';', $comment->likeList ?: []),
        ];
    }
}