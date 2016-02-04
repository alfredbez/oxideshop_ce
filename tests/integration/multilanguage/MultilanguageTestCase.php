<?php
/**
 * This file is part of OXID eShop Community Edition.
 *
 * OXID eShop Community Edition is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eShop Community Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2016
 * @version   OXID eShop CE
 */

use OxidEsales\TestingLibrary\UnitTestCase;

require_once realpath(dirname(__FILE__)) . '/helpers/LanguageMainHelper.php';

abstract class MultilanguageTestCase extends UnitTestCase
{
    /**
     * Original tables and fields.
     *
     * @var array
     */
    protected $originalTables = array();
    protected $originalFields = array();

    /**
     * Fixture setUp.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->prepareDatabase();
        $this->setTestLanguageConfiguration();
        $this->updateViews();
    }

    /**
     * Fixture tearDown.
     */
    protected function tearDown()
    {
        $this->setConfigParam('aMultiLangTables', array());
        $this->restoreDatabase();

        parent::tearDown();
    }

    /**
     * Test helper for test preparation.
     * Add given count of new languages.
     *
     * @param $count
     *
     * @return int
     */
    protected function prepare($count = 9)
    {
        for ($i=0;$i<$count;$i++) {
            $languageAbbreviation = chr(97+$i) . chr(97+$i);
            $this->insertLanguage($languageAbbreviation);
        }
        //we need a fresh instance of language object in registry,
        //otherwise stale data is used for language abbreviations.
        oxRegistry::set('oxLang', null);
        oxRegistry::set('oxTableViewNameGenerator', null);

        $this->updateViews();

        return $languageAbbreviation;
    }

    /**
     * Test helper to insert a new language with given id.
     *
     * @param $languageAbbreviation
     */
    protected function insertLanguage($languageAbbreviation)
    {
        $this->configureNewLanguage($languageAbbreviation);

        if (!$this->getLanguageMain()->_checkMultilangFieldsExistsInDb($languageAbbreviation)) {
            $this->getLanguageMain()->_addNewMultilangFieldsToDb($languageAbbreviation);
        }

    }

    /**
     * Test helper for saving language configuration.
     *
     * @param $languages
     */
    protected function storeLanguageConfiguration($languages, $defaultLanguage = 'de')
    {
        $this->getConfig()->saveShopConfVar('aarr', 'aLanguageParams', $languages['params']);
        $this->getConfig()->saveShopConfVar('aarr', 'aLanguages', $languages['lang']);
        $this->getConfig()->saveShopConfVar('arr', 'aLanguageURLs', $languages['urls']);
        $this->getConfig()->saveShopConfVar('arr', 'aLanguageSSLURLs', $languages['sslUrls']);
        $this->getConfig()->saveShopConfVar('str', 'sDefaultLang', $defaultLanguage);
    }

    /**
     * Test helder to trigger view update.
     */
    protected function updateViews()
    {
        $oMeta = oxNew('oxDbMetaDataHandler');
        $oMeta->updateViews();
    }

    /**
     * Getter for LanguageMainHelper proxy class.
     *
     * @return object
     */
    protected function getLanguageMain()
    {
        if (is_null($this->languageMain)) {
            $this->languageMain = $this->getProxyClass('LanguageMainHelper');
            $this->languageMain->render();
        }
        return $this->languageMain;
    }

    /**
     * Test helper to insert a new language with given id into language configuration.
     *
     * @param $languageId
     *
     * @return integer
     */
    protected function configureNewLanguage($languageId)
    {
        $languages = $this->getLanguageMain()->_getLanguages();
        $sort = (count($languages['lang']) + 1) * 100;

        $languages['params'][$languageId] = array('baseId' => $languageId,
                                                  'active' => 1,
                                                  'sort'   => $sort);

        $languages['lang'][$languageId] = $languageId;
        $languages['urls'][$languageId]     = '';
        $languages['sslUrls'][$languageId]  = '';
        $this->getLanguageMain()->setLanguageData($languages);

        $this->storeLanguageConfiguration($languages);

        oxRegistry::set('oxLang', null);
        oxRegistry::set('oxTableViewNameGenerator', null);
    }

    /**
     * Test helper to restore default language configuration.
     */
    protected function setTestLanguageConfiguration()
    {
        $languages = array(
            'params'  => array(
                'de' => array(
                    'baseId' => 'de',
                    'active' => 1,
                    'sort'   => 1
                ),
                'en' => array(
                    'baseId' => 'en',
                    'active' => 1,
                    'sort'   => 1
                ),
            ),
            'lang'    => array(
                'de' => 'Deutsch',
                'en' => 'Englisch'
            ),
            'urls'    => array(
                'de' => '',
                'en' => ''
            ),
            'sslUrls' => array(
                'de' => '',
                'en' => ''
            )
        );

        $this->storeLanguageConfiguration($languages, 'de');
    }

    /**
     * Create additional multilanguage table.
     *
     * @param string $name
     */
    protected function createTable($name = 'addtest')
    {
        $sql = "CREATE TABLE `" . $name . "` (" .
               "`OXID` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Item id'," .
               "`TITLE` varchar(128) NOT NULL DEFAULT '' COMMENT 'Title (multilanguage)'," .
               "`TITLE_DE` varchar(128) NOT NULL DEFAULT ''," .
               "PRIMARY KEY (`OXID`)" .
               ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='for testing'";

        oxDb::getDb()->query($sql);
        oxDb::getInstance()->getTableDescription($name); //throws exception if table does not exist
        $this->additionalTables[] = $name;
    }

    /**
     * Remove additional multilanguage tables and related.
     *
     * @return null
     */
    protected function removeAdditionalTables($name)
    {
        $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_NAME LIKE '%" . $name . "%'";
        $result = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getArray($sql);
        foreach ($result as $sub) {
            oxDb::getDb()->query("DROP TABLE IF EXISTS `" . $sub['TABLE_NAME'] . "`");
        }
    }

    /**
     * Restore database to whatever state it was in at beginning of this test.
     */
    protected function restoreDatabase()
    {
        $dbMetaDataHandler = oxNew('oxDbMetaDataHandler');
        $allTables = $dbMetaDataHandler->getAllTables();
        $excessTables = array_diff($allTables, $this->originalTables);

        foreach ($excessTables as $table) {
            $query = "DROP TABLE " . $table;
            oxDb::getDb()->execute($query);
        }

        foreach ($this->originalTables as $table) {
            $fields = array_keys($dbMetaDataHandler->getFields($table));
            $excessFields = array_diff($fields, $this->originalFields[$table]);

            if (!empty($excessFields)) {
                $query = "ALTER TABLE $table DROP COLUMN " . implode(', DROP COLUMN ', $excessFields);
                oxDb::getDb()->execute($query);
            }
        }
    }

    /**
     * Restore database to whatever state it was in at beginning of this test.
     */
    protected function prepareDatabase()
    {
        $dbMetaDataHandler = oxNew('oxDbMetaDataHandler');
        $this->originalTables = $dbMetaDataHandler->getAllTables();

        foreach ($this->originalTables as $table) {
            $this->originalFields[$table] = array_keys($dbMetaDataHandler->getFields($table));
        }
    }

}

