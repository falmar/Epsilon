<?php
/**
 * Project: Epsilon
 * Date: 10/26/15
 * Time: 5:31 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Environment;

defined('EPSILON_EXEC') or die();

use App\Config;
use Epsilon\Factory;
use Epsilon\IO\Input;
use Epsilon\Object\Object;

/**
 * Class URI
 *
 * @package Epsilon\Environment
 */
class URI extends Object
{
    protected static $Instance;

    /** @var string */
    protected $URI;
    protected $Scheme;
    protected $Host;
    protected $Path;
    protected $InversePath;
    protected $Query;
    protected $Fragment;

    /**
     * @param array $URI
     */
    public function __construct($URI)
    {
        parent::__construct([]);
        if (is_string($URI)) {
            $this->URI = $URI;
            $this->parse($URI);
        }
    }

    /**
     * @return URI
     */
    public static function getInstance()
    {
        if (!isset(self::$Instance)) {
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
                $https = 'https://';
            } else {
                $https = 'http://';
            }

            if (!Factory::getApplication()->isCLI()) {
                if ($_SERVER['PHP_SELF'] && isset($_SERVER['REQUEST_URI'])) {
                    $uri = $https . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                } else {
                    $uri = $https . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
                    if (isset($_SERVER['QUERY_STRING']) && !$_SERVER['QUERY_STRING']) {
                        $uri .= $_SERVER['QUERY_STRING'];
                    }
                }
            } else {
                $uri = null;
            }

            self::$Instance = new URI($uri);
        }

        return self::$Instance;
    }

    /**
     * @param string $URI
     */
    public function parse($URI)
    {
        $parts          = parse_url($URI);
        $this->Scheme   = isset($parts['scheme']) ? $parts['scheme'] . '://' : null;
        $this->Host     = isset($parts['host']) ? $parts['host'] : null;
        $this->Path     = isset($parts['path']) ? $parts['path'] : null;
        $this->Query    = isset($parts['query']) ? $parts['query'] : null;
        $this->Fragment = isset($parts['fragment']) ? $parts['fragment'] : null;

        $this->InversePath = Input::getVar('r', Input::GET);

        if (Config::PRETTY_URL && !$this->InversePath) {
            $this->InversePath = strpos($this->Path, 'index.php') ? substr($this->Path, strpos($this->Path, 'index.php') + 9, strlen($this->Path)) : $this->Path;
        }
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->getScheme() . $this->getHost();
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->Scheme;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->Host;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->Path;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->Query;
    }

    /**
     * @return mixed
     */
    public function getFragment()
    {
        return $this->Fragment;
    }

    /**
     * @return string
     */
    public function getRelativePath()
    {
        if ($this->getPath() == $this->getInversePath()) {
            return $this->getServer() . '/';
        } elseif (strpos($this->getPath(), 'index.php')) {
            return $this->getServer() . substr($this->getPath(), 0, strpos($this->getPath(), 'index.php'));
        } else {
            return $this->getServer() . $this->getPath();
        }
    }

    /**
     * @return string
     */
    public function getInversePath()
    {
        return $this->InversePath;
    }

    public function toStrong()
    {
        $this->__toString();
    }

    /**
     * @return array|string
     */
    public function __toString()
    {
        return $this->URI;
    }
}
