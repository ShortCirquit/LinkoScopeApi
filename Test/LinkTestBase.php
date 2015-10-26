<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-18
 * Time: 11:39 AM
 */

namespace ShortCirquit\LinkoScopeApi\Test;

use ShortCirquit\LinkoScopeApi\iLinkoScope;
use ShortCirquit\LinkoScopeApi\Models\Link;

class LinkTestBase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var iLinkoScope
     */
    protected $api;

    public function testCreateLink()
    {
        $link = new Link();
        $link->title = "Title 1";
        $link->url = "http://url1.com";

        $ret = $this->api->addLink($link);
        $this->assertNotNull($ret);
        $this->assertEquals($link->title, $ret->title);

        return $ret;
    }

    /**
     * @depends testCreateLink
     */
    public function testGetLink(Link $link)
    {
        $ret = $this->api->getLink($link->id);
        $this->assertNotNull($ret);
        $this->assertEquals($link->id, $ret->id);
        $this->assertEquals($link->title, $ret->title);

        return $ret;
    }

    /**
     * @depends testGetLink
     */
    public function testDeleteLink(Link $link)
    {
        $ret = $this->api->deleteLink($link->id);
        $this->assertNotNull($ret);
        $this->assertEquals($link->id, $ret->id);
        $this->assertEquals($link->title, $ret->title);
    }

    public function testLikeLink()
    {
        $link = new Link();
        $link->title = "Like Link Test";
        $link->url = 'http://like.com/';
        $link = $this->api->addLink($link);

        $this->assertNotNull($link);
        $this->assertNotNull($link->id);
        $this->assertNotNull($link->score);
        $this->assertEquals(0, $link->votes);

        $this->assertEquals(1, $this->api->likeLink($link->id));
        $update = $this->api->getLink($link->id);
        $this->assertEquals(1, $update->votes);
        $this->assertGreaterThan($link->score, $update->score);

        $this->assertEquals(0, $this->api->unlikeLink($link->id));
        $update = $this->api->getLink($link->id);
        $this->assertEquals(0, $update->votes);
        $this->assertEquals($link->score, $update->score);
    }

    public function testGetAccounts()
    {
        $accounts = $this->api->getAccounts();
        $this->assertNotNull($accounts);
        $this->assertGreaterThanOrEqual(2, count($accounts),
            'We need at least two different accounts for this test to work.');

        $acc1 = $this->api->getAccount($accounts[0]->id);
        $this->assertNotNull($acc1);

        $self = $this->api->getAccount();
        $acc2 = $this->api->getAccount($self->id);
        $this->assertEquals($self->id, $acc2->id);
    }
}
