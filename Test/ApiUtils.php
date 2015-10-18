<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-18
 * Time: 8:26 AM
 */

namespace ShortCirquit\LinkoScopeApi\Test;

use ShortCirquit\LinkoScopeApi\ComLinkoScope;
use ShortCirquit\LinkoScopeApi\OrgLinkoScope;

/**
 * Class ApiUtils
 *
 * Contains common utilities used by unit tests
 *
 * @package ShortCirquit\WordPressApi\Test
 */
class ApiUtils
{
    /**
     * Create ComWpApi instance
     *
     * @return ComLinkoScope
     */
    public static function getComApi(){
        $file = __DIR__ . '/config_com.json';
        $cfg = json_decode(file_get_contents($file), true);
        return new ComLinkoScope($cfg);
    }

    /**
     * @return OrgLinkoScope
     */
    public static function getOrgApi(){
        $file = __DIR__ . '/config_org.json';
        $cfg = json_decode(file_get_contents($file), true);
        return new OrgLinkoScope($cfg);
    }
}
