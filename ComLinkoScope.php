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
        return $this->api->likeComment($id);
    }

    public function unlikeComment($id, $userId = null)
    {
        return $this->api->unlikeComment($id);
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
            'author' => $link->authorId,
        ];

        // If there is no created metadata, add it and set the date field with it's initial 'score'
        $created = $this->getMetaKeyValue($val, 'linkoscope_created');
        if (null == $created){
            $val = $this->setMetaKey($val, 'linkoscope_created', time());
            $link->votes = 0;
        } else {
            $time = date(DATE_ATOM, $created);
        }

        $val['date'] = date(DATE_ATOM, $time - $this->dateOffset + $link->votes * $this->likeFactor);
        return $val;
    }

    private function apiToComments($comments) {
        $result = [];
        foreach ($comments as $c) {
            $result[] = $this->apiToComment($c);
        }
        return $result;
    }

    private function apiToComment($c) {
        return new Comment([
            'id' => $c['ID'],
            'date' => $c['date'],
            'postId' => $c['post']['ID'],
            'content' => $c['content'],
            'authorId' => $c['author']['ID'],
            'authorName' => $c['author']['name'],
            'votes' => $c['like_count'],
        ]);
    }

    private function commentToApi(Comment $comment){
        return [
            'content' => $comment->content,
            'date' => $comment->date,
        ];
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

