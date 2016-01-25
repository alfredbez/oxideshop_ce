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

require_once 'MultilanguageTestCase.php';

class Integration_Multilanguage_NonNumericLanguageIdsTest extends MultilanguageTestCase
{

    /**
     * Test getting language table names.
     */
    public function testFunctionGetLanguageTableNames()
    {
        $this->assertEquals('oxarticles', getLangTableName('oxarticles', 0));
        $this->assertEquals('oxarticles', getLangTableName('oxarticles', 1));
        $this->assertEquals('oxarticles', getLangTableName('oxarticles', 7));
        $this->assertEquals('oxarticles_set1', getLangTableName('oxarticles', 8));
        $this->assertEquals('oxarticles_set1', getLangTableName('oxarticles', 15));
        $this->assertEquals('oxarticles_set2', getLangTableName('oxarticles', 16));
        $this->assertEquals('oxarticles_set4', getLangTableName('oxarticles', 32));
    }

    /**
     * Test language to set table map.
     */
    public function testDbMetaDataHandlerGetLanguage2TableSetMap()
    {
        $this->createTestTables('addtest');
        $this->setTestLanguages();

        $expected = array( 'de' => 'addtest',
                           'en' => 'addtest',
                           'aa' => 'addtest',
                           'bb' => 'addtest',
                           'cc' => 'addtest',
                           'dd' => 'addtest',
                           'ee' => 'addtest',
                           'ff' => 'addtest',
                           'gg' => 'addtest_set1',
                           'hh' => 'addtest_set1',
                           'ii' => 'addtest_set1',
                           'jj' => 'addtest_set1',
                           'kk' => 'addtest_set1',
                           'll' => 'addtest_set1',
                           'mm' => 'addtest_set1',
                           'nn' => 'addtest_set1',
                           'oo' => 'addtest_set2',
                           'pp' => 'addtest_set2',
                           'qq' => 'addtest_set2' );

        $metaDataHandler = oxNew('oxDbMetaDataHandler');
        $this->assertSame($expected, $metaDataHandler->getLanguage2TableSetMap('addtest', 'title'));

    }

    /**
     * Test getting existing set tables.
     */
    public function testDbMetaDataHandlerGetLanguageSetTables()
    {
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $expected = array('addtest');
        $this->assertSame($expected, $metaDataHandler->getLanguageSetTables('addtest'));

        $this->createTestTables('addtest');
        $this->setTestLanguages();

        $expected = array('addtest', 'addtest_set1', 'addtest_set2');
        $this->assertSame($expected, $metaDataHandler->getLanguageSetTables('addtest'));
    }

    /**
     * Test determining table set next language's fields should be inserted.
     * Case next language has to be added to a new set.
     */
    public function testDbMetaDataHandlerGetNextLanguageSetTable()
    {
        $this->createTestTables('addtest');
        $this->setTestLanguages();
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $this->assertSame('addtest_set3', $metaDataHandler->getNextLanguageSetTable('addtest'));
    }

    /**
     * Test getting the correct table set for given language abbreviation.
     */
    public function testDbMetaDataHandlerGetTableSetForLanguageAbbreviation()
    {
        $this->createTestTables('addtest');
        $this->setTestLanguages();
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $this->assertSame('oxarticles', $metaDataHandler->getTableSetForLanguageAbbreviation('en'));
        $this->assertSame('addtest', $metaDataHandler->getTableSetForLanguageAbbreviation('de', 'addtest'));
        $this->assertSame('addtest', $metaDataHandler->getTableSetForLanguageAbbreviation('en', 'addtest'));
        $this->assertSame('addtest_set1', $metaDataHandler->getTableSetForLanguageAbbreviation('hh', 'addtest'));
        $this->assertSame('addtest_set2', $metaDataHandler->getTableSetForLanguageAbbreviation('qq', 'addtest'));

    }

    /**
     * Test determining table set next language's fields should be inserted.
     * Case next language will be appended to last table set.
     */
    public function testDbMetaDataHandlerGetNextFreeSlotInTableSetAppend()
    {
        $this->createTestTables('addtest');
        $this->setTestLanguages();
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $this->assertSame('addtest_set2', $metaDataHandler->getNextFreeSlotInTableSet('addtest', 'title'));
    }

