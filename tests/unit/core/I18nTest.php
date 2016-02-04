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

//require_once 'oxbaseTest.php';

class _oxI18n extends \oxI18n
{

    public function getClassVar($sName)
    {
        return $this->$sName;
    }

    public function setClassVar($sName, $sVal)
    {
        return $this->$sName = $sVal;
    }

    public function enableLazyLoading()
    {
        $this->_blUseLazyLoading = true;
    }

}

class Unit_Core_oxi18ntest extends OxidTestCase
{
    protected function setUp()
    {
        if ($this->getName() == "testMultilangObjectDeletion") {
            $this->_insertTestLanguage();
        }

        parent::setUp();
    }

    /**
     * Tear down the fixture.
     */
    protected function tearDown()
    {
        if ($this->getName() == 'testUpdateAndSeoIsOn') {
            $oDB = oxDb::getDb();
            $oDB->execute("delete from oxseo where oxtype != 'static'");
            $oDB->execute("delete from oxarticles where oxid='testa'");
            $oDB->execute("delete from oxartextends where oxid='testa'");
        }

        if ($this->getName() == "testMultilangObjectDeletion") {
            $this->_deleteTestLanguage();
        }

        parent::tearDown();
        modDB::getInstance()->cleanup();
    }

    protected function getSqlShopId()
    {
        $shopId = $this->getConfig()->getEdition() === 'EE' ? '1' : '';
        return $shopId;
    }

    public function testUpdateAndSeoIsOn()
    {
        oxTestModules::addFunction("oxutils", "seoIsActive", "{return true;}");

        $oArticle = oxNew('oxArticle');
        $oArticle->setId('testa');
        $oArticle->save();
        $oArticle->getLink();

        $oArticle = oxNew('oxArticle');
        $oArticle->setAdminMode(true);
        $oArticle->load('testa');
        $oArticle->oxarticles__oxtitle = new oxField('new title');
        $oArticle->save();

        $this->assertTrue('1' == oxDb::getDb()->getOne('select oxexpired from oxseo where oxobjectid = "testa"'));
    }

    public function testUpdateAndSeoIsOnMock()
    {

        $oSeo = $this->getMock('oxseoencoder', array('markAsExpired'));
        $oSeo->expects($this->once())->method('markAsExpired')->with(
            $this->equalTo('testa'),
            $this->equalTo(null),
            $this->equalTo(1),
            $this->equalTo('de')
        )->will($this->returnValue(null));
        oxTestModules::addFunction("oxutils", "seoIsActive", "{return true;}");

        $oArticle = oxNew('oxArticle');
        $oArticle->setId('testa');
        $oArticle->save();
        $oArticle->getLink();

        oxTestModules::addModuleObject('oxSeoEncoder', $oSeo);

        $oArticle = oxNew('oxArticle');
        $oArticle->setAdminMode(true);
        $oArticle->load('testa');
        $oArticle->oxarticles__oxtitle = new oxField('new title');
        $oArticle->save();
    }

    public function testSetLanguage()
    {
        $oObj = new _oxI18n();
        $oObj->setLanguage('de');
        $this->assertEquals('de', $oObj->getClassVar("_iLanguage"));
        $oObj->setLanguage('en');
        $this->assertEquals('en', $oObj->getClassVar("_iLanguage"));
    }

    public function testSetEnableMultilang()
    {
        $oObj = new _oxI18n();
        $oObj->setEnableMultilang(false);
        $this->assertFalse($oObj->getClassVar("_blEmployMultilanguage"));
        $oObj->setEnableMultilang(true);
        $this->assertTrue($oObj->getClassVar("_blEmployMultilanguage"));
    }

    public function testSetEnableMultiLangReloadsAFieldNames()
    {
        $oi18 = new _oxI18n();
        $oi18->init("oxartextends");
        $this->assertEquals(array('oxid' => 0, 'oxlongdesc' => 1, 'oxtags' => 1, 'oxtimestamp' => 0), $oi18->getClassVar('_aFieldNames'));

        $oi18 = new _oxI18n();
        $oi18->init("oxartextends");
        $oi18->setEnableMultilang(false);
        $this->assertEquals(array('oxid' => 0, 'oxlongdesc_de' => 0, 'oxlongdesc_en' => 0, 'oxlongdesc_fr' => 0, 'oxlongdesc' => 0, 'oxtags_de' => 0, 'oxtags_en' => 0, 'oxtags_fr' => 0, 'oxtags' => 0, 'oxtimestamp' => 0), $oi18->getClassVar('_aFieldNames'));
    }

    public function testSetEnableMultilanguageCacheTest()
    {
        $oI18n = $this->getMock('oxI18n', array('modifyCacheKey'));
        $oI18n->expects($this->once())->method('modifyCacheKey')->with("_nonml");
        $oI18n->setEnableMultilang(false);
    }

    public function testIsMultilingualField()
    {
        $oObj = new _oxI18n();
        $oObj->init("oxarticles");

        $this->assertTrue($oObj->IsMultilingualField('oxtitle'));
        $this->assertTrue($oObj->IsMultilingualField('oxvarselect'));
        $this->assertFalse($oObj->IsMultilingualField('oxid'));
        $this->assertFalse($oObj->IsMultilingualField('non existing'));
        $this->assertFalse($oObj->IsMultilingualField('oxtime'));
    }

    public function testIsMultilingualFieldLazyLoad()
    {
        $this->cleanTmpDir();
        $oObj = new _oxI18n();
        $oObj->enableLazyLoading();
        $oObj->init("oxarticles");

        $this->assertTrue($oObj->IsMultilingualField('oxtitle'));
    }

    public function testLoadInLangDe()
    {
        $oObj = new _oxI18n();
        $oObj->init("oxarticles");
        $oObj->loadInLang('de', 1127);

        $this->assertEquals("Blinkende Eiswürfel FLASH", $oObj->oxarticles__oxtitle->value);
        $this->assertEquals(1127, $oObj->getId());
        $this->assertFalse(isset($oObj->oxarticles__oxtitle_de->value));
    }

