<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-18
 * Time: 11:42 AM
 */

namespace ShortCirquit\LinkoScopeApi\Test;

class ComLinkTest extends LinkTestBase
{
    public function setUp()
    {
        $this->api = ApiUtils::getComApi();
    }
}