    /**
     * Test determining table set next language's fields should be inserted.
     * Case next language can be added in a set with a free slot.
     */
    public function testDbMetaDataHandlerGetNextFreeSlotInTableSetFillEmptySlot()
    {
        $this->createTestTables('addtest', 'title', true);
        $this->setTestLanguages(true);
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $this->assertSame('addtest_set1', $metaDataHandler->getNextFreeSlotInTableSet('addtest', 'title'));
    }

    /**
     * Test determining table set next language's fields should be inserted.
     * Case next language has to be added to a new set.
     */
    public function testDbMetaDataHandlerGetNextFreeSlotInTableSetUseNewTableSet()
    {
        $this->createTestTables('addtest', 'title', false, true);
        $this->setTestLanguages(false, true);
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $this->assertSame('addtest_set2', $metaDataHandler->getNextFreeSlotInTableSet('addtest', 'title'));
    }

    /**
     * Test getting last inserted multilanguage field.
     */
    public function testDbMetaDataHandlerGetPreviousMultilanguageField()
    {
        $this->createTestTables('addtest', 'title');
        $this->setTestLanguages();

        $metaDataHandler = oxNew('oxDbMetaDataHandler');
        $this->assertSame('TITLEXXX_ff', $metaDataHandler->getPreviousMultilanguageField('addtest', 'titleXXX'));
        $this->assertSame('TITLEXXX_ff', $metaDataHandler->getPreviousMultilanguageField('addtest', 'TITLEXXX'));
        $this->assertSame('TITLE_ff', $metaDataHandler->getPreviousMultilanguageField('addtest', 'TITLE'));
        $this->assertSame('TITLEXXX_nn', $metaDataHandler->getPreviousMultilanguageField('addtest_set1', 'titleXXX'));
        $this->assertSame('TITLEXXX_nn', $metaDataHandler->getPreviousMultilanguageField('addtest_set1', 'TITLEXXX'));
        $this->assertSame('TITLEXXX_qq', $metaDataHandler->getPreviousMultilanguageField('addtest_set2', 'titleXXX'));
        $this->assertSame('TITLE_qq', $metaDataHandler->getPreviousMultilanguageField('addtest_set2', 'TITLE'));
        $this->assertNull($metaDataHandler->getPreviousMultilanguageField('addtest_set2', 'YYY'));
        $this->assertNull($metaDataHandler->getPreviousMultilanguageField('notexistingtable', 'YYY'));
    }

    /**
     * Test getting oxarticles table's multilanguage fields.
     */
    public function testDbMetaDataHandlerGetMultilangFieldsOxArticles()
    {
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $expected = array('OXVARNAME',
                          'OXVARSELECT',
                          'OXTITLE',
                          'OXSHORTDESC',
                          'OXURLDESC',
                          'OXSEARCHKEYS',
                          'OXSTOCKTEXT',
                          'OXNOSTOCKTEXT');

        $this->assertSame($expected, $metaDataHandler->getMultilangFields('oxarticles'));

    }

    /**
     * Test getting additional test table's multilanguage fields.
     */
    public function testDbMetaDataHandlerGetMultilangFieldsAdditionalTable()
    {
        $this->createTestTables('addtest', 'title');
        $this->setTestLanguages();
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $expected = array('TITLE',
                          'TITLEXXX');
        $this->assertSame($expected, $metaDataHandler->getMultilangFields('addtest'));
    }

    /**
     * Test ensuring multi language fields are created.
     * Case field in question already exists.
     */
    public function testDbMetaDataHandlerEnsureMultiLanguageFieldsForExistingField()
    {
        $this->createTestTables('addtest', 'title');
        $this->setTestLanguages();

        $metaDataHandler = oxNew('oxDbMetaDataHandler');
        $this->assertTrue($metaDataHandler->fieldExists('title_qq', 'addtest_set2'));

        $metaDataHandler->ensureMultiLanguageFields('addtest', 'qq');
        $this->assertTrue($metaDataHandler->fieldExists('title_qq', 'addtest_set2'));
    }