    public function testLoadInLangEn()
    {
        $oObj = new _oxI18n();
        $oObj->init("oxarticles");
        $oObj->loadInLang('en', 1127);
        $this->assertEquals("Ice Cubes FLASH", $oObj->oxarticles__oxtitle->value);
        $this->assertEquals(1127, $oObj->getId());
        $this->assertFalse(isset($oObj->oxarticles__oxtitle_en->value));
    }

    public function testLoadInLangDeDisableMultilang()
    {
        $oObj = new _oxI18n();
        $oObj->setEnableMultilang(false);
        $oObj->init("oxarticles");
        $oObj->loadInLang('de', 1127);
        $this->assertEquals(1127, $oObj->getId());
        $this->assertEquals('', $oObj->oxarticles__oxtitle->value);
        $this->assertEquals("Blinkende Eiswürfel FLASH", $oObj->oxarticles__oxtitle_de->value);
        $this->assertEquals("Ice Cubes FLASH", $oObj->oxarticles__oxtitle_en->value);
    }

    public function testLoadInLangEnDisableMultilang()
    {
        $oObj = new _oxI18n();
        $oObj->setEnableMultilang(false);
        $oObj->init("oxarticles");
        $oObj->loadInLang('en', 1127);
        $this->assertEquals('', $oObj->oxarticles__oxtitle->value);
        $this->assertEquals("Blinkende Eiswürfel FLASH", $oObj->oxarticles__oxtitle_de->value);
        $this->assertEquals("Ice Cubes FLASH", $oObj->oxarticles__oxtitle_en->value);
    }

    public function testLazyLoadInLangDe()
    {
        $this->cleanTmpDir();
        oxRegistry::getLang()->setBaseLanguage('de');

        $oBase = new _oxI18n();
        $oBase->enableLazyLoading();
        $oBase->init("oxarticles");
        $oBase->load("2000");
        $this->assertEquals("Wanduhr ROBOT", $oBase->oxarticles__oxtitle->value);
    }

    public function testLazyLoadInLangEn()
    {
        $this->cleanTmpDir();
        oxRegistry::getLang()->setBaseLanguage('en');

        $oBase = new _oxI18n();
        $oBase->enableLazyLoading();
        $oBase->init("oxarticles");
        $oBase->load("2000");
        $this->assertEquals("Wall Clock ROBOT", $oBase->oxarticles__oxtitle->value);
    }

    public function testLoad()
    {
        oxRegistry::getLang()->setBaseLanguage('en');

        $oObj = new _oxI18n();
        $oObj->init("oxarticles");
        $oObj->load(1127);

        $this->assertEquals("Ice Cubes FLASH", $oObj->oxarticles__oxtitle->value);
    }

    public function testGetAvailableInLangs()
    {
        $aLang = array('de' => "Deutsch", 'en' => "English", 'lt' => "Lithuanian", 'zb' => "ZuluBumBum");
        $aLangParams['de']['baseId'] = 'de';
        $aLangParams['de']['abbr'] = 'de';
        $aLangParams['en']['baseId'] = 'en';
        $aLangParams['en']['abbr'] = 'en';
        $aLangParams['lt']['baseId'] = 'lt';
        $aLangParams['lt']['abbr'] = 'lt';
        $aLangParams['zb']['baseId'] = 'zb';
        $aLangParams['zb']['abbr'] = 'zb';

        $this->getConfig()->setConfigParam('aLanguageParams', $aLangParams);
        $this->getConfig()->setConfigParam('aLanguages', $aLang);

        $oObj = new _oxI18n();
        $oObj->init("oxwrapping");
        $oObj->load('a6840cc0ec80b3991.74884864');

        $aRes = $oObj->getAvailableInLangs();
        $this->assertEquals(array('de' => "Deutsch", 'en' => "English"), $aRes);
    }

    public function testGetAvailableInLangsWithNotLoadedObject()
    {
        $aLang = array('de' => "Deutsch", 'en' => "English", 'lt' => "Lithuanian", 'zb' => "ZuluBumBum");
        $this->getConfig()->setConfigParam('aLanguages', $aLang);

        $oObj = new _oxI18n();
        $oObj->init("oxwrapping");

        $aRes = $oObj->getAvailableInLangs();
        $this->assertEquals(array(), $aRes);

        $oObj->setId('noSuchId');
        $aRes = $oObj->getAvailableInLangs();
        $this->assertEquals(array(), $aRes);
    }

    public function testGetAvailableInLangsObjectWithoutMultilangFields()
    {
        $aRezLang = array('de' => "Deutsch", 'en' => "English", 'lt' => "Lithuanian", 'zb' => "ZuluBumBum");
        $aLang = array('de' => "Deutsch", 'en' => "English", 'lt' => "Lithuanian", 'zb' => "ZuluBumBum");
        $aLangParams['de']['baseId'] = 'de';
        $aLangParams['de']['abbr'] = 'de';
        $aLangParams['en']['baseId'] = 'en';
        $aLangParams['en']['abbr'] = 'en';
        $aLangParams['lt']['baseId'] = 'lt';
        $aLangParams['lt']['abbr'] = 'lt';
        $aLangParams['zb']['baseId'] = 'zb';
        $aLangParams['zb']['abbr'] = 'zb';

        $this->getConfig()->setConfigParam('aLanguageParams', $aLangParams);
        $this->getConfig()->setConfigParam('aLanguages', $aLang);
        $this->getConfig()->setConfigParam('aLanguages', $aLang);

        $oObj = new _oxI18n();
        $oObj->init("oxvouchers");

        $aRes = $oObj->getAvailableInLangs();
        $this->assertEquals($aRezLang, $aRes);
    }

