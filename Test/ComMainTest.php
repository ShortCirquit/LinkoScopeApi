<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-12
 * Time: 4:52 PM
 */

namespace ShortCirquit\LinkoScopeApi\Test;

use ShortCirquit\LinkoScopeApi\ComLinkoScope;
use ShortCirquit\LinkoScopeApi\Models\Link;

class ComMainTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ComLinkoScope
     */
    private $api;

    private $configTemplate = [
        "clientId"     => "clientId",
        "clientSecret" => "clientSecret",
        "blogId"       => "blogId",
        "blogUrl"      => "blogUrl",
        "token"        => "token",
        "adminToken"   => "token",
    ];

    public function setUp()
    {
        $file = __DIR__ . '/config_com.json';
        $cfg = json_decode(file_get_contents($file), true);
        $this->api = new ComLinkoScope($cfg);
    }

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
     * @param Link $link
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
     * @param Link $link
     * @depends testGetLink
     */
    public function testDeleteLink(Link $link)
    {
        $ret = $this->api->deleteLink($link->id);
        $this->assertNotNull($ret);
        $this->assertEquals($link->id, $ret->id);
        $this->assertEquals($link->title, $ret->title);
    }
}
