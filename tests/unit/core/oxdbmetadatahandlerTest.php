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

class Unit_Core_oxDbMetaDataHandlerTest extends OxidTestCase
{
    /**
     * Initialize the fixture.
     *
     * @return null
     */
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * Tear down the fixture.
     *
     * @return null
     */
    protected function tearDown()
    {
        $this->cleanUpTable('oxactions');

        //dorpping test table
        oxDb::getDb()->execute("DROP TABLE IF EXISTS `testDbMetaDataHandler`");

        parent::tearDown();
    }

    /**
     * Test table
     */
    protected function _createTestTable()
    {
        $sSql = " CREATE TABLE `testDbMetaDataHandler` (
                    `OXID` char(32) NOT NULL,
                    `OXTITLE` varchar(255) NOT NULL,
                    `OXTITLE_EN` varchar(255) NOT NULL,
                    `OXLONGDESC` text NOT NULL,
                    `OXLONGDESC_EN` text NOT NULL,
                     PRIMARY KEY (`OXID`),
                     KEY `OXTITLE` (`OXTITLE`),
                     KEY `OXTITLE_EN` (`OXTITLE_EN`),
                     FULLTEXT KEY `OXLONGDESC` (`OXLONGDESC`),
                     FULLTEXT KEY `OXLONGDESC_EN` (`OXLONGDESC_EN`)
                  ) ENGINE = MyISAM";