    public function testGetFieldLang()
    {
        $oObj = new _oxI18n();
        $this->assertEquals('de', $oObj->UNITgetFieldLang('oxtitle_de'));
        $this->assertEquals('en', $oObj->UNITgetFieldLang('oxtitle_en'));
        $this->assertNull($oObj->UNITgetFieldLang('oxtitle'));
        $this->assertEquals('xy_yx', $oObj->UNITgetFieldLang('oxtitle_xy_yx'));
    }

    public function testAddFieldNormal()
    {
        $oObj = new _oxI18n();
        $oObj->setClassVar("_sCoreTable", "oxtesttable");
        $oObj->UNITaddField('oxtestField', 1);

        $aFieldNames = $oObj->getClassVar("_aFieldNames");

        $this->assertEquals(array("oxid" => 0, "oxtestfield" => 1), $aFieldNames);
        $this->assertTrue(isset($oObj->oxtesttable__oxtestfield));
    }

    public function testAddFieldMulitlanguage()
    {
        $oObj = new _oxI18n();
        $oObj->setClassVar("_sCoreTable", "oxtesttable");
        $oObj->UNITaddField('oxtestField_en', 1);

        $aFieldNames = $oObj->getClassVar("_aFieldNames");

        $this->assertEquals(array('oxid' => 0), $aFieldNames);
        $this->assertFalse(isset($oObj->oxtesttable__oxtestfield));
        $this->assertFalse(isset($oObj->oxtesttable__oxtestfield_en));
    }

    public function testAddFieldMulitlanguageDisableMultilang()
    {
        $oObj = new _oxI18n();
        $oObj->setClassVar("_sCoreTable", "oxtesttable");
        $oObj->setEnableMultilang(false);
        $oObj->UNITaddField('oxtestField_en', 1);

        $aFieldNames = $oObj->getClassVar("_aFieldNames");

        $this->assertEquals(array('oxid' => 0, 'oxtestfield_en' => 1), $aFieldNames);
        $this->assertFalse(isset($oObj->oxtesttable__oxtestfield));
        $this->assertTrue(isset($oObj->oxtesttable__oxtestfield_en));
    }

    //tests from oxBase public functions, but having different public functionality in oxi18n
    public function testGetUpdateFieldsLangDe()
    {
        $oObj = new _oxI18n();
        $oObj->init('oxattribute');
        $oObj->setLanguage('de');

        $shopId = $this->getSqlShopId();
        $sExpRes = "oxid = '',oxshopid = '" . $shopId . "',oxtitle_de = '',oxpos = '9999',oxdisplayinbasket = '0'";

        $this->assertEquals($sExpRes, $oObj->UNITgetUpdateFields());

    }

    public function testGetSelectFieldsLangDe()
    {
        $oObj = new _oxI18n();
        $oObj->init('oxattribute');
        $oObj->setLanguage('de');
        $sTable = $oObj->getViewName();

        $additional = $this->getConfig()->getEdition() === 'EE' ? "`$sTable`.`oxmapid`, " : "";
        $sExpRes = "`$sTable`.`oxid`, $additional`$sTable`.`oxshopid`, `$sTable`.`oxtitle`, `$sTable`.`oxpos`, `$sTable`.`oxtimestamp`, `$sTable`.`oxdisplayinbasket`";

        $this->assertEquals($sExpRes, $oObj->getSelectFields());
    }

    public function testGetUpdateFieldsLangEn()
    {
        $oObj = new _oxI18n();
        $oObj->init('oxattribute');
        $oObj->setLanguage('en');

        $shopId = $this->getSqlShopId();
        $sExpRes = "oxid = '',oxshopid = '$shopId',oxtitle_en = '',oxpos = '9999',oxdisplayinbasket = '0'";

        $this->assertEquals($sExpRes, $oObj->UNITgetUpdateFields());
    }

    public function testGetSelectFieldsLangEn()
    {
        $oObj = new _oxI18n();
        $oObj->init('oxattribute');
        $oObj->setLanguage('en');
        $sTable = $oObj->getViewName();

        $additional = $this->getConfig()->getEdition() === 'EE' ? "`$sTable`.`oxmapid`, " : "";
        $sExpRes = "`$sTable`.`oxid`, $additional`$sTable`.`oxshopid`, `$sTable`.`oxtitle`, `$sTable`.`oxpos`, `$sTable`.`oxtimestamp`, `$sTable`.`oxdisplayinbasket`";

        $this->assertEquals($sExpRes, $oObj->getSelectFields());
    }

    public function testGetUpdateFieldsLangEnDisableMultilang()
    {
        $oObj = new _oxI18n();
        $oObj->setEnableMultilang(false);
        $oObj->init('oxattribute');
        $oObj->setLanguage('en');

        $shopId = $this->getSqlShopId();
        $sExpRes = "oxid = '',oxshopid = '$shopId',oxtitle = '',oxtitle_de = '',oxtitle_en = '',oxtitle_fr = '',oxpos = '9999',oxdisplayinbasket = '0'";

        $this->assertEquals($sExpRes, $oObj->UNITgetUpdateFields());
    }

    public function testGetSelectFieldsLangEnDisableMultilang()
    {
        $oObj = new _oxI18n();
        $oObj->setEnableMultilang(false);
        $oObj->init('oxattribute');
        $oObj->setLanguage('en');
        $sTable = $oObj->getViewName();

        $additional = $this->getConfig()->getEdition() === 'EE' ? "`$sTable`.`oxmapid`, " : "";
        $sExpRes = "`$sTable`.`oxid`, $additional`$sTable`.`oxshopid`, `$sTable`.`oxtitle`, `$sTable`.`oxtitle_de`, `$sTable`.`oxtitle_en`, `$sTable`.`oxtitle_fr`, `$sTable`.`oxpos`, `$sTable`.`oxtimestamp`, `$sTable`.`oxdisplayinbasket`";

        $this->assertEquals($sExpRes, $oObj->getSelectFields());
    }

