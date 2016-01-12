<?php

/**
 * Project: Epsilon
 * Date: 11/5/15
 * Time: 12:42 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace App;

defined('EPSILON_EXEC') or die();

/**
 * Class eConfig
 */
class Config
{
    /** Site Config */
    const APPLICATION_ID     = 'Site';
    const SITE_NAME          = 'Epsilon | PHP Framework';
    const COMPANY_NAME       = 'Epsilon';
    const MAINTENANCE        = false;
    const LOCALHOST          = true;
    const APP_DEBUG          = true;
    const CHARSET            = 'UTF-8';
    const TIMEZONE           = 'America/Caracas';
    const DATE_FORMAT        = 'H:i:s d-m-Y';
    const PASSWORD_HAST_COST = 9;
    const PRETTY_URL         = false;
    const SHOW_SCRIPT        = false;

    /** Database  */
    const DB_PERSISTENT = true;
    const DB_DEBUG      = true;
    const DB_PROTOCOL   = 'mysql';
    const DB_HOST       = '127.0.0.1';
    const DB_PORT       = '3306';
    const DB_NAME       = 'Epsilon';
    const DB_USER       = 'Epsi';
    const DB_PASSWORD   = '';

    /** Paging */
    const MAX_PAGE_SIZE = 15;

    /** Session && Cookies */
    const SESSION_TIMEOUT  = 3600;
    const SESSION_LIFESPAN = 7200;
    const COOKIE_DOMAIN    = '';
    const COOKIE_PATH      = '';

    /** Google Captcha Config */
    const CAPTCHA_KEY        = '';
    const CAPTCHA_SECRET_KEY = '';
}
