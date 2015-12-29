<?php
/**
 * Project: Epsilon
 * Date: 10/8/15
 * Time: 8:59 PM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Document;

defined("EPSILON_EXEC") or die();

use App\eConfig;
use Epsilon\Factory;
use Epsilon\Object\Object;

/**
 * Class Document
 *
 * @package Epsilon\Document
 */
class Document extends Object
{
    protected static $Instance;
    public           $Title;
    public           $SubTitle;
    public           $StyleSheets;
    public           $JavaScripts;
    public           $Positions;

    /**
     * Construct Method
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->StyleSheets = [];
        $this->JavaScripts = [];
        $this->Positions   = [];
    }

    /**
     * @return Document
     */
    public static function getInstance()
    {
        if (!isset(self::$Instance)) {
            self::$Instance = new Document();
        }

        return self::$Instance;
    }

    /**
     * Set itself to Smarty Variables as a reference
     * Any change made the Document Object during the execution of the application will also be applied in the smarty variable
     * Set the Document Title which will be displayed in the <title> tag of the HTML
     * Call method setTemplate
     */
    public function initialize()
    {
        $this->setTitle(eConfig::SITE_NAME);
        $this->setTemplate();
    }

    /**
     * Display the Template
     */
    public function render()
    {
        Factory::getTemplate()->display();
    }

    /**
     * @param string $Title
     */
    public function setTitle($Title)
    {
        $this->Title = $Title;
    }

    /**
     * @param string $SubTitle
     */
    public function setSubTitle($SubTitle)
    {
        $this->SubTitle = $SubTitle;
    }

    /**
     * Load the template
     * set available positions of the template to the document
     * set the Javascript and Cascade Style Sheets if there's not an XML HTTP Request
     */
    public function setTemplate()
    {
        $eApplication = Factory::getApplication();
        $Template     = Factory::getTemplate();
        $ContentType  = $eApplication->get("ContentType");
        $XHRequest    = $eApplication->get("XHRequest");

        if ($ContentType == "text/html" && !$XHRequest) {
            foreach ($Template->getPositions() as $p) {
                $this->setPosition($p);
            }

            foreach ($Template->getJavaScripts() as $v) {
                $this->setJavaScript($v);
            }

            foreach ($Template->getStyleSheets() as $v) {
                $this->setStyleSheet($v);
            }
        } else {
            $this->setPosition("XHRequest");
        }
    }

    /**
     * Check if a Position exist
     *
     * @param string $Position
     * @return bool
     */
    public function checkPosition($Position)
    {
        return array_key_exists($Position, $this->Positions) ? true : false;
    }

    /**
     * set the available positions to the Document
     *
     * @param string $Position
     */
    public function setPosition($Position)
    {
        if (is_array($Position)) {
            foreach ($Position as $p) {
                $this->setPosition($p);
            }
        } elseif (!$this->checkPosition($Position)) {
            $this->Positions[$Position] = [];
        }
    }

    /**
     * Set the \Epsilon\MVC\View Objects to their corresponding position
     *
     * @param string $Position
     * @param mixed  $View
     */
    public function setInPosition($Position, $View)
    {
        if ($this->checkPosition($Position)) {
            array_push($this->Positions[$Position], $View);
        }
    }

    /**
     * get \Epsilon\MVC\View Objects from a specific position
     *
     * @param string $Position
     * @return array
     */
    public function getByPosition($Position)
    {
        if ($this->checkPosition($Position)) {
            return $this->Positions[$Position];
        } else {
            return [];
        }
    }

    /**
     * Count \Epsilon\MVC\View Objects
     *
     * @param $Position
     * @return int
     */
    public function countByPosition($Position)
    {
        return count($this->getByPosition($Position));
    }

    /**
     * Set the js to the document to be used
     *
     * @param string $File the URL of the File
     */
    public function setJavaScript($File)
    {
        array_push($this->JavaScripts, $File);
    }

    /**
     * Set the css to the document to be used
     *
     * @param string $File the URL of the File
     */
    public function setStyleSheet($File)
    {
        array_push($this->StyleSheets, $File);
    }
}