    public function testGetSqlActiveSnippetForceCoreActiveMultilang()
    {
        $iCurrTime = time();
        oxTestModules::addFunction("oxUtilsDate", "getTime", "{ return $iCurrTime; }");

        $oI18n = $this->getMock('oxI18n', array('getViewName'));
        $oI18n->expects($this->once())->method('getViewName')->with($this->equalTo(null))->will($this->returnValue('oxi18n'));

        $oI18n->UNITaddField('oxactive', 0);
        $oI18n->UNITaddField('oxactivefrom', 0);
        $oI18n->UNITaddField('oxactiveto', 0);

        if ($this->getConfig()->getEdition() === 'EE') {
            $oI18n->setForceCoreTableUsage(true);
        }

        $sDate = date('Y-m-d H:i:s', $iCurrTime);
        $sTable = 'oxi18n';
        $sTemplate = " (   $sTable.oxactive = 1  or  ( $sTable.oxactivefrom < '$sDate' and $sTable.oxactiveto > '$sDate' ) ) ";

        $sQ = $oI18n->getSqlActiveSnippet();
        $this->assertEquals($sTemplate, $sQ);
    }

    public function testGetSqlActiveSnippet()
    {
        $iCurrTime = time();
        oxTestModules::addFunction("oxUtilsDate", "getTime", "{ return $iCurrTime; }");

        $sTable = 'oxi18n';

        /** @var oxI18n|PHPUnit_Framework_MockObject_MockObject $oI18n */
        $oI18n = $this->getMock('oxI18n', array('getCoreTableName', 'getViewName', 'isMultilingualField', 'getLanguage'));
        $oI18n->expects($this->any())->method('getCoreTableName')->will($this->returnValue($sTable));
        $oI18n->expects($this->once())->method('getViewName')->will($this->returnValue('oxi18n'));
        $oI18n->expects($this->never())->method('getLanguage');

        $oI18n->UNITaddField('oxactive', 0);
        $oI18n->UNITaddField('oxactivefrom', 0);
        $oI18n->UNITaddField('oxactiveto', 0);

        if ($this->getConfig()->getEdition() === 'EE') {
            $oI18n->setForceCoreTableUsage(false);
        }

        $sDate = date('Y-m-d H:i:s', $iCurrTime);
        $sTemplate = " (   $sTable.oxactive = 1  or  ( $sTable.oxactivefrom < '$sDate' and $sTable.oxactiveto > '$sDate' ) ) ";

        $sQ = $oI18n->getSqlActiveSnippet();
        $this->assertEquals($sTemplate, $sQ);
    }

    /*
     * Testing if object is treated as multilanguage
     */
    public function testIsMultilang()
    {
        $oObj = oxNew('oxi18n');
        $this->assertTrue($oObj->isMultilang());
    }

    /*
     * Testing cache hay modifier
     */
    public function testModifyCacheKey()
    {
        $oObj = $this->getProxyClass('oxi18n');
        $oObj->modifyCacheKey(null);
        $this->assertNull($oObj->getNonPublicVar("_sCacheKey"));
        $oObj->modifyCacheKey("_nonml");
        $this->assertEquals("_nonml", $oObj->getNonPublicVar("_sCacheKey"));
        $oObj->modifyCacheKey("_nonml");
        $this->assertEquals("_nonml_nonml", $oObj->getNonPublicVar("_sCacheKey"));
        $oObj->modifyCacheKey("_nonml", true);
        $this->assertEquals("_nonml|i18n", $oObj->getNonPublicVar("_sCacheKey"));
    }

    /**
     * base test
     */
    public function testGetUpdateSqlFieldNameMLfield()
    {
        $oObj = $this->getMock('oxi18n', array('isMultilingualField'));
        $oObj->expects($this->exactly(3))->method('isMultilingualField')
            ->with($this->equalTo('field'))
            ->will($this->returnValue(true));

        $oObj->setLanguage('de');
        $this->assertEquals('field_de', $oObj->getUpdateSqlFieldName('field'));
        $oObj->setLanguage('en');
        $this->assertEquals('field_en', $oObj->getUpdateSqlFieldName('field'));
        $oObj->setLanguage('xy_yx');
        $this->assertEquals('field_xy_yx', $oObj->getUpdateSqlFieldName('field'));
    }

    /**
     * base test
     */
    public function testGetUpdateSqlFieldNameNonMLfield()
    {
        $oObj = $this->getMock('oxi18n', array('isMultilingualField'));
        $oObj->expects($this->exactly(3))->method('isMultilingualField')
            ->with($this->equalTo('field'))
            ->will($this->returnValue(false));

        $oObj->setLanguage('de');
        $this->assertEquals('field', $oObj->getUpdateSqlFieldName('field'));
        $oObj->setLanguage('en');
        $this->assertEquals('field', $oObj->getUpdateSqlFieldName('field'));
        $oObj->setLanguage('xy_yx');
        $this->assertEquals('field', $oObj->getUpdateSqlFieldName('field'));
    }

    /**
     * base test
     */
    public function testGetUpdateFieldsForTable()
    {
        $oObj = oxNew('oxi18n');
        $oObj->init('oxstates');
        $oObj->setId('test_a');
        $oObj->oxstates__oxtitle = new oxField('titletest');

        $oObj->setLanguage('de');
        $this->assertEquals("oxid = 'test_a',oxcountryid = '',oxtitle_de = 'titletest',oxisoalpha2 = ''", $oObj->UNITgetUpdateFieldsForTable('oxstates'));
        $this->assertEquals("oxid = 'test_a'", $oObj->UNITgetUpdateFieldsForTable('oxstates_set1'));
        $this->assertEquals("oxid = 'test_a'", $oObj->UNITgetUpdateFieldsForTable('oxstates_set3'));
    }

