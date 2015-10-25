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
    private static $file = __DIR__ . '/config.json';

    /**
     * Create ComWpApi instance
     *
     * @return ComLinkoScope
     */
    public static function getComApi(){
        $cfg = json_decode(file_get_contents(ApiUtils::$file), true);
        return new ComLinkoScope($cfg['com']);
    }

    /**
     * @return OrgLinkoScope
     */
    public static function getOrgApi(){
        $cfg = json_decode(file_get_contents(ApiUtils::$file), true);
        return new OrgLinkoScope($cfg['org']);
    }
}
