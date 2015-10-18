<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-18
 * Time: 11:47 AM
 */

namespace ShortCirquit\LinkoScopeApi\Test;


class OrgLinkTest extends LinkTestBase
{
    public function setUp()
    {
        $this->api = ApiUtils::getOrgApi();
    }
}