    /**
     * Test ensuring multi language fields are created.
     * Case field in question needs to be created.
     */
    public function testDbMetaDataHandlerEnsureMultiLanguageFieldsNewField()
    {
        $this->createTestTables('addtest', 'title');
        $this->setTestLanguages();

        $metaDataHandler = oxNew('oxDbMetaDataHandler');
        $this->assertFalse($metaDataHandler->fieldExists('title_rr', 'addtest_set2'));
        $this->assertSame('addtest_set2', $metaDataHandler->getTableSetForLanguageAbbreviation('rr', 'addtest'));

        $metaDataHandler->ensureMultiLanguageFields('addtest', 'rr');
        $this->assertTrue($metaDataHandler->fieldExists('title_rr', 'addtest_set2'));

        //check if fields were generated in the desired order
        $expected = array(
            'OXID'        => 'addtest_set2.OXID',
            'TITLE_oo'    => 'addtest_set2.TITLE_oo',
            'TITLE_pp'    => 'addtest_set2.TITLE_pp',
            'TITLE_qq'    => 'addtest_set2.TITLE_qq',
            'TITLE_rr'    => 'addtest_set2.TITLE_rr',
            'TITLEXXX_oo' => 'addtest_set2.TITLEXXX_oo',
            'TITLEXXX_pp' => 'addtest_set2.TITLEXXX_pp',
            'TITLEXXX_qq' => 'addtest_set2.TITLEXXX_qq',
            'TITLEXXX_rr' => 'addtest_set2.TITLEXXX_rr'
        );

        $this->assertSame($expected, $metaDataHandler->getFields('addtest_set2'));
    }

    /**
     * Test field language check.
     */
    public function testDbMetaDataHandlerDoesFieldLanguageMatch()
    {
        $dbMetaDataHandler = $this->getProxyClass('oxDbMetaDataHandler');
        $this->assertTrue($dbMetaDataHandler->doesFieldLanguageMatch('oxarticles_set1.oxtitle_de', 'de'));
        $this->assertFalse($dbMetaDataHandler->doesFieldLanguageMatch('oxarticles.oxtitle_xy_yx', 'xy'));
        $this->assertFalse($dbMetaDataHandler->doesFieldLanguageMatch('oxarticles.oxtitle_xy_yx', 'yx'));
        $this->assertTrue($dbMetaDataHandler->doesFieldLanguageMatch('oxarticles.oxtitle_xy_yx', 'xy_yx'));
    }

    /*
     * Test generating view names.
     */
    public function testViewNameGeneratorViewNames()
    {
        $language             = oxNew('oxLang');
        $languageAbbreviation = $language->getLanguageAbbr('de');
        $this->assertEquals('de', $languageAbbreviation);

        $viewNameGenerator = oxRegistry::get('oxTableViewNameGenerator');
        $viewName = $viewNameGenerator->getViewName('oxshops', 'de', 'oxbaseshop');
        $this->assertEquals('oxv_oxshops_de', $viewName);
    }

    /**
     * Test getting single language fields
     */
    public function testDbMetaDataHandlerGetSingleLanguageFields()
    {
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        //core table
        $fields = $metaDataHandler->getSinglelangFields('oxshops', 'de');
        $this->assertFalse(isset($fields['OXTITLEPREFIX_fr']));
        $this->assertSame($fields['OXTITLEPREFIX'], 'oxshops.OXTITLEPREFIX_de');

        //generic view
        $fields = $metaDataHandler->getSinglelangFields('oxv_oxshops', 'de');
        $this->assertSame($fields['OXTITLEPREFIX'], 'oxv_oxshops.OXTITLEPREFIX_de');

        //language specific view
        $fields = $metaDataHandler->getSinglelangFields('oxv_oxshops_de', 'de');
        $this->assertSame($fields['OXTITLEPREFIX'], 'oxv_oxshops_de.OXTITLEPREFIX');

        //core table
        $fields = $metaDataHandler->getSinglelangFields('oxshops', 'en');
        $this->assertSame($fields['OXTITLEPREFIX'], 'oxshops.OXTITLEPREFIX_en');

        //generic view
        $fields = $metaDataHandler->getSinglelangFields('oxv_oxshops', 'en');
        $this->assertSame($fields['OXTITLEPREFIX'], 'oxv_oxshops.OXTITLEPREFIX_en');

        //language specific view
        $fields = $metaDataHandler->getSinglelangFields('oxv_oxshops_en', 'en');
        $this->assertSame($fields['OXTITLEPREFIX'], 'oxv_oxshops_en.OXTITLEPREFIX');

    }