        oxDb::getDb()->execute($sSql);
    }

    /**
     * Test getting table fields list
     */
    public function testGetFields()
    {
        $sTable = 'oxreviews';
        $aTestFields = oxDb::getDb(oxDB::FETCH_MODE_ASSOC)->getAll("show columns from {$sTable}");
        $aFields = array();

        foreach ($aTestFields as $aField) {
            $aFields[$aField['Field']] = "{$sTable}.{$aField['Field']}";
        }

        $oDbMeta = oxNew("oxDbMetaDataHandler");
        $aTableFields = $oDbMeta->getFields("oxreviews");

        $this->assertTrue(count($aTableFields) > 0);
        $this->assertEquals($aFields, $aTableFields);
    }

    /**
     * Test if field name exists in given table
     */
    public function testFieldExists()
    {
        $oDbMeta = oxNew("oxDbMetaDataHandler");
        $this->assertTrue($oDbMeta->fieldExists("oxuserid", "oxreviews"));
        $this->assertTrue($oDbMeta->fieldExists("OXUSERID", "oxreviews"));
        $this->assertFalse($oDbMeta->fieldExists("oxblablabla", "oxreviews"));
        $this->assertFalse($oDbMeta->fieldExists("", "oxreviews"));

    }

    /**
     * Test if field name (camelCase) exists in given table
     */
    public function testFieldExistsCamelCase()
    {
        $oDbMeta = $this->getMock('oxDbMetaDataHandler', array('getFields'));
        $oDbMeta->expects($this->once())->method('getFields')->with('oxreviews')->will($this->returnValue(array("oxreviews.field1", 'oxreviews.field2Name', 'oxreviews.FIELD')));

        $this->assertTrue($oDbMeta->fieldExists("field2Name", "oxreviews"));
    }

    /**
     * Test getting all db tables list (except views)
     */
    public function testGetAllTables()
    {
        if ($this->getTestConfig()->getShopEdition() == 'EE') {
            $this->markTestSkipped('This test is for Community and Professional editions only.');
        }
        $aTables = oxDb::getDb()->getAll("show tables");

        foreach ($aTables as $aTableInfo) {
            if (false === strpos($aTableInfo[0], 'oxv_')) {
                $aTablesList[] = $aTableInfo[0];
            }
        }

        $oDbMeta = oxNew("oxDbMetaDataHandler");
        $this->assertTrue(count($aTablesList) > 1);
        $this->assertEquals($aTablesList, $oDbMeta->getAllTables());
    }

    /**
     * Test if returned sql for creating new table set is correct
     */
    public function testGetCreateTableSetSql()
    {
        $sTestSql = "CREATE TABLE `oxcountry_set1` (`OXID` char(32)  NOT NULL, PRIMARY KEY (`OXID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Countries list'";

        $oDbMeta = $this->getProxyClass("oxDbMetaDataHandler");

        //comparing in case insensitive form
        $this->assertEquals($sTestSql, $oDbMeta->UNITgetCreateTableSetSql("oxcountry", 'oxcountry_set1'), '', 0, 10, false, true);
    }

    /*
     * Test if returned sql for creating new table set is correct
     */
    public function testGetCreateTableSetSqlInIsoMode()
    {
        $this->setConfigParam('iUtfMode', 0);
        $sTestSql = "CREATE TABLE `oxcountry_set1` (`OXID` char(32) COLLATE latin1_general_ci NOT NULL, PRIMARY KEY (`OXID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Countries list'";

        $oDbMeta = $this->getProxyClass("oxDbMetaDataHandler");

        //comparing in case insensitive form
        $this->assertEquals($sTestSql, $oDbMeta->UNITgetCreateTableSetSql("oxcountry", 'oxcountry_set1'), '', 0, 10, false, true);
    }

    /**
     * Test if returned sql for creating new field is correct
     */
    public function testGetAddFieldSql()
    {
        $sTestSql = "alter TABLE `oxcountry` ADD `OXTITLE_LT` char(128) NOT NULL default ''  AFTER `OXTITLE_FR`";

        $oDbMeta = $this->getProxyClass("oxDbMetaDataHandler");

        //comparing in case insensitive form
        $this->assertEquals($sTestSql, $oDbMeta->getAddFieldSql("oxcountry", "OXTITLE", "OXTITLE_LT", "OXTITLE_FR"), '', 0, 10, false, true);
    }

    /**
     * Test if returned sql for creating new field indexes is correct
     */
    public function testAddFieldIndexSql()
    {
        $oDbMeta = $this->getProxyClass("oxDbMetaDataHandler");

        $aTestSql[0] = "ALTER TABLE `oxartextends` ADD FULLTEXT KEY  (`OXTAGS_LT`)";
        $this->assertEquals($aTestSql, $oDbMeta->getAddFieldIndexSql("oxartextends", "OXTAGS", "OXTAGS_LT"));

        $aTestSql[0] = "ALTER TABLE `oxartextends` ADD FULLTEXT KEY  (`OXTAGS_AA`)";
        $this->assertEquals($aTestSql, $oDbMeta->getAddFieldIndexSql("oxartextends", "OXTAGS", "OXTAGS_AA"));

        $aTestSql[0] = "ALTER TABLE `oxartextends_set1` ADD FULLTEXT KEY  (`OXTAGS_BB`)";
        $this->assertEquals($aTestSql, $oDbMeta->getAddFieldIndexSql("oxartextends", "OXTAGS", "OXTAGS_BB", "oxartextends_set1"));

        $aTestSql[0] = "ALTER TABLE `oxartextends_set2` ADD FULLTEXT KEY  (`OXTAGS_XY_YX`)";
        $this->assertEquals($aTestSql, $oDbMeta->getAddFieldIndexSql("oxartextends", "OXTAGS", "OXTAGS_XY_YX", "oxartextends_set2"));
    }

    /**
     * Test getting current max lang base id
     */
    public function testGetCurrentMaxLangIdDEPRECATED()
    {
        $this->markTestSkipped('deprecated');

        $oDbMeta = oxNew("oxDbMetaDataHandler");

        $this->assertEquals(3, $oDbMeta->getCurrentMaxLangId());
    }

    /**
     * Test getting next available base lang id
     */
    public function testGetNextLangIdDEPRECATED()
    {
        $this->markTestSkipped('deprecated');

        $oDbMeta = oxNew("oxDbMetaDataHandler");

        $this->assertEquals(4, $oDbMeta->getNextLangId());
    }

    /**
     * Test getting multilang fields from selected table
     */
    public function testGetMultilangFields()
    {
        $oDbMeta = oxNew("oxDbMetaDataHandler");
        $aRes = array("OXTITLE", "OXSHORTDESC", "OXLONGDESC");

        $this->assertEquals($aRes, $oDbMeta->getMultilangFields("oxcountry"));
    }

    /**
     * Test getting multilang fields from selected table which does not has any
     * multilang field
     */
    public function testGetMultilangFieldsFromNonMultilangTable()
    {
        $oDbMeta = oxNew("oxDbMetaDataHandler");
        $aRes = array();

        $this->assertEquals($aRes, $oDbMeta->getMultilangFields("oxuser"));
    }

    /**
     * Checks wether getSingleLangField returns fields in associative array
     */
    public function testGetSingleLangFields()
    {
        $aFields = array(
            'OXID'       => "OXID",
            'OXSHOPID_EN' => "oxarticles.OXSHOPID_EN",
            'OXPARENTID' => "oxarticles.OXPARENTID",
            'OXACTIVE'   => "oxarticles.OXACTIVE",
        );

        $aExpectedFields = array(
            'OXID',
            'OXSHOPID',
            'OXPARENTID',
            'OXACTIVE',
        );

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxDbMetaDataHandler', array('getFields'));
        $oDbMeta->expects($this->any())->method('getFields')->will($this->returnValue($aFields));
        $this->assertEquals($aExpectedFields, array_keys($oDbMeta->getSinglelangFields('oxarticles', 'en')));
    }

    /**
     * Test if method collects sql for creating table new multilang fields
     */
    public function testAddNewMultilangFieldAlterTableDEPRECATED()
    {
        $this->markTestSkipped('tetsing deprecated and no longer working function');

        $aTestSql[] = "ALTER TABLE `oxcountry` ADD `OXTITLE_XY_YX` char(128) NOT NULL default ''  AFTER `OXTITLE_FR`";
        $aTestSql[] = "ALTER TABLE `oxcountry` ADD `OXSHORTDESC_XY_YX` char(128) NOT NULL default ''  AFTER `OXSHORTDESC_FR`";
        $aTestSql[] = "ALTER TABLE `oxcountry` ADD `OXLONGDESC_XY_YX` char(255) NOT NULL default ''  AFTER `OXLONGDESC_FR`";

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('executeSql'));

        $oDbMeta->expects($this->once())->method('executeSql')->with($this->equalTo($aTestSql, 0, 10, false, true)); //case insensitive

        $oDbMeta->addNewMultilangField("oxcountry");
    }

    /**
     * Test if method collects sql for creating table new multilang fields
     */
    public function testAddNewMultilanguageFieldAlterTable()
    {
        $aTestSql[] = "ALTER TABLE `oxcountry` ADD `OXTITLE_XY_YX` char(128) NOT NULL DEFAULT '' AFTER `OXTITLE_FR`";
        $aTestSql[] = "ALTER TABLE `oxcountry` ADD `OXSHORTDESC_XY_YX` char(128) NOT NULL DEFAULT '' AFTER `OXSHORTDESC_FR`";
        $aTestSql[] = "ALTER TABLE `oxcountry` ADD `OXLONGDESC_XY_YX` char(255) NOT NULL DEFAULT '' AFTER `OXLONGDESC_FR`";

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('executeSql'));

        $oDbMeta->expects($this->once())->method('executeSql')->with($this->equalTo($aTestSql, 0, 10, false, true)); //case insensitive

        $oDbMeta->ensureMultiLanguageFields("oxcountry", 'xy_yx');
    }

    /**
     * Test if method collects sql for creating table new multilang fields
     */
    public function testAddNewMultilangFieldCreateTableDEPRECATED()
    {
        $this->markTestSkipped('testing deprecated and no longer functional code');

        $aTestSql[] = "CREATE TABLE `oxcountry_set1` (`OXID` char(32)  NOT NULL, PRIMARY KEY (`OXID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Countries list'";
        $aTestSql[] = "ALTER TABLE `oxcountry_set1` ADD `OXTITLE_8` char(128) NOT NULL DEFAULT '' ";
        $aTestSql[] = "ALTER TABLE `oxcountry_set1` ADD `OXSHORTDESC_8` char(128) NOT NULL DEFAULT '' ";
        $aTestSql[] = "ALTER TABLE `oxcountry_set1` ADD `OXLONGDESC_8` char(255) NOT NULL DEFAULT '' ";

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('executeSql', 'getCurrentMaxLangId'));
        $oDbMeta->expects($this->any())->method('getCurrentMaxLangId')->will($this->returnValue(7));
        $oDbMeta->expects($this->once())->method('executeSql')->with($this->equalTo($aTestSql, 0, 10, false, true)); //case insensitive

        $oDbMeta->addNewMultilangField("oxcountry");
    }


    /**
     * Test if method collects sql for creating table new multilang fields
     */
    public function testEnsureMultiLanguageFieldsCreateTable()
    {
        $aTestSql[] = "CREATE TABLE `oxcountry_set1` (`OXID` char(32)  NOT NULL, PRIMARY KEY (`OXID`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Countries list'";
        $aTestSql[] = "ALTER TABLE `oxcountry_set1` ADD `OXTITLE_XY_YX` char(128) NOT NULL DEFAULT ''";
        $aTestSql[] = "ALTER TABLE `oxcountry_set1` ADD `OXSHORTDESC_XY_YX` char(128) NOT NULL DEFAULT ''";
        $aTestSql[] = "ALTER TABLE `oxcountry_set1` ADD `OXLONGDESC_XY_YX` char(255) NOT NULL DEFAULT ''";

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('executeSql', 'getTableSetForLanguageAbbreviation'));
        $oDbMeta->expects($this->any())->method('getTableSetForLanguageAbbreviation')->will($this->returnValue('oxcountry_set1'));
        $oDbMeta->expects($this->once())->method('executeSql')->with($this->equalTo($aTestSql, 0, 10, false, true)); //case insensitive

        $oDbMeta->ensureMultiLanguageFields("oxcountry", 'XY_YX');
    }

    /**
     * Testing real db table update on adding new fields
     */
    public function testAddNewMultilangFieldUpdatesTableDEPRECATED()
    {
        $this->markTestSkipped('testing deprecated and no longer functional code');

        $this->_createTestTable();

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('getCurrentMaxLangId'));
        $oDbMeta->expects($this->any())->method('getCurrentMaxLangId')->will($this->returnValue(1));

        $oDbMeta->addNewMultilangField("testDbMetaDataHandler");

        $aTestFields = oxDb::getDb(oxDB::FETCH_MODE_ASSOC)->getAll("show columns from testDbMetaDataHandler");
        $aFileds = array();

        foreach ($aTestFields as $aField) {
            $aFileds[] = $aField["Field"];
        }

        $this->assertTrue(in_array("OXTITLE_2", $aFileds));
        $this->assertTrue(in_array("OXLONGDESC_2", $aFileds));
    }

    /**
     * Testing real db table update on adding new fields
     */
    public function testEnsureMultiLanguageFieldsUpdatesTable()
    {
        $this->_createTestTable();

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('getTableSetForLanguageAbbreviation'));
        $oDbMeta->expects($this->any())->method('getTableSetForLanguageAbbreviation')->will($this->returnValue('testDbMetaDataHandler'));

        $oDbMeta->ensureMultiLanguageFields("testDbMetaDataHandler", 'XY_YX');

        $aTestFields = oxDb::getDb(oxDB::FETCH_MODE_ASSOC)->getAll("show columns from testDbMetaDataHandler");
        $aFileds = array();

        foreach ($aTestFields as $aField) {
            $aFileds[] = $aField["Field"];
        }

        $this->assertTrue(in_array("OXTITLE_XY_YX", $aFileds));
        $this->assertTrue(in_array("OXLONGDESC_XY_YX", $aFileds));
    }


    /**
     * Testing real db table update on adding correct indexes
     */
    public function testAddNewMultilangFieldUpdatesTableIndexesDEPRECATED()
    {
        $this->markTestSkipped('testing deprecated and no longer functional code');

        $this->_createTestTable();

        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('getCurrentMaxLangId'));
        $oDbMeta->expects($this->any())->method('getCurrentMaxLangId')->will($this->returnValue(1));

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta->addNewMultilangField("testDbMetaDataHandler");

        $aIndexes = oxDb::getDb(oxDB::FETCH_MODE_ASSOC)->getAll("show index from testDbMetaDataHandler");

        $this->assertEquals("PRIMARY", $aIndexes[0]["Key_name"]);
        $this->assertEquals("OXID", $aIndexes[0]["Column_name"]);

        //checking newly added index for column OXTITLE
        $this->assertEquals("OXTITLE", $aIndexes[1]["Key_name"]);
        $this->assertEquals("OXTITLE", $aIndexes[1]["Column_name"]);
        $this->assertEquals("OXTITLE_1", $aIndexes[2]["Key_name"]);
        $this->assertEquals("OXTITLE_1", $aIndexes[2]["Column_name"]);
        $this->assertEquals("OXTITLE_2", $aIndexes[3]["Key_name"]);
        $this->assertEquals("OXTITLE_2", $aIndexes[3]["Column_name"]);


        //checking newly added index for column OXLONGDESC
        $this->assertEquals("OXLONGDESC", $aIndexes[4]["Key_name"]);
        $this->assertEquals("OXLONGDESC", $aIndexes[4]["Column_name"]);
        $this->assertEquals("OXLONGDESC_1", $aIndexes[5]["Key_name"]);
        $this->assertEquals("OXLONGDESC_1", $aIndexes[5]["Column_name"]);
        $this->assertEquals("OXLONGDESC_2", $aIndexes[6]["Key_name"]);
        $this->assertEquals("OXLONGDESC_2", $aIndexes[6]["Column_name"]);
        $this->assertEquals("FULLTEXT", $aIndexes[6]["Index_type"]);
    }

    /**
     * Testing real db table update on adding correct indexes
     */
    public function testEnsureMultiLanguageFieldsUpdatesTableIndexes()
    {
        $this->_createTestTable();

        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('getTableSetForLanguageAbbreviation'));
        $oDbMeta->expects($this->any())->method('getTableSetForLanguageAbbreviation')->will($this->returnValue('testDbMetaDataHandler'));

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta->ensureMultiLanguageFields("testDbMetaDataHandler", 'XY_YX');

        $aIndexes = oxDb::getDb(oxDB::FETCH_MODE_ASSOC)->getAll("show index from testDbMetaDataHandler");

        $this->assertEquals("PRIMARY", $aIndexes[0]["Key_name"]);
        $this->assertEquals("OXID", $aIndexes[0]["Column_name"]);

        //checking newly added index for column OXTITLE
        $this->assertEquals("OXTITLE", $aIndexes[1]["Key_name"]);
        $this->assertEquals("OXTITLE", $aIndexes[1]["Column_name"]);
        $this->assertEquals("OXTITLE_EN", $aIndexes[2]["Key_name"]);
        $this->assertEquals("OXTITLE_EN", $aIndexes[2]["Column_name"]);
        $this->assertEquals("OXTITLE_XY_YX", $aIndexes[3]["Key_name"]);
        $this->assertEquals("OXTITLE_XY_YX", $aIndexes[3]["Column_name"]);

        //checking newly added index for column OXLONGDESC
        $this->assertEquals("OXLONGDESC", $aIndexes[4]["Key_name"]);
        $this->assertEquals("OXLONGDESC", $aIndexes[4]["Column_name"]);
        $this->assertEquals("OXLONGDESC_EN", $aIndexes[5]["Key_name"]);
        $this->assertEquals("OXLONGDESC_EN", $aIndexes[5]["Column_name"]);
        $this->assertEquals("OXLONGDESC_XY_YX", $aIndexes[6]["Key_name"]);
        $this->assertEquals("OXLONGDESC_XY_YX", $aIndexes[6]["Column_name"]);
        $this->assertEquals("FULLTEXT", $aIndexes[6]["Index_type"]);
    }

    /**
     * Testing real db table update on adding correct indexes
     */
    public function testAddNewMultilangFieldAddsTableDEPRECATED()
    {
        $this->markTestSkipped('testing deprecated and no longer functional code');

        $this->_createTestTable();

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('getCurrentMaxLangId'));
        $oDbMeta->expects($this->any())->method('getCurrentMaxLangId')->will($this->returnValue(8));

        $oDbMeta->addNewMultilangField("testDbMetaDataHandler");
    }

    /**
     * Testing real db table update on adding correct indexes
     */
    public function testEnsureMultiLanguageFieldsAddsTable()
    {
        $this->_createTestTable();

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxdbmetadatahandler',array( 'getTableSetForLanguageAbbreviation'));
        $oDbMeta->expects($this->any())->method('getTableSetForLanguageAbbreviation')->will($this->returnValue('testDbMetaDataHandler_set1'));

        $oDbMeta->ensureMultiLanguageFields("testDbMetaDataHandler", 'xy_yx');
        $this->assertTrue($oDbMeta->tableExists('testDbMetaDataHandler_set1'));
    }

    /**
     * Test if method call another method which creates sql's with correct params
     */
    public function testAddNewLangToDbDEPRECATED()
    {
        $this->markTestSkipped('testing deprecated and no longer functional code');

        if ($this->getTestConfig()->getShopEdition() == 'EE') {
            $this->markTestSkipped('This test is for Community and Professional editions only.');
        }
        $aTables = oxDb::getDb()->getAll("show tables");

        $aTablesList = array();
        foreach ($aTables as $aTableInfo) {
            $aTablesList[] = $aTableInfo[0];
        }

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock('oxDbMetaDataHandler', array('addNewMultilangField'));

        $iIndex = 0;
        foreach ($aTablesList as $sTableName) {
            $oDbMeta->expects($this->at($iIndex++))->method('addNewMultilangField')->with($this->equalTo($sTableName));
        }

        $oDbMeta->addNewLangToDb();
    }

    /**
     * Test if method call another method which creates sql's with correct params
     */
    public function testAddNewLanguageToDb()
    {
        if ($this->getTestConfig()->getShopEdition() == 'EE') {
            $this->markTestSkipped('This test is for Community and Professional editions only.');
        }
        $aTables = oxDb::getDb()->getAll("show tables");

        $aTablesList = array();
        foreach ($aTables as $aTableInfo) {
            if (false === strpos($aTableInfo[0], 'oxv_')) {
                $aTablesList[] = $aTableInfo[0];
            }
        }

        /** @var oxDbMetaDataHandler|PHPUnit_Framework_MockObject_MockObject $oDbMeta */
        $oDbMeta = $this->getMock($this->getProxyClassName('oxDbMetaDataHandler'), array('safeGuardAdditionalMultiLanguageTables', 'ensureMultiLanguageFields'));
        $oDbMeta->expects($this->at(0))->method('safeGuardAdditionalMultiLanguageTables');

        $iIndex = 1;
        foreach ($aTablesList as $sTableName) {
            $oDbMeta->expects($this->at($iIndex))->method('ensureMultiLanguageFields')->with($this->equalTo($sTableName),
                $this->equalTo('ab_ba'));
            $iIndex++;
        }

        $oDbMeta->addNewLanguageToDb('ab_ba');
    }

    /**
     * Test method executes sql's array
     */
    public function testExecuteSql()
    {
        $aSql[] = " insert into oxactions set oxid='_testId1', oxtitle = 'testValue1' ";
        $aSql[] = " insert into oxactions set oxid='_testId2', oxtitle = 'testValue2' ";

        $oDbMeta = $this->getProxyClass("oxDbMetaDataHandler");
        $oDbMeta->executeSql($aSql);

        $oDb = oxDb::getDb();
        $this->assertEquals(1, $oDb->getOne("select 1 from oxactions where oxid='_testId1' and oxtitle = 'testValue1' "));
        $this->assertEquals(1, $oDb->getOne("select 1 from oxactions where oxid='_testId2' and oxtitle = 'testValue2' "));
    }

    /**
     * Test if nothing is executed if param is not an array
     */
    public function testExecuteSqlWhenParamIsNotSql()
    {
        $sSql = " insert into oxactions set oxid='_testId1', oxtitle = 'testValue1' ";

        $oDbMeta = $this->getProxyClass("oxDbMetaDataHandler");
        $oDbMeta->executeSql($sSql);

        $oDb = oxDb::getDb();
        $this->assertFalse($oDb->getOne("select 1 from oxactions where oxid='_testId1' and oxtitle = 'testValue1' "));
    }

    /**
     * Testing reseting multilangual fields
     */
    public function testResetMultilangFields()
    {
        $this->_createTestTable();
        $oDbMeta = $this->getProxyClass("oxDbMetaDataHandler");

        // inserting test data
        $sSql = "INSERT INTO testDbMetaDataHandler SET
                    OXTITLE = 'aaa',
                    OXLONGDESC = 'bbb',
                    OXTITLE_EN = 'aaa 1',
                    OXLONGDESC_EN = 'bbb 1'
                ";

        oxDb::getDb(oxDB::FETCH_MODE_ASSOC)->execute($sSql);

        $oDbMeta->resetMultilangFields('en', "testDbMetaDataHandler");

        $aRes = oxDb::getDb(oxDB::FETCH_MODE_ASSOC)->getAll("SELECT * FROM testDbMetaDataHandler");

        $this->assertEquals("aaa", $aRes[0]["OXTITLE"]);
        $this->assertEquals("bbb", $aRes[0]["OXLONGDESC"]);
        $this->assertEquals("", $aRes[0]["OXTITLE_EN"]);
        $this->assertEquals("", $aRes[0]["OXLONGDESC_EN"]);
    }

    /**
     * Testing resetting multilingual fields when language ID is empty.
     * Cannot reset cause fields like OXTITLE_ do not exist.
     */
    public function testResetMultilangFields_skipsResetingWhenIdIsZero()
    {
        $this->_createTestTable();

        $oDb = oxDb::getDb(oxDB::FETCH_MODE_ASSOC);
        $oDbMeta = $this->getProxyClass("oxDbMetaDataHandler");

        // inserting test data
        $sSql = "INSERT INTO testDbMetaDataHandler SET
                    OXTITLE = 'aaa',
                    OXLONGDESC = 'bbb'
                ";

        oxDb::getDb(oxDB::FETCH_MODE_ASSOC)->execute($sSql);

        $oDbMeta->resetMultilangFields('', "testDbMetaDataHandler");

        $aRes = oxDb::getDb(oxDB::FETCH_MODE_ASSOC)->getAll("SELECT * from testDbMetaDataHandler");

        $this->assertEquals("aaa", $aRes[0]["OXTITLE"]);
        $this->assertEquals("bbb", $aRes[0]["OXLONGDESC"]);
    }

    /**
     * Testing if method calls method "resetMultilangFields" with correct params
     */
    public function testResetLanguage()
    {
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('getAllTables', 'resetMultilangFields'));
        $oDbMeta->expects($this->once())->method('getAllTables')->will($this->returnValue(array("testTable1", "testTable2")));
        $oDbMeta->expects($this->at(1))->method('resetMultilangFields')->with($this->equalTo(1), $this->equalTo("testTable1"));
        $oDbMeta->expects($this->at(2))->method('resetMultilangFields')->with($this->equalTo(1), $this->equalTo("testTable2"));

        $oDbMeta->resetLanguage(1, "testDbMetaDataHandler");
    }

    /**
     * Testing if method does nothing then language id = 0
     */
    public function testResetLanguage_zeroId()
    {
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('getAllTables', 'resetMultilangFields'));
        $oDbMeta->expects($this->never())->method('getAllTables');
        $oDbMeta->expects($this->never())->method('resetMultilangFields');

        $oDbMeta->resetLanguage(0, "testDbMetaDataHandler");
    }

    /**
     * Testing if method does nothing then language id = ''
     */
    public function testResetLanguageEmptyId()
    {
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('getAllTables', 'resetMultilangFields'));
        $oDbMeta->expects($this->never())->method('getAllTables');
        $oDbMeta->expects($this->never())->method('resetMultilangFields');

        $oDbMeta->resetLanguage('', "testDbMetaDataHandler");
    }

    /**
     * Testing if method skips tables that does not requires reset (oxcountry)
     */
    public function testResetLanguage_skipTables()
    {
        $oDbMeta = $this->getMock('oxdbmetadatahandler', array('getAllTables', 'resetMultilangFields'));
        $oDbMeta->expects($this->once())->method('getAllTables')->will($this->returnValue(array("oxcountry", "testTable2")));
        $oDbMeta->expects($this->once())->method('resetMultilangFields')->with($this->equalTo('en'), $this->equalTo("testTable2"));

        $oDbMeta->resetLanguage('en', "testDbMetaDataHandler");
    }

    /**
     * Check whether merging inside getSingleLangFields returns proper values
     *
     * @bug https://bugs.oxid-esales.com/view.php?id=5990
     */
    public function testGetSingleLangFieldsForTableSet1Language()
    {
        /** @var oxDbMetaDataHandler $oHandler */
        $oHandler = $this->getMock('oxDbMetaDataHandler', array('getTableSetForLanguageAbbreviation', 'getFields'));
        $oHandler->expects($this->at(0))->method('getTableSetForLanguageAbbreviation')->will($this->returnValue('oxarticles_set1'));
        $oHandler->expects($this->at(1))->method('getFields')->will($this->returnValue(
            array(
                'OXID' => 'oxarticles.OXID',
                'OXVARNAME_EN' => 'oxarticles.OXVARNAME_EN',
                'OXVARSELECT_EN' => 'oxarticles.OXVARSELECT_EN'
            )
        ));
        $oHandler->expects($this->at(2))->method('getFields')->will($this->returnValue(
            array(
                'OXID' => 'oxarticles_set1.OXID',
                'OXVARNAME_XY_YX' => 'oxarticles_set1.OXVARNAME_XY_YX',
                'OXVARSELECT_XY_YX' =>'oxarticles_set1.OXVARSELECT_XY_YX'
            )
        ));
        $aExpectedResult = array(
            'OXID' => 'oxarticles.OXID',
            'OXVARNAME' => 'oxarticles_set1.OXVARNAME_XY_YX',
            'OXVARSELECT' =>'oxarticles_set1.OXVARSELECT_XY_YX'
        );

        $this->assertEquals($aExpectedResult, $oHandler->getSinglelangFields('oxarticles', 'XY_YX'));
    }
}
