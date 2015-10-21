<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 2015-10-20
 * Time: 8:09 PM
 */

namespace ShortCirquit\LinkoScopeApi;


interface iApiHandler
{
    function refreshLink($id);
    function refreshComment($id);
}