    /**
     * Test multilingual field status.
     */
    public function testShopMultilingualFieldStatus()
    {
        $shop = $this->getProxyClass('oxShop');
        $this->assertEquals('oxv_oxshops_de', $shop->getViewName());
        $this->assertSame(0, $shop->_getFieldStatus('oxcountry'));
        $this->assertSame(1, $shop->_getFieldStatus('oxregistersubject'));
        $this->assertSame(1, $shop->_getFieldStatus('oxregistersubject_de'));
    }

    /**
     * Test if oxshop object works.
     */
    public function testShopLoadMultilingualObject()
    {
        $shop = $this->getProxyClass('oxShop');

        $this->assertNull($shop->_getFieldLang('oxtitleprefix'));
        $this->assertSame('de', $shop->_getFieldLang('oxtitleprefix_de'));

        $selectFields = $shop->getSelectFields();
        $this->assertFalse(strpos($selectFields, '_fr'));

        $shop->load('oxbaseshop');
        $this->assertSame('Ihre Bestellung bei OXID eSales', $shop->oxshops__oxordersubject->value);
    }

    /**
     * Test if oxshop object works.
     */
    public function testShopSaveMultilingualObject()
    {
        $shop = $this->getProxyClass('oxShop');
        $shop->load('oxbaseshop');
        $this->assertSame('Ihre Bestellung bei OXID eSales', $shop->oxshops__oxordersubject->value);

        $shop->oxshops__oxordersubject = new oxField('test text');
        $shop->save('test text');
        $shop->load('oxbaseshop');
        $this->assertSame('test text', $shop->oxshops__oxordersubject->value);

        $query = "SELECT oxordersubject, oxordersubject_de, oxordersubject_en FROM oxshops WHERE oxid = 'oxbaseshop'";
        $result = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)->getArray($query);

        $expected = array( 'oxordersubject'    => '',
                           'oxordersubject_de' => 'test text',
                           'oxordersubject_en' => 'Your order at OXID eShop' );

