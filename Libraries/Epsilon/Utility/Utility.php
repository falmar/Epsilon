<?php
/**
 * Project: Epsilon
 * Date: 6/21/15
 * Time: 11:54 AM
 *
 * @link      https://github.com/falmar/Epsilon
 * @author    David Lavieri (falmar) <daviddlavier@gmail.com>
 * @copyright 2015 David Lavieri
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Epsilon\Utility;

defined("EPSILON_EXEC") or die();

use App\Config;
use Epsilon\Factory;
use Epsilon\IO\Input;
use Epsilon\Object\Object;
use PDO;
use PDOException;

/**
 * Class Utility
 *
 * @package Epsilon\Utility
 */
class Utility
{

    /**
     * @param $Path
     * @return string
     */
    public static function getRelativePath($Path)
    {
        $URI    = Factory::getURI();
        $Filter = array_filter(explode("/", EPSILON_PATH));

        $nPath = array_pop($Filter);

        return $URI->getRelativePath() . substr($Path, strpos($Path, $nPath) + strlen($nPath) + 1, strlen($Path));

    }

    /**
     * @param PDO  $objPDO
     * @param      $fieldID
     * @param      $tableName
     * @param      $parentID
     * @param      $left
     * @param null $OrderBY
     * @return bool|int
     */
    public static function rebuildNest($objPDO, $fieldID, $tableName, $parentID, $left, $OrderBY = null)
    {

        if (is_null($parentID)) {
            $stmt = $objPDO->prepare("SELECT $fieldID FROM $tableName WHERE ParentID IS NULL ORDER BY ParentID $OrderBY");
        } else {
            $stmt = $objPDO->prepare("SELECT $fieldID FROM $tableName WHERE ParentID = :ParentID ORDER BY ParentID $OrderBY");
            $stmt->bindValue(":ParentID", $parentID, PDO::PARAM_STR);
        }

        $right = (int)$left + 1;

        try {
            $stmt->execute();

            foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $child) {
                $right = self::rebuildNest($objPDO, $fieldID, $tableName, $child->$fieldID, $right, $OrderBY);
                if ($right === false) {
                    return false;
                }
            }

            if (is_null($parentID)) {
                $stmt = $objPDO->prepare("UPDATE $tableName SET lft = $left,rgt = $right WHERE $fieldID IS NULL");
            } else {
                $stmt = $objPDO->prepare("UPDATE $tableName SET lft = $left,rgt = $right WHERE $fieldID = :ParentID");
                $stmt->bindValue(":ParentID", $parentID, PDO::PARAM_STR);
            }

            $stmt->execute();

        } catch (PDOException $e) {
            return false;
        }

