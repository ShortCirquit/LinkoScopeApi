<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-28
 * Time: 1:16 PM
 */

namespace ShortCirquit\LinkoScopeApi;

use ShortCirquit\WordPressApi\ComWpApi;
use ShortCirquit\LinkoScopeApi\Models\Link;
use ShortCirquit\LinkoScopeApi\Models\Comment;

class ComLinkoScope
{
    public $type = 'com';
    private $api;
    private $likeFactor = 86400;
    private $dateOffset = 3153600000;

    public function __construct(ComWpApi $api){
        $this->api = $api;
    }

    public function getConfig(){
        return $this->api->getConfig();
    }

    public function authorize(){
        return $this->api->getAuthorizeUrl();
    }

    public function token($code)
    {
        return $this->api->getToken($code);
    }

    public function getLinks(){
        $this->api->useAdminToken = true;
        $result = $this->api->listPosts();
        if (!isset($result['posts']))
            return [];

        return ($this->apiToLinks($result['posts']));
    }

    public function getLink($id){
        $this->api->useAdminToken = true;
        $result = $this->api->getPost($id);
        return $this->apiToLink($result);
    }

    public function addLink(Link $link){
        return $this->api->addPost($this->linkToApi($link));
    }

    public function updateLink(Link $link){
        return $this->api->updatePost($link->id, $this->linkToApi($link));
    }

    public function deleteLink($id){
        return $this->api->deletePost($id);
    }

    public function likeLink($id, $userId = null)
    {
        $this->api->likePost($id);
        $this->api->useAdminToken = true;
        $link = $this->getLink($id);
        $this->api->useAdminToken = true;
        return $this->updateLink($link);
    }

    public function unlikeLink($id, $userId = null)
    {
        $this->api->unlikePost($id);
        $this->api->useAdminToken = true;
        $link = $this->getLink($id);
        $this->api->useAdminToken = true;
        return $this->updateLink($link);
    }

    public function likeComment($id, $userId = null)
    {
        $this->api->likeComment($id);
        $c = $this->getComment($id);
        return $this->updateComment($c);
    }

    public function unlikeComment($id, $userId = null)
    {
        $this->api->unlikeComment($id);
        $c = $this->getComment($id);
        return $this->updateComment($c);
    }

    private function getComment($id){
        $this->api->adminToken = true;
        $c = $this->api->getComment($id);
        return $this->apiToComment($c);
    }

    private function updateComment(Comment $c){
        $this->api->adminToken = true;
        $c->score = strtotime($c->date) + $this->likeFactor * $c->votes;
        $this->updateToPost($c);
        return $this->api->updateComment($c->id, $this->commentToApi($c));
    }

    public function getTypes(){return [];}

    public function getAccount(){
        return $this->api->getSelf();
    }

    public function getComments($postId){
        $this->api->useAdminToken = true;
        $result = $this->api->listComments($postId);
        if (!isset($result['comments']))
            return [];
        return $this->apiToComments($result['comments']);
    }

    public function addComment(Comment $comment) {
        return $this->api->addComment($comment->postId, [
            'content' => $comment->content,
        ]);
    }

    public function deleteComment($id)
    {
        return $this->api->deleteComment($id);
    }

    private function apiToLinks($posts)
    {
        $result = [];
        foreach ($posts as $p)
        {
            $result[] = $this->apiToLink($p);
        }
        return $result;
    }

    private function apiToLink($p){
        return new Link([
            'id' => $p['ID'],
            'authorId' => $p['author']['ID'],
            'authorName' => $p['author']['name'],
            'date' => $p['modified'],
            'title' => $p['title'],
            'url' => $p['content'],
            'votes' => $p['like_count'],
            'score' => $this->getMetaKeyValue($p, 'linkoscope_score'),
            'comments' => $p['discussion']['comment_count'],
        ]);
    }

    private function linkToApi(Link $link){
        $val = [
            'title' => $link->title,
            'content' => $link->url,
            'status' => 'publish',
        ];

        if ($link->authorId != null)
            $val['author'] = $link->authorId;

        // If there is no created metadata, add it and set the date field with it's initial 'score'
        $created = $this->getMetaKeyValue($val, 'linkoscope_created');
        if (null == $created){
            $created = time();
            $val = $this->setMetaKey($val, 'linkoscope_created', $created);
            $link->votes = 0;
        }

        $score = $created  + $link->votes * $this->likeFactor;
        $val = $this->setMetaKey($val, 'linkoscope_score', $score);
        $val['date'] = date(DATE_ATOM, $score - $this->dateOffset);
        return $val;
    }

    private function apiToComments($comments) {
        $result = [];
        foreach ($comments as $c) {
            $result[] = $this->apiToComment($c);
        }
        return $result;
    }

    private $postCache = null;

    private function apiToComment($c) {
        $comment = new Comment([
            'id' => $c['ID'],
            'date' => $this->getMetaKeyValue($c, 'linkoscope_created') ?: $c['date'],
            'postId' => $c['post']['ID'],
            'content' => $c['content'],
            'authorId' => $c['author']['ID'],
            'authorName' => $c['author']['name'],
            'votes' => $c['like_count'],
            'score' => $this->getMetaKeyValue($c, 'linkoscope_score') ?: strtotime($c['date']),
        ]);

        $this->updateFromPostCache($comment, $c);
        return $comment;
    }

    // fetch associated post if it hasn't been fetched before, or if the incorrect post is cached.
    private function updateFromPostCache(Comment $comment, $c){
        if ($comment->postId == null) return;
        if ($this->postCache == null || $this->postCache['ID'] != $comment->postId)
            $this->postCache = $this->api->getPost($comment->postId);

        $comment->date = $this->getMetaKeyValue($this->postCache, "linkoscope_created_$comment->id") ?: $c['date'];
        $comment->score = $this->getMetaKeyValue($this->postCache, "linkoscope_score_$comment->id") ?: strtotime($c['date']);
    }

    private function commentToApi(Comment $comment){
        $data = [
            'content' => $comment->content,
            'date' => $comment->date,
        ];
        return $data;
    }

    private function updateToPost(Comment $comment){
        $post = $this->api->getPost($comment->postId);
        $update = ['metadata' => $post['metadata']];
        $update = $this->setMetaKey($update, "linkoscope_created_$comment->id", $comment->date);
        $update = $this->setMetaKey($update, "linkoscope_score_$comment->id", $comment->score);
        $this->api->updatePost($post['ID'], $update);
    }

    private function getMetaKeyValue($result, $key){
        if (!isset($result['metadata']) || !is_array($result['metadata']))
            return null;
        foreach ($result['metadata'] as $data){
            if ($data['key'] == $key)
                return $data['value'];
        }
        return null;
    }

    private function setMetaKey($result, $key, $value){
        $result = $this->deleteMetaKey($result, $key);
        if(!isset($result['metadata']))
            $result['metadata'] = [];
        $result['metadata'][] = ['key' => $key, 'value' => $value];
        return $result;
    }

    private function deleteMetaKey($result, $key){
        if (!isset($result['metadata']) || !is_array($result['metadata']))
            return $result;

        $arr = $result['metadata'];
        $result['metadata'] = [];
        foreach ($arr as $item){
            if (isset($item['key']) && $item['key'] != $key)
                $result['metadata'][] = $item;
        }

        return $result;
    }
}