    /**
     * base test
     */
    public function testGetUpdateFieldsForTableSetTable()
    {
        $oObj = $this->getMock('oxi18n', array('getLanguageTableName'));
        $oObj->expects($this->any())->method('getLanguageTableName')->will($this->returnValue('oxstates_set1'));

        $oObj->init('oxstates');
        $oObj->setId('test_a');
        $oObj->oxstates__oxtitle = new oxField('titletest');
        $oObj->setLanguage('xy_yx');

        $this->assertEquals("oxid = 'test_a',oxcountryid = '',oxisoalpha2 = ''", $oObj->UNITgetUpdateFieldsForTable('oxstates'));
        $this->assertEquals("oxid = 'test_a',oxtitle_xy_yx = 'titletest'", $oObj->UNITgetUpdateFieldsForTable('oxstates_set1'));
        $this->assertEquals("oxid = 'test_a'", $oObj->UNITgetUpdateFieldsForTable('oxstates_set3'));
    }

    /**
     * base test
     */
    public function testGetUpdateFieldsForTableNonMlObject()
    {
        $cl = oxTestModules::addFunction(
            oxTestModules::addFunction(
                'oxi18n',
                '__setFieldNames($fn)',
                '{$this->_aFieldNames = $fn;}'
            ),
            '__getFieldNames',
            '{return $this->_aFieldNames;}'
        );

        $oObj = $this->getMock($cl, array('getLanguageTableName'));
        $oObj->expects($this->any())->method('getLanguageTableName')
            ->will($this->onConsecutiveCalls(
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set1'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set1'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set1'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set1'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set1'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set1')));

        $oObj->setEnableMultilang(false);
        $oObj->init('oxstates');
        $oObj->__setFieldNames(array_merge($oObj->__getFieldNames(), array('oxtitle_xy_yx' => 0)));
        $oObj->setId('test_a');
        $oObj->oxstates__oxtitle_xy_yx = new oxField('titletest');

        $oObj->setLanguage('de');
        $this->assertEquals("oxid = 'test_a',oxcountryid = '',oxtitle = '',oxisoalpha2 = '',oxtitle_de = '',oxtitle_en = '',oxtitle_fr = ''", $oObj->UNITgetUpdateFieldsForTable('oxstates'));
        $this->assertEquals("oxid = 'test_a',oxtitle_xy_yx = 'titletest'", $oObj->UNITgetUpdateFieldsForTable('oxstates_set1'));
        $this->assertEquals("oxid = 'test_a'", $oObj->UNITgetUpdateFieldsForTable('oxstates_set2'));