        $this->assertSame($expected, $result[0]);
    }

    /**
     * Test if oxshop object works.
     */
    public function testShopLoadMultilingualObjectInDifferentLanguage()
    {
        $shop = $this->getProxyClass('oxShop');

        $shop->loadInLang('en','oxbaseshop');
        $this->assertSame('Your order at OXID eShop', $shop->oxshops__oxordersubject->value);
    }

    /**
     * Test multilanguage field exists check in language_main.
     */
    public function testLanguageMainMultiLanguageFieldExists()
    {
        $languageMain = $this->getLanguageMain();
        $this->assertTrue(($languageMain->_checkMultilangFieldsExistsInDb('de')));
        $this->assertTrue(($languageMain->_checkMultilangFieldsExistsInDb('fr')));
        $this->assertFalse(($languageMain->_checkMultilangFieldsExistsInDb('aa')));
    }

    /**
     * Test generating views.
     */
    public function testDbMetaDataHandlerUpdateViews()
    {
        $newLanguage = 'aa';
        $this->configureNewLanguage($newLanguage);

        $dbMetaDataHandler = oxNew("oxDbMetaDataHandler");
        $this->assertEquals('oxarticles', $dbMetaDataHandler->getTableSetForLanguageAbbreviation($newLanguage, 'oxarticles'));

        $dbMetaDataHandler->ensureMultiLanguageFields('oxarticles', 'aa');
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle_aa', 'oxarticles'));

        $dbMetaDataHandler->updateViews();

        $this->assertFalse($dbMetaDataHandler->tableExists('oxarticles_set1'));
        $this->assertTrue($dbMetaDataHandler->tableExists('oxv_oxarticles_aa'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle_aa', 'oxarticles'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle_aa', 'oxv_oxarticles'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle', 'oxv_oxarticles_aa'));

    }

    /**
     * Test adding a brand new language.
     */
    public function testLanguageMainAddNewMultilanguageFieldsToDb()
    {
        $newLanguage = 'aa';
        $this->configureNewLanguage($newLanguage);

        $languageMain = $this->getLanguageMain();
        $this->assertFalse(($languageMain->_checkMultilangFieldsExistsInDb('aa')));

        $languageMain->_addNewMultilangFieldsToDb($newLanguage);

        $dbMetaDataHandler = oxNew("oxDbMetaDataHandler");
        $this->assertTrue($dbMetaDataHandler->tableExists('oxv_oxarticles_aa'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle_aa', 'oxarticles'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle_aa', 'oxv_oxarticles'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle', 'oxv_oxarticles_aa'));
    }

    /**
     * Test adding a brand new language like it would be done via shop admin.
     */
    public function testLanguageMainAddNewLanguage()
    {
        $languageMain = oxNew('language_main');

        $parameters = array(
            'oxid'       => '-1',
            'active'     => '1',
            'abbr'       => 'aa',
            'desc'       => 'AAA',
            'baseurl'    => '',
            'basesslurl' => '',
            'sort'       => ''
        );

        $this->setRequestParameter('oxid', '-1');
        $this->setRequestParameter('editval', $parameters);
        $languageMain->save();

        $dbMetaDataHandler = oxNew("oxDbMetaDataHandler");
        $dbMetaDataHandler->updateViews();

        $this->assertFalse($dbMetaDataHandler->tableExists('oxarticles_set1'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle_aa', 'oxarticles'));
        $this->assertTrue($dbMetaDataHandler->tableExists('oxv_oxarticles_aa'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle_aa', 'oxv_oxarticles'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle', 'oxv_oxarticles_aa'));
    }

    /**
     * Test changing a language abbreviation.
     */
    public function testLanguageMainForbiddenToChangeLanguageAbbreviation()
    {
        $expectedException = oxNew('oxExceptionToDisplay');
        $expectedException->setMessage('LANGUAGE_ABBRCHANGELANG_WARNING');

        $utilsView = $this->getMock('oxUtilsView', array('addErrorToDisplay'));
        $utilsView->expects($this->once())->method('addErrorToDisplay')->with($this->equalTo($expectedException));
        oxRegistry::set('oxUtilsView', $utilsView);

        $languageMain = oxNew('language_main');

        $parameters = array(
            'oxid'       => 'de',
            'active'     => '1',
            'abbr'       => 'zz',
            'desc'       => 'German',
            'baseurl'    => '',
            'basesslurl' => '',
            'sort'       => ''
        );

        $this->setRequestParameter('oxid', 'de');
        $this->setRequestParameter('editval', $parameters);
        $languageMain->save();
    }

    /**
     * Test deleting a language.
     */
    public function testLanguageMainDeleteLanguage()
    {
        $this->setRequestParameter('oxid', 'en');

        $languageList = oxNew('Language_List');
        $languageList->deleteEntry();

        $languages = $this->getLanguageMain()->_getLanguages();
        $this->assertFalse(isset($languages['params']['en']));

        $dbMetaDataHandler = oxNew("oxDbMetaDataHandler");
        $dbMetaDataHandler->updateViews();

        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle_en', 'oxarticles'));
        $this->assertTrue($dbMetaDataHandler->fieldExists('oxtitle_en', 'oxv_oxarticles'));
        $this->assertFalse($dbMetaDataHandler->tableExists('oxv_oxarticles_en'));
    }

    /**
     * Test deleting a language.
     */
    public function testLanguageMainDeleteMainLanguageForbidden()
    {
        $expectedException = oxNew('oxExceptionToDisplay');
        $expectedException->setMessage('LANGUAGE_DELETINGMAINLANG_WARNING');

        $utilsView = $this->getMock('oxUtilsView', array('addErrorToDisplay'));
        $utilsView->expects($this->once())->method('addErrorToDisplay')->with($this->equalTo($expectedException));
        oxRegistry::set('oxUtilsView', $utilsView);

        $this->setRequestParameter('oxid', 'de');

        $languageList = oxNew('Language_List');
        $languageList->deleteEntry();

        $languages = $this->getLanguageMain()->_getLanguages();
        $this->assertTrue(isset($languages['params']['en']));

    }

    /**
     * Test table set creation when adding multiple new languages.
     */
    public function testCreateManyLanguages()
    {
        //Give the core tables their full amount of multilanguage columns (e.g. oxtitle and 7 oxtitle_*)
        $this->prepare(4);
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $expected = array(
            'de' => 'oxarticles',
            'en' => 'oxarticles',
            'fr' => 'oxarticles',
            'aa' => 'oxarticles',
            'bb' => 'oxarticles',
            'cc' => 'oxarticles',
            'dd' => 'oxarticles'
        );

        $this->assertSame($expected, $metaDataHandler->getLanguage2TableSetMap('oxarticles', 'oxtitle'));

        //next language should go to *_set1 table
        $newLanguage = 'zz';
        $this->insertLanguage($newLanguage);

        $expected['zz'] = 'oxarticles_set1';
        $this->assertSame($expected, $metaDataHandler->getLanguage2TableSetMap('oxarticles', 'oxtitle'));

        $this->assertTrue($metaDataHandler->tableExists('oxarticles_set1'));
        $this->assertFalse($metaDataHandler->tableExists('oxv_oxarticles_set1'));

    }

    /**
     * Test table set creation when adding multiple new languages.
     */
    public function testCreateEvenMoreLanguages()
    {
        $this->prepare(21);
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $expected = array(
            'de' => 'oxarticles',
            'en' => 'oxarticles',
            'fr' => 'oxarticles',
            'aa' => 'oxarticles',
            'bb' => 'oxarticles',
            'cc' => 'oxarticles',
            'dd' => 'oxarticles',
            'ee' => 'oxarticles_set1',
            'ff' => 'oxarticles_set1',
            'gg' => 'oxarticles_set1',
            'hh' => 'oxarticles_set1',
            'ii' => 'oxarticles_set1',
            'jj' => 'oxarticles_set1',
            'kk' => 'oxarticles_set1',
            'll' => 'oxarticles_set1',
            'mm' => 'oxarticles_set2',
            'nn' => 'oxarticles_set2',
            'oo' => 'oxarticles_set2',
            'pp' => 'oxarticles_set2',
            'qq' => 'oxarticles_set2',
            'rr' => 'oxarticles_set2',
            'ss' => 'oxarticles_set2',
            'tt' => 'oxarticles_set2',
            'uu' => 'oxarticles_set3'
        );

        $this->assertSame($expected, $metaDataHandler->getLanguage2TableSetMap('oxarticles', 'oxtitle'));
    }

    /**
     * Test getting multilanguage set tables.
     */
    public function testDbMetaDataHandlerGetAllMultiTables()
    {
        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        $this->prepare(21);
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $expected = array('addtest_set1', 'addtest_set2', 'addtest_set3');
        $this->assertSame($expected, $metaDataHandler->getAllMultiTables('addtest'));
    }

    /**
     * Test getting single language fields. Needed ofr view creation.
     */
    public function testDbMetaDataHandlerGetSingleLangFields()
    {
        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        $this->prepare(9);
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $expected = array('OXID'  => 'addtest.OXID',
                          'TITLE' => 'addtest_set1.TITLE_ii');

        $this->assertSame($expected, $metaDataHandler->getSinglelangFields('addtest', 'ii'));

    }

    /**
     * Test getting single language fields. Needed ofr view creation.
     */
    public function testDbMetaDataHandlerGetSingleLangFieldsUnderscoreLanguageAbbreviation()
    {
        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        $this->insertLanguage('xy_yx');
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $expected = array('de'    => 'addtest',
                          'en'    => 'addtest',
                          'fr'    => 'addtest',
                          'xy_yx' => 'addtest');

        $this->assertSame($expected, $metaDataHandler->getLanguage2TableSetMap('addtest', 'title'));

        $expected = array('OXID'  => 'addtest.OXID',
                          'TITLE' => 'addtest.TITLE_xy_yx');

        $this->assertSame($expected, $metaDataHandler->getSinglelangFields('addtest', 'xy_yx'));
    }

    /**
     * Test getting single language fields. Needed ofr view creation.
     */
    public function testDbMetaDataHandlerManyLanguagesGetSingleLangFieldsUnderscoreLanguageAbbreviation()
    {
        $this->createTable('addtest');
        $this->setConfigParam('aMultiLangTables', array('addtest'));

        $this->prepare(9);
        $this->insertLanguage('xy_yx');
        $metaDataHandler = oxNew('oxDbMetaDataHandler');

        $expected = array('OXID'  => 'addtest.OXID',
                          'TITLE' => 'addtest_set1.TITLE_xy_yx');

        $this->assertSame($expected, $metaDataHandler->getSinglelangFields('addtest', 'xy_yx'));
    }

    /**
     * Test helper to create additional multilanguage table and set tables.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param bool   $leaveOneOut
     * @param bool   $fullSet
     */
    protected function createTestTables($tableName = 'addtest', $fieldName = 'title', $leaveOneOut = false, $fullSet = false)
    {
        $queries = array();
        $fieldName = strtoupper($fieldName);

        $queries[0] = "CREATE TABLE `" . $tableName . "` (" .
                      "`OXID` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Item id'," .
                      "`" . $fieldName . "_de` varchar(128) NOT NULL DEFAULT '' COMMENT 'Title (multilanguage)'," .
                      "`" . $fieldName . "_en` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "_aa` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "_bb` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "_cc` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "_dd` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "_ee` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "_ff` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "XXX_de` varchar(128) NOT NULL DEFAULT '' COMMENT 'Title (multilanguage)'," .
                      "`" . $fieldName . "XXX_en` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "XXX_aa` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "XXX_bb` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "XXX_cc` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "XXX_dd` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "XXX_ee` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "XXX_ff` varchar(128) NOT NULL DEFAULT ''," .
                      "PRIMARY KEY (`OXID`)" .
                      ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='for testing'";

        $queries[1] = "CREATE TABLE `" . $tableName . "_set1` (" .
                      "`OXID` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Item id'," .
                      "`" . $fieldName . "_gg` varchar(128) NOT NULL DEFAULT '' COMMENT 'Title (multilanguage)',";
        if (!$leaveOneOut) {
            $queries[1] .= "`" . $fieldName . "_hh` varchar(128) NOT NULL DEFAULT ''," ;
        }
        $queries[1] .= "`" . $fieldName . "_ii` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "_jj` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "_kk` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "_ll` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "_mm` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "_nn` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "XXX_gg` varchar(128) NOT NULL DEFAULT '',";
        if (!$leaveOneOut) {
            $queries[1] .= "`" . $fieldName . "XXX_hh` varchar(128) NOT NULL DEFAULT ''," ;
        }
        $queries[1] .= "`" . $fieldName . "XXX_ii` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "XXX_jj` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "XXX_kk` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "XXX_ll` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "XXX_mm` varchar(128) NOT NULL DEFAULT ''," .
                       "`" . $fieldName . "XXX_nn` varchar(128) NOT NULL DEFAULT ''," .
                       "PRIMARY KEY (`OXID`)" .
                       ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='for testing'";

        $queries[2] = "CREATE TABLE `" . $tableName . "_set2` (" .
                      "`OXID` char(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Item id'," .
                      "`" . $fieldName . "_oo` varchar(128) NOT NULL DEFAULT '' COMMENT 'Title (multilanguage)'," .
                      "`" . $fieldName . "_pp` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "_qq` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "XXX_oo` varchar(128) NOT NULL DEFAULT '' COMMENT 'Title (multilanguage)'," .
                      "`" . $fieldName . "XXX_pp` varchar(128) NOT NULL DEFAULT ''," .
                      "`" . $fieldName . "XXX_qq` varchar(128) NOT NULL DEFAULT ''," .
                      "PRIMARY KEY (`OXID`)" .
                      ") ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='for testing'";

        if ($fullSet) {
            unset($queries[2]);
        }

        foreach ($queries as $query) {
            oxDb::getDb()->query($query);
            oxDb::getInstance()->getTableDescription($tableName); //throws exception if table does not exist
            $this->additionalTables[$tableName] = $tableName;
        }

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
     * Test helper to add a bunch of new languages to shop configuration.
     *
     * @param bool $leaveOneOut
     * @param bool $fullSet
     */
    protected function setTestLanguages($leaveOneOut = false, $fullSet = false)
    {
        if ($fullSet) {
            $languageAbbreviations = array('de', 'en', 'aa', 'bb', 'cc', 'dd', 'ee', 'ff',
                                           'gg', 'hh', 'ii', 'jj', 'kk', 'll', 'mm', 'nn');
        } else {
            $languageAbbreviations = array('de', 'en', 'aa', 'bb', 'cc', 'dd', 'ee', 'ff',
                                           'gg', 'hh', 'ii', 'jj', 'kk', 'll', 'mm', 'nn',
                                           'oo', 'pp', 'qq');
        }

        $languages = array();
        $sort = 0;

        if ($leaveOneOut) {
            unset($languageAbbreviations['hh']);
        }


        foreach ($languageAbbreviations as $languageId) {
            $languages['params'][$languageId] = array('baseId' => $languageId,
                                                      'active' => 1,
                                                      'sort'   => $sort);

            $languages['lang'][$languageId] = $languageId;
            $languages['urls'][$languageId]     = '';
            $languages['sslUrls'][$languageId]  = '';

            $sort++;
        }

        $this->storeLanguageConfiguration($languages, 'de');
    }

}
