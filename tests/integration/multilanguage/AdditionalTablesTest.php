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
 * @copyright (C) OXID eSales AG 2003-2015
 * @version   OXID eShop CE
 */

require_once 'MultilanguageTestCase.php';

class Integration_Multilanguage_AdditionalTablesTest extends MultilanguageTestCase
{
    /**
     * Assert that set tables are automatically created for additional multilanguage table
     * in case we add first the table and then create the languages.
     */
    public function testCreateLanguagesAfterAdditionalTable()
    {
        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        //add nine more languages
        $this->prepare(9);

        $dbMetaDataHandler = oxNew('oxDbMetaDataHandler');
        $this->assertTrue($dbMetaDataHandler->tableExists('addtest_set1'));

        //Make sure the multilanguage fields have the same order (and so the same set tables) as the core fields.
        //Not relevant for functionality but it is more tidy.
        $expected = array_keys($dbMetaDataHandler->getLanguage2TableSetMap('oxarticles', 'oxtitle'));
        $this->assertSame($expected, array_keys($dbMetaDataHandler->getLanguage2TableSetMap('addtest', 'title')));

    }

    /**
     * Assert that set tables are automatically created for additional multilanguage table
     * in case first create the languages, then set the table in config.inc.php variable 'aMultiLangTables'
     * and call updateViews. Without *_set1 tables, view creating throws and exception.
     */
    public function testCreateAdditionalTableAfterCreatingLanguages()
    {
        //add nine more languages
        $this->prepare(9);

        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        $this->updateViews();

        $dbMetaDataHandler = oxNew('oxDbMetaDataHandler');
        $this->assertTrue($dbMetaDataHandler->tableExists('addtest_set1'));

        //Make sure the multilanguage fields have the same order (and so the same set tables) as the core fields.
        //Not relevant for functionality but it is more tidy.
        $expected = array_keys($dbMetaDataHandler->getLanguage2TableSetMap('oxarticles', 'oxtitle'));
        $this->assertSame($expected, array_keys($dbMetaDataHandler->getLanguage2TableSetMap('addtest', 'title')));

    }

    /**
     * Verify that the expected data turned up in the language views
     */
    public function testViewContentsCreateLanguagesAfterAdditionalTable()
    {
        $oxid = '_test101';

        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        //add nine more languages
        $languageId = $this->prepare(9);

        //insert testdata for default language
        $defaultLanguage = $this->getConfig()->getConfigParam('sDefaultLang');
        $sql = "INSERT INTO addtest (OXID, TITLE_" . $defaultLanguage . ") VALUES ('" . $oxid . "', 'some default title')";
        oxDb::getDb()->query($sql);

        //insert testdata for last added language id in set1 table
        $sql = "INSERT INTO addtest_set1 (OXID, TITLE_" . $languageId . ") VALUES ('" . $oxid . "', 'some additional title')";
        oxDb::getDb()->query($sql);

        $sql = "SELECT TITLE FROM " . getViewName('addtest', $languageId) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some additional title', oxDb::getDb()->getOne($sql));

        $sql = "SELECT TITLE FROM " . getViewName('addtest', $defaultLanguage) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some default title', oxDb::getDb()->getOne($sql));

    }

    /**
     * Verify that the expected data turned up in the language views
     */
    public function testViewContentsCreateAdditionalTableAfterCreatingLanguages()
    {
        //add nine more languages
        $languageId = $this->prepare(9);
        $oxid = '_test101';

        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        $this->updateViews();

        //insert testdata for default language
        $defaultLanguage = $this->getConfig()->getConfigParam('sDefaultLang');
        $sql = "INSERT INTO addtest (OXID, TITLE_" . $defaultLanguage . ") VALUES ('" . $oxid . "', 'some default title')";
        oxDb::getDb()->query($sql);

        //insert testdata for last added language id in set1 table
        $sql = "INSERT INTO addtest_set1 (OXID, TITLE_" . $languageId . ") VALUES ('" . $oxid . "', 'some additional title')";
        oxDb::getDb()->query($sql);

        $sql = "SELECT TITLE FROM " . getViewName('addtest', $languageId) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some additional title', oxDb::getDb()->getOne($sql));

        $sql = "SELECT TITLE FROM " . getViewName('addtest', $defaultLanguage) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some default title', oxDb::getDb()->getOne($sql));

    }

    /**
     * Verify that the expected data turned up in the language views
     */
    public function testViewContentsCreateAdditionalTableAfterCreatingLanguageIdWithUnderscore()
    {
        //add nine more languages
        $this->prepare(8);
        $languageId = 'xy_yx';
        $this->insertLanguage($languageId);
        $oxid = '_test101';

        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        $this->updateViews();

        //insert testdata for default language
        $defaultLanguage = $this->getConfig()->getConfigParam('sDefaultLang');
        $sql = "INSERT INTO addtest (OXID, TITLE_" . $defaultLanguage . ") VALUES ('" . $oxid . "', 'some default title')";
        oxDb::getDb()->query($sql);

        //insert testdata for last added language id in set1 table
        $sql = "INSERT INTO addtest_set1 (OXID, TITLE_" . $languageId . ") VALUES ('" . $oxid . "', 'some additional title')";
        oxDb::getDb()->query($sql);

        $sql = "SELECT TITLE FROM " . getViewName('addtest', $languageId) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some additional title', oxDb::getDb()->getOne($sql));

        $sql = "SELECT TITLE FROM " . getViewName('addtest', $defaultLanguage) . " WHERE OXID = '" . $oxid . "'";
        $this->assertSame('some default title', oxDb::getDb()->getOne($sql));

    }
}