        return $right + 1;

    }

    /**
     * @param string $Date
     * @param bool   $Time
     * @return null|string
     */
    public static function getDateForDB($Date = "NOW()", $Time = true)
    {
        if (is_null($Date) or $Date == "") {
            return null;
        }

        if ($Date == "NOW()") {
            if ($Time) {
                $Date = date("Y-m-d H:i:s");
            } else {
                $Date = date("Y-m-d");
            }
        } else {
            if (strpos($Date, "-") === false) {
                $Date = explode("/", $Date);
            } else {
                $Date = explode("-", $Date);
            }
        }

        if (is_array($Date) && count($Date) == 3) {
            $Date = $Date[2] . "-" . $Date[1] . "-" . $Date[0];
        }

        return $Date;
    }

    /**
     * @param      $Results
     * @param      $ElementID
     * @param null $MaxPageSize
     * @return \Epsilon\Object\Object
     */
    public static function foundationListPaging($Results, $ElementID, $MaxPageSize = null)
    {
        $CurrentPage = Input::getVar("CurrentPage", "REQUEST");

        if (is_integer($MaxPageSize)) {
            $PageSize = $MaxPageSize;
        } else {
            $PageSize = Config::MAX_PAGE_SIZE;
        }

        $Paging = new Object([
            'HTML'         => null,
            'Init'         => 0,
            'Max'          => $PageSize,
            'Results'      => 0,
            'PageInit'     => 0,
            'PageMax'      => 0,
            'TotalPages'   => 0,
            'TotalResults' => 0
        ]);

        if (is_array($Results)) {
            $TotalResults = count($Results);
        } else {
            if (is_numeric($Results)) {
                $TotalResults = $Results;
            } else {
                $TotalResults = 0;
            }
        }

        if ($TotalResults > 0) {


            $TotalPages = self::getTotalPages($TotalResults, $PageSize);

            if ($CurrentPage > $TotalPages) {
                $CurrentPage = $TotalPages;
            } elseif (!is_numeric($CurrentPage) || !((int)$CurrentPage > 0) || ((int)$CurrentPage) == 0) {
                $CurrentPage = 1;
            }

            if ($CurrentPage > 1) {
                $Init     = ($CurrentPage - 1) * $PageSize;
                $Max      = $PageSize;
                $PageInit = ($CurrentPage - 1) * ($PageSize) + 1;
                $PageMax  = ($PageInit + $PageSize - 1);
            } else {
                $Init     = 0;
                $Max      = $PageSize;
                $PageInit = 1;
                $PageMax  = ($PageInit + $Max) - 1;
            }

            if ($PageMax >= $TotalResults) {
                $PageMax = $TotalResults;
            }

            $Pages = self::getPages($CurrentPage, $TotalPages);

            $HTML = "";

            $PreviousSet = false;

            if (is_string($ElementID)) {
                $ElementID = "\"$ElementID\"";
            } else {
                $ElementID = "null";
            }

            $eLanguage = Factory::getLanguage();

            $PagesCount = 0;

            foreach ($Pages as $p) {

                $PagesCount++;

                if (!$PreviousSet) {
                    $Previus = $CurrentPage - 1;
                    if ($CurrentPage != 1) {
                        $HTML .= "<ul class=\"pagination text-center\" role=\"navigation\" aria-label=\"Pagination\">\n<li class=\"pagination-previous\"><a href='javascript:goToPage($Previus, $ElementID)'><span class=\"show-for-sr\">page</span> " . $eLanguage->_("LIST-RESULT-PREVIOUS") . "</a></li>\n";
                    } else {
                        $HTML .= "<ul class=\"pagination text-center\" role=\"navigation\" aria-label=\"Pagination\">\n<li class=\"pagination-previous disabled\"><span class=\"show-for-sr\">page</span> " . $eLanguage->_("LIST-RESULT-PREVIOUS") . "</li>\n";
                    }
                    $PreviousSet = true;
                }

                if ($CurrentPage == $p) {
                    $HTML .= "<li class='current'><span class=\"show-for-sr\">You're on page </span > $p</li > \n";
                } else {
                    $HTML .= " <li><a href = 'javascript:goToPage($p,$ElementID)' > $p</a ></li > \n";
                }

                if ($PagesCount == count($Pages)) {
                    $Next = $CurrentPage + 1;
                    if ($CurrentPage != $p) {
                        $HTML .= "<li class=\"pagination-next\"><a aria-label=\"Next page\" href='javascript:goToPage($Next,$ElementID)'>" . $eLanguage->_("LIST-RESULT-NEXT") . " <span class=\"show-for-sr\">page</span></a></li></ul>";
                    } else {
                        $HTML .= "<li class=\"pagination-next disabled\"><span class=\"show-for-sr\">page</span>" . $eLanguage->_("LIST-RESULT-NEXT") . "</li>\n</ul>";
                    }
                }

            }

            $Paging->setProperties([
                'HTML'         => $HTML,
                'Init'         => $Init,
                'Max'          => $Max,
                'Results'      => ($PageMax - $PageInit) + 1,
                'PageInit'     => $PageInit,
                'PageMax'      => $PageMax,
                'TotalPages'   => $TotalPages,
                'TotalResults' => $TotalResults
            ]);

        }

        return $Paging;
    }

    /**
     * @param $CurrentPage
     * @param $TotalPages
     * @return array
     */
    protected static function getPages($CurrentPage, $TotalPages)
    {
        if ($TotalPages > 0) {
            if (($CurrentPage - 3) > 0 && ($CurrentPage + 3) <= $TotalPages) {
                $Pages = range($CurrentPage - 3, $CurrentPage + 3);
            } else {
                if ($TotalPages > 7) {
                    if ($CurrentPage - 3 <= 0) {
                        $Pages = range(1, 7);
                    } else {
                        $Pages = range($TotalPages - 6, $TotalPages);
                    }
                } else {
                    $Pages = range(1, $TotalPages);
                }
            }
        } else {
            $Pages = [];
        }

        return $Pages;
    }

    /**
     * @param $TotalResults
     * @param $PageSize
     * @return float|int
     */
    protected static function getTotalPages($TotalResults, $PageSize)
    {
        if ($TotalResults > 0) {
            if ($TotalResults < $PageSize) {
                $TotalPages = 1;
            } elseif (($TotalResults % $PageSize) > 0) {
                $TotalPages = floor($TotalResults / $PageSize) + 1;
            } else {
                $TotalPages = $TotalResults / $PageSize;
            }
        } else {
            $TotalPages = 0;
        }

        return $TotalPages;
    }

    /**
     * @param            $String
     * @param bool|false $HTML_Decode
     * @param string     $Prefix
     * @param string     $Postfix
     * @return string
     */
    public static function getSlug($String, $HTML_Decode = false, $Prefix = '', $Postfix = '')
    {
        return implode('-', preg_split('/\s+/', strtolower($Prefix . self::transliterateString($String, $HTML_Decode) . $Postfix)));
    }

    /**
     * @author Edgar Zagórski
     * @param string $txt
     * @param bool   $html_decode
     * @return mixed
     */

    public static function transliterateString($txt, $html_decode = false)
    {
        //@formatter:off
        if ($html_decode) {
            $txt = html_entity_decode($txt, ENT_QUOTES, "UTF-8");
        }
        $transliterationTable = ["#" => "lel", '&' => '', 'á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'ae', 'Ä' => 'AE', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'oe', 'Ø' => 'OE', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'oe', 'Ö' => 'OE', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'ue', 'Ü' => 'UE', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja'];

        return preg_replace('/[^A-Za-z0-9_\@\.\s\-\ñ\á\ó\é\ú\í]/', '', str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt));
        //@formatter:on
    }

}
