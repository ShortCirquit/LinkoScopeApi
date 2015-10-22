<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-09-28
 * Time: 1:16 PM
 */

namespace ShortCirquit\LinkoScopeApi;

use app\models\User;
use ShortCirquit\LinkoScopeApi\Models\UserProfile;
use ShortCirquit\WordPressApi\ComWpApi;
use ShortCirquit\LinkoScopeApi\Models\Link;
use ShortCirquit\LinkoScopeApi\Models\Comment;

class ComLinkoScope implements iLinkoScope
{
    private $api;
    private $adminApi;
    private $likeFactor = 86400;
    private $dateOffset = 3153600000;
    private $ctx = ['context' => 'edit'];

    private $handler;

    public function __construct(Array $cfg){
        $this->api = new ComWpApi($cfg);
        if (isset($cfg['adminToken'])){
            $cfg['token'] = $cfg['adminToken'];
            $this->adminApi = new ComWpApi($cfg);
        } else {
            $this->adminApi = $this->api;
        }

        $this->handler = isset($cfg['handler']) ? $cfg['handler'] : new DefaultApiHandler($this);
    }

    public function setHandler(iApiHandler $handler)
    {
        $this->handler = $handler;
    }

    public function getConfig(){
        return $this->api->getConfig() + [
            'adminToken' => $this->adminApi->token,
        ];
    }

    public function authorize(){
        return $this->api->getAuthorizeUrl();
    }

    public function token($code)
    {
        return $this->api->getToken($code);
    }

    public function getLinks(GetLinksRequest $request = null){
        $request = $request ?: new GetLinksRequest();
        $params = [
            'number' => $request->maxResults,
            'offset' => $request->offset,
        ] + $this->ctx;
        if($request->authorId != null)
            $params['author'] = $request->authorId;

        $result = $this->api->listPosts($params);
        if (!isset($result['posts']))
            return null;

        $ret = new GetLinksResult();
        $ret->links = $this->apiToLinks($result['posts']);
        $ret->offset = $request->offset;
        $ret->totalResults = $result['found'];
        return $ret;
    }

    public function getLink($id){
        $result = $this->api->getPost($id, $this->ctx);
        return $this->apiToLink($result);
    }

    public function addLink(Link $link){
        $link->score = time();
        $link->date = date(DATE_ATOM, $link->score);
        $p = $this->api->addPost($this->linkToApi($link), $this->ctx);
        return $this->apiToLink($p);
    }

    public function updateLink(Link $link){
        $link->score = strtotime($link->date) + $this->likeFactor * $link->votes;
        $l = $this->adminApi->updatePost($link->id, $this->linkToApi($link), $this->ctx);
        return $this->apiToLink($l);
    }

    public function deleteLink($id){
        $p = $this->api->deletePost($id, $this->ctx);
        return $this->apiToLink($p);
    }

    public function likeLink($id)
    {
        $result = $this->api->likePost($id);
        $this->handler->refreshLink($id);
        return $result['like_count'];
    }

    public function unlikeLink($id)
    {
        $result = $this->api->unlikePost($id);
        $this->handler->refreshLink($id);
        return $result['like_count'];
    }

    public function likeComment($id)
    {
        $result = $this->api->likeComment($id);
        $this->handler->refreshComment($id);
        return $result['like_count'];
    }

    public function unlikeComment($id)
    {
        $result = $this->api->unlikeComment($id);
        $this->handler->refreshComment($id);
        return $result['like_count'];
    }

    public function getComment($id){
        $c = $this->api->getComment($id, $this->ctx);
        return $this->apiToComment($c);
    }

    public function updateComment(Comment $c){
        $c->score = strtotime($c->date) + $this->likeFactor * $c->votes;
        $this->updateToPost($c);
        return $this->adminApi->updateComment($c->id, $this->commentToApi($c), $this->ctx);
    }

    public function getAccount($id = null){
        if ($id !== null)
            return $this->getUser($id);

        $user = $this->api->getSelf();
        return new UserProfile([
            'id' => $user['ID'],
            'username' => $user['display_name'],
        ]);
    }

    private function getUser($id){
        $u = $this->api->getUser($id);
        return new UserProfile([
            'id' => $u['ID'],
            'username' => $u['login'],
            'name' => $u['name'],
            'url' => $u['profile_URL'],
        ]);
    }

    public function getComments($postId){
        $result = $this->api->listComments($postId, ['number' => 100] + $this->ctx);
        if (!isset($result['comments']))
            return [];
        return $this->apiToComments($result['comments']);
    }

    public function addComment(Comment $comment) {
        $comment->score = time();
        $comment->date = date(DATE_ATOM, $comment->score);
        $c = $this->api->addComment($comment->postId, $this->commentToApi($comment), $this->ctx);
        $this->updateComment($this->apiToComment($c));
        return $this->apiToComment($c);
    }

    public function deleteComment($id)
    {
        return $this->api->deleteComment($id, $this->ctx);
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
            'date' => date(DATE_ATOM, $this->getMetaKeyValue($p, 'linkoscope_created')),
            'title' => $p['title'],
            'url' => $p['content'],
            'votes' => $p['like_count'],
            'hasVoted' => $p['i_like'],
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

        $val = $this->setMetaKey($val, 'linkoscope_created', strtotime($link->date));
        $val = $this->setMetaKey($val, 'linkoscope_score', $link->score);
        $val['date'] = date(DATE_ATOM, $link->score - $this->dateOffset);
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
            'postId' => $c['post']['ID'],
            'content' => $c['content'],
            'authorId' => $c['author']['ID'],
            'authorName' => $c['author']['name'],
            'votes' => $c['like_count'],
            'hasVoted' => $c['i_like'],
        ]);

        $this->updateFromPostCache($comment, $c);
        return $comment;
    }

    // fetch associated post if it hasn't been fetched before, or if the incorrect post is cached.
    private function updateFromPostCache(Comment $comment, $c){
        if ($comment->postId == null) return;
        if ($this->postCache == null || $this->postCache['ID'] != $comment->postId) {
            $this->postCache = $this->adminApi->getPost($comment->postId);
        }

        $comment->date = $this->getMetaKeyValue($this->postCache, "linkoscope_created_$comment->id") ?: $c['date'];
        $comment->score = $this->getMetaKeyValue($this->postCache, "linkoscope_score_$comment->id") ?: strtotime($c['date']);
    }

    private function commentToApi(Comment $comment){
        $data = [
            'content' => $comment->content,
            'date' => date(DATE_ATOM, $comment->score - $this->dateOffset),
            'status' => 'approved',
            'author' => $comment->authorId,
        ];
        return $data;
    }

    private function updateToPost(Comment $comment){
        $post = $this->adminApi->getPost($comment->postId);
        $update = ['metadata' => $post['metadata']];
        $update = $this->setMetaKey($update, "linkoscope_created_$comment->id", $comment->date);
        $update = $this->setMetaKey($update, "linkoscope_score_$comment->id", $comment->score);
        $this->adminApi->updatePost($post['ID'], $update);
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