        $oObj->setLanguage('xy_yx');
        $this->assertEquals("oxid = 'test_a',oxcountryid = '',oxtitle = '',oxisoalpha2 = '',oxtitle_de = '',oxtitle_en = '',oxtitle_fr = ''", $oObj->UNITgetUpdateFieldsForTable('oxstates'));
        $this->assertEquals("oxid = 'test_a',oxtitle_xy_yx = 'titletest'", $oObj->UNITgetUpdateFieldsForTable('oxstates_set1'));
        $this->assertEquals("oxid = 'test_a'", $oObj->UNITgetUpdateFieldsForTable('oxstates_set2'));
    }

    /**
     * base test
     */
    public function testGetUpdateFields()
    {
        $oObj = $this->getMock('oxi18n', array('_getUpdateFieldsForTable', 'getCoreTableName'));
        $oObj->expects($this->exactly(1))->method('_getUpdateFieldsForTable')
            ->with($this->equalTo('coretable'), $this->equalTo('useskipsavefields'))
            ->will($this->returnValue('returned val'));
        $oObj->expects($this->exactly(1))->method('getCoreTableName')
            ->will($this->returnValue('coretable'));

        $this->assertEquals('returned val', $oObj->UNITgetUpdateFields('useskipsavefields'));
    }

    public static $aLoggedSqls = array();

    /**
     * base test
     */
    public function testUpdateCoreTable()
    {
        $oObj = oxNew('oxi18n');
        $oObj->init('oxstates');

        $oObj->setId("test_update");
        $oObj->oxstates__oxtitle = new oxField('test_x');

        $dbMock = $this->getDbObjectMock();
        $dbMock->expects($this->any())->method('select')->will($this->returnValue(false));
        $dbMock->expects($this->any())->method('execute')->will($this->evalFunction('{Unit_Core_oxi18ntest::$aLoggedSqls[] = $args[0];return true;}'));
        oxDb::setDbObject($dbMock);

        $oObj->setLanguage('de');
        Unit_Core_oxi18ntest::$aLoggedSqls = array();
        $oObj->UNITupdate();
        $this->assertEquals(
            array("update oxstates set oxid = 'test_update',oxcountryid = '',oxtitle_de = 'test_x',oxisoalpha2 = '' where oxstates.oxid = 'test_update'"),
            array_map('trim', Unit_Core_oxi18ntest::$aLoggedSqls)
        );
    }

    /**
     * base test
     */
    public function testUpdateSetTable()
    {
        $oObj = $this->getMock('oxi18n', array('getLanguageTableName'));
        $oObj->init('oxstates');
        $oObj->setId("test_update");
        $oObj->expects($this->any())->method('getLanguageTableName')->will($this->returnValue('oxstates_set11'));
        $oObj->oxstates__oxtitle = new oxField('test_x');

        $dbMock = $this->getDbObjectMock();
        $dbMock->expects($this->any())->method('select')->will($this->returnValue(false));
        $dbMock->expects($this->any())->method('execute')->will($this->evalFunction('{Unit_Core_oxi18ntest::$aLoggedSqls[] = $args[0];return true;}'));
        oxDb::setDbObject($dbMock);

        $oObj->setLanguage('xy_yx');
        Unit_Core_oxi18ntest::$aLoggedSqls = array();
        $oObj->UNITupdate();
        $this->assertEquals(
            array(
                "update oxstates set oxid = 'test_update',oxcountryid = '',oxisoalpha2 = '' where oxstates.oxid = 'test_update'",
                "insert into oxstates_set11 set oxid = 'test_update',oxtitle_xy_yx = 'test_x' on duplicate key update oxid = 'test_update',oxtitle_xy_yx = 'test_x'",
            ),
            array_map('trim', Unit_Core_oxi18ntest::$aLoggedSqls)
        );
    }

    /**
     * base test
     */
    public function testUpdateTogether()
    {
        $oObj = $this->getMock('oxi18n', array('getLanguageTableName'));
        $oObj->init('oxstates');
        $oObj->setId("test_update");
        $oObj->oxstates__oxtitle = new oxField('test_x');
        $oObj->expects($this->any())->method('getLanguageTableName')->will(
            $this->onConsecutiveCalls(
                $this->returnValue('oxstates'),$this->returnValue('oxstates'),$this->returnValue('oxstates_set11'),
                $this->returnValue('oxstates_set11'),$this->returnValue('oxstates_set11'),$this->returnValue('oxstates_set11')));

        $dbMock = $this->getDbObjectMock();
        $dbMock->expects($this->any())->method('select')->will($this->returnValue(false));
        $dbMock->expects($this->any())->method('execute')->will($this->evalFunction('{Unit_Core_oxi18ntest::$aLoggedSqls[] = $args[0];return true;}'));
        oxDb::setDbObject($dbMock);

        $oObj->setLanguage('de');
        Unit_Core_oxi18ntest::$aLoggedSqls = array();
        $oObj->UNITupdate();
        $this->assertEquals(
            array("update oxstates set oxid = 'test_update',oxcountryid = '',oxtitle_de = 'test_x',oxisoalpha2 = '' where oxstates.oxid = 'test_update'"),
            array_map('trim', Unit_Core_oxi18ntest::$aLoggedSqls)
        );

        $oObj->setLanguage('xy_yx');
        Unit_Core_oxi18ntest::$aLoggedSqls = array();
        $oObj->UNITupdate();
        $this->assertEquals(
            array(
                "update oxstates set oxid = 'test_update',oxcountryid = '',oxisoalpha2 = '' where oxstates.oxid = 'test_update'",
                "insert into oxstates_set11 set oxid = 'test_update',oxtitle_xy_yx = 'test_x' on duplicate key update oxid = 'test_update',oxtitle_xy_yx = 'test_x'",
            ),
            array_map('trim', Unit_Core_oxi18ntest::$aLoggedSqls)
        );
    }

    /**
     * base test
     */
    public function testUpdate_MLangDisabled()
    {
        $cl = oxTestModules::addFunction(
            oxTestModules::addFunction(
                'oxi18n',
                '__setFieldNames($fn)',
                '{$this->_aFieldNames = $fn;}'
            ),
            '__getFieldNames',
            '{return $this->_aFieldNames;}'
        );

        $oObj = $this->getMock($cl, array('_getLanguageSetTables', 'getLanguageTableName'));
        $oObj->expects($this->any())->method('_getLanguageSetTables')->will($this->returnValue(array('oxstates_set11')));
        $oObj->expects($this->any())->method('getLanguageTableName')->will(
            $this->onConsecutiveCalls(
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set11'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set11'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set11'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set11'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates_set11'),
                $this->returnValue('oxstates'), $this->returnValue('oxstates'), $this->returnValue('oxstates'),  $this->returnValue('oxstates_set11')));
        $oObj->setEnableMultilang(false);
        $oObj->init('oxstates');
        $oObj->__setFieldNames(array_merge($oObj->__getFieldNames(), array('oxtitle_xy_yx' => 0)));

        $oObj->setId("test_update");
        $oObj->oxstates__oxtitle = new oxField('test_x');
        $oObj->oxstates__oxtitle_xy_yx = new oxField('test_y');

        $dbMock = $this->getDbObjectMock();
        $dbMock->expects($this->any())->method('select')->will($this->returnValue(false));
        $dbMock->expects($this->any())->method('execute')->will($this->evalFunction('{Unit_Core_oxi18ntest::$aLoggedSqls[] = $args[0];return true;}'));
        oxDb::setDbObject($dbMock);

        $oObj->setLanguage('de');
        Unit_Core_oxi18ntest::$aLoggedSqls = array();
        $oObj->UNITupdate();
        $this->assertEquals(
            array(
                 "update oxstates set oxid = 'test_update',oxcountryid = '',oxtitle = 'test_x',oxisoalpha2 = '',oxtitle_de = '',oxtitle_en = '',oxtitle_fr = '' where oxstates.oxid = 'test_update'",
                 "insert into oxstates_set11 set oxid = 'test_update',oxtitle_xy_yx = 'test_y' on duplicate key update oxid = 'test_update',oxtitle_xy_yx = 'test_y'",
            ),
            array_map('trim', Unit_Core_oxi18ntest::$aLoggedSqls)
        );

        $oObj->setLanguage('xy_yx');
        Unit_Core_oxi18ntest::$aLoggedSqls = array();
        $oObj->UNITupdate();
        $this->assertEquals(
            array(
                 "update oxstates set oxid = 'test_update',oxcountryid = '',oxtitle = 'test_x',oxisoalpha2 = '',oxtitle_de = '',oxtitle_en = '',oxtitle_fr = '' where oxstates.oxid = 'test_update'",
                 "insert into oxstates_set11 set oxid = 'test_update',oxtitle_xy_yx = 'test_y' on duplicate key update oxid = 'test_update',oxtitle_xy_yx = 'test_y'",
            ),
            array_map('trim', Unit_Core_oxi18ntest::$aLoggedSqls)
        );
    }

    /**
     * base test
     */
    public function testInsert()
    {
        $oObj = $this->getMock('oxi18n', array('_getLanguageSetTables', 'getLanguageTableName'));
        $oObj->expects($this->any())->method('_getLanguageSetTables')->will($this->returnValue(array('oxstates_set11')));
        $oObj->init('oxstates');

        $oObj->expects($this->any())->method('getLanguageTableName')->will(
            $this->onConsecutiveCalls(
                $this->returnValue('oxstates'), $this->returnValue('oxstates'),
                $this->returnValue('oxstates_set11'), $this->returnValue('oxstates_set11')));

        $oObj->setId("test_insert");
        $oObj->oxstates__oxtitle = new oxField('test_x');

        $dbMock = $this->getDbObjectMock();
        $dbMock->expects($this->any())->method('select')->will($this->returnValue(false));
        $dbMock->expects($this->any())->method('execute')->will($this->evalFunction('{Unit_Core_oxi18ntest::$aLoggedSqls[] = $args[0];return true;}'));
        oxDb::setDbObject($dbMock);

        $oObj->setLanguage('de');
        Unit_Core_oxi18ntest::$aLoggedSqls = array();
        $oObj->UNITinsert();
        $this->assertEquals(
            array(
                "Insert into oxstates set oxid = 'test_insert',oxcountryid = '',oxtitle_de = 'test_x',oxisoalpha2 = ''",
                "insert into oxstates_set11 set oxid = 'test_insert'",
            ),
            array_map('trim', Unit_Core_oxi18ntest::$aLoggedSqls)
        );

        $oObj->setLanguage('xy_yx');
        Unit_Core_oxi18ntest::$aLoggedSqls = array();
        $oObj->UNITinsert();
        $this->assertEquals(
            array(
                "Insert into oxstates set oxid = 'test_insert',oxcountryid = '',oxisoalpha2 = ''",
                "insert into oxstates_set11 set oxid = 'test_insert',oxtitle_xy_yx = 'test_x'",
            ),
            array_map('trim', Unit_Core_oxi18ntest::$aLoggedSqls)
        );
    }


    /**
     * base test
     */
    public function testGetViewName()
    {
        $oObj = oxNew('oxi18n');
        $oObj->init('oxarticles');

        $this->assertEquals(getViewName('oxarticles', 'de', 1), $oObj->getViewName());
        $this->assertEquals(getViewName('oxarticles', 'de', -1), $oObj->getViewName(1));
        $this->assertEquals(getViewName('oxarticles', 'de', 1), $oObj->getViewName(0));
        $this->assertEquals(getViewName('oxarticles', 'de', 1), $oObj->getViewName());

        $oObj->setLanguage('en');
        $this->assertEquals(getViewName('oxarticles', 'en', 1), $oObj->getViewName());
        $this->assertEquals(getViewName('oxarticles', 'en', -1), $oObj->getViewName(1));
        $this->assertEquals(getViewName('oxarticles', 'en', 1), $oObj->getViewName(0));
        $this->assertEquals(getViewName('oxarticles', 'en', 1), $oObj->getViewName());

        $oObj->setEnableMultilang(false);
        $this->assertEquals(getViewName('oxarticles', -1, 1), $oObj->getViewName());
        $this->assertEquals(getViewName('oxarticles', -1, 1), $oObj->getViewName(0));
        $this->assertEquals(getViewName('oxarticles', -1, -1), $oObj->getViewName(1));
        $this->assertEquals(getViewName('oxarticles', -1, 1), $oObj->getViewName());
    }


    /**
     * base test
     */
    public function testGetAllFields()
    {
        $oObj = $this->getMock('oxi18n', array('_getTableFields', 'getViewName'));
        $oObj->expects($this->exactly(1))->method('_getTableFields')
            ->with($this->equalTo('view'), $this->equalTo('simeple?'))
            ->will($this->returnValue('returned val'));
        $oObj->expects($this->exactly(1))->method('getViewName')
            ->will($this->returnValue('view'));
        $oObj->setEnableMultilang(false);

        $this->assertEquals('returned val', $oObj->UNITGetAllFields('simeple?'));

        $oObj = $this->getMock('oxi18n', array('getViewName'));
        $oObj->expects($this->exactly(1))->method('getViewName')
            ->will($this->returnValue(''));
        $oObj->setEnableMultilang(false);

        $this->assertEquals(array(), $oObj->UNITGetAllFields('simeple?'));

    }

    /**
     * Test get update field value.
     */
    public function test_getUpdateFieldValue()
    {
        $oObj = oxNew('oxI18n');
        $oObj->init("oxarticles");
        $oObj->setId('test');
        $this->assertSame("'aaa'", $oObj->UNITgetUpdateFieldValue('oxid', new oxField('aaa')));
        $this->assertSame("'aaa\\\"'", $oObj->UNITgetUpdateFieldValue('oxid', new oxField('aaa"')));
        $this->assertSame("'aaa\''", $oObj->UNITgetUpdateFieldValue('oxid', new oxField('aaa\'')));

        $this->assertSame("''", $oObj->UNITgetUpdateFieldValue('oxid', new oxField(null)));
        $this->assertSame('null', $oObj->UNITgetUpdateFieldValue('oxvat', new oxField(null)));

        $this->assertSame("''", $oObj->UNITgetUpdateFieldValue('oxid_10', new oxField(null)));
        $this->assertSame('null', $oObj->UNITgetUpdateFieldValue('oxvat_10', new oxField(null)));
    }

    /**
     * Test for #0003138: Multilanguage fields having different
     * character case are not always detected as multilanguage
     */
    public function testIsMultilingualFieldFor0003138()
    {
        $oArticle = oxNew('oxArticle');
        $this->assertTrue($oArticle->isMultilingualField("oxtitle"));
        $this->assertTrue($oArticle->isMultilingualField("OXTITLE"));
        $this->assertTrue($oArticle->isMultilingualField("oXtItLe"));
    }

    protected $_aLangTables = array();

    /**
     * Inserts new test language tables
     */
    protected function _insertTestLanguage()
    {
        $oDb = oxDb::getDb(oxDB::FETCH_MODE_ASSOC);

        $this->_aLangTables["oxactions"] = "oxactions";
        $this->_aLangTables["oxcategory"] = "oxcategories";
        $this->_aLangTables["oxcontent"] = "oxcontents";
        $this->_aLangTables["oxcountry"] = "oxcountry";
        $this->_aLangTables["oxdelivery"] = "oxdelivery";
        $this->_aLangTables["oxdiscount"] = "oxdiscount";
        $this->_aLangTables["oxgroups"] = "oxgroups";
        $this->_aLangTables["oxlinks"] = "oxlinks";
        $this->_aLangTables["oxmediaurl"] = "oxmediaurls";
        $this->_aLangTables["oxnews"] = "oxnews";
        $this->_aLangTables["oxpayment"] = "oxpayments";
        $this->_aLangTables["oxreview"] = "oxreviews";
        $this->_aLangTables["oxstate"] = "oxstates";
        $this->_aLangTables["oxvendor"] = "oxvendor";
        $this->_aLangTables["oxwrapping"] = "oxwrapping";
        $this->_aLangTables["oxattribute"] = "oxattribute";
        $this->_aLangTables["oxselectlist"] = "oxselectlist";
        $this->_aLangTables["oxdeliveryset"] = "oxdeliveryset";
        $this->_aLangTables["oxmanufacturer"] = "oxmanufacturers";

        // creating language set tables and inserting by one test record
        foreach ($this->_aLangTables as $iPos => $sTable) {
            $sQ = "show create table {$sTable}";
            $rs = $oDb->execute($sQ);

            // creating table
            $sQ = end($rs->fields);
            if ((stripos($sTable, "oxartextends") === false && stripos($sTable, "oxshops") === false) &&
                !preg_match("/oxshopid/i", $sQ)
            ) {
                unset($this->_aLangTables[$iPos]);
                continue;
            }


            $sQ = str_replace($sTable, $sTable . "_set1", $sQ);
            $oDb->execute($sQ);
        }

        $sShopId = $this->_sOXID;

        // inserting test records
        foreach ($this->_aLangTables as $sTable) {
            // do not insert data into shops table..
            if (stripos($sTable, "oxshops") !== false) {
                continue;
            }

            $sQVal = "";
            $sQ = "show columns from {$sTable}";
            $rs = $oDb->execute($sQ);
            if ($rs != false && $rs->recordCount() > 0) {
                while (!$rs->EOF) {
                    $sValue = $rs->fields["Default"];
                    $sType = $rs->fields["Type"];
                    $sField = $rs->fields["Field"];

                    // overwriting default values
                    if (stripos($sField, "oxshopid") !== false) {
                        $sValue = $sShopId;
                    }
                    if (stripos($sField, "oxid") !== false) {
                        $sValue = "_testRecordForTest";
                    }


                    if ($sQVal) {
                        $sQVal .= ", ";
                    }
                    $sQVal .= "'$sValue'";
                    $rs->moveNext();
                }
            }

            $oDb->execute("insert into {$sTable} values ({$sQVal})");
            $oDb->execute("insert into {$sTable}_set1 values ({$sQVal})");
        }
    }

    /**
     * Removes test language tables
     */
    protected function _deleteTestLanguage()
    {
        // dropping language set tables
        $oDb = oxDb::getDb(oxDB::FETCH_MODE_ASSOC);
        foreach ($this->_aLangTables as $sTable) {
            $oDb->execute("drop table {$sTable}_set1");
            $oDb->execute("delete from {$sTable} where oxid like '_test%'");
        }
    }

    /**
     * Testing how multilanguage objects are deleted..
     */
    public function testMultilangObjectDeletion()
    {
        $sId = "_testRecordForTest";
        $oDb = oxDb::getDb(oxDB::FETCH_MODE_ASSOC);

        $this->getConfig()->setConfigParam("iLangPerTable", 4);
        oxTestModules::addFunction("oxLang", "getLanguageIds", "{return array('0' => 'de', '1' => 'de', '2' => 'lt', '3' => 'ru', '4' => 'pl', '5' => 'cz');}");

        foreach ($this->_aLangTables as $sObjectType => $sTableName) {
            $this->assertTrue((bool) $oDb->getOne("select 1 from {$sTableName} where oxid = '{$sId}'"), "Missing data for table {$sTableName} table");
            $this->assertTrue((bool) $oDb->getOne("select 1 from {$sTableName}_set1 where oxid = '{$sId}'"), "Missing data for table {$sTableName}_set1 table");

            $oObject = oxNew($sObjectType);
            $oObject->setId($sId);

            // some fine tuning..
            if ($sObjectType == "oxcategory") {
                $oObject->oxcategories__oxright = new oxField(11);
                $oObject->oxcategories__oxleft = new oxField(10);
            }

            $this->assertTrue($oObject->delete($sId), "Unable to delete $sObjectType type object");

            $this->assertFalse((bool) $oDb->getOne("select 1 from {$sTableName} where oxid = '{$sId}'"), "Not cleaned {$sTableName} table");
            $this->assertFalse((bool) $oDb->getOne("select 1 from {$sTableName}_set1 where oxid = '{$sId}'"), "Not cleaned {$sTableName}_set1 table");
        }
    }

    /**
     * Test helper to get language table name.
     */
    protected function getLanguageTableName($table, $languageId)
    {
        $dbMetaDataHandler = oxNew('oxDbMetaDataHandler');
        return $dbMetaDataHandler->getTableSetForLanguageAbbreviation($languageId, $table);
    }
}
