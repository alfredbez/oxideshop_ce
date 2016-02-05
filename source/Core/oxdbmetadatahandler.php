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

/**
 * Class for handling database related operations
 *
 */
class oxDbMetaDataHandler extends oxSuperCfg
{

    /**
     *
     * @var array
     */
    protected $_aDbTablesFields = null;


    /**
     *
     * @var array
     */
    protected $_aTables = null;

    /**
     *
     * @var int
     */
    protected $_iCurrentMaxLangId;

    /**
     *
     * @var array Tables which should be skipped from resetting
     */
    protected $_aSkipTablesOnReset = array("oxcountry");

    /**
     * When creating views, always use those fields from core table.
     *
     * @var array
     */
    protected $forceOriginalFields = array('OXID');

    /**
     *  Get table fields
     *
     * @param string $sTableName  table name
     *
     * @return array
     */
    public function getFields($sTableName)
    {
        $aFields = array();
        $aRawFields = oxDb::getDb()->MetaColumns($sTableName);
        if (is_array($aRawFields)) {
            foreach ($aRawFields as $oField) {
                $aFields[$oField->name] = "{$sTableName}.{$oField->name}";
            }
        }

        return $aFields;
    }

    /**
     * Check if table exists
     *
     * @param string $sTableName table name
     *
     * @return bool
     */
    public function tableExists($sTableName)
    {
        $oDb = oxDb::getDb();
        $aTables = $oDb->getAll("show tables like " . $oDb->quote($sTableName));

        return count($aTables) > 0;
    }

    /**
     * Check if field exists in table
     *
     * @param string $sFieldName field name
     * @param string $sTableName table name
     *
     * @return bool
     */
    public function fieldExists($sFieldName, $sTableName)
    {
        $aTableFields = $this->getFields($sTableName);
        $sTableName = strtoupper($sTableName);
        if (is_array($aTableFields)) {
            $sFieldName = strtoupper($sFieldName);
            $aTableFields = array_map('strtoupper', $aTableFields);
            if (in_array("{$sTableName}.{$sFieldName}", $aTableFields)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Get all tables names from db. Views tables are not included in
     * this list.
     *
     * @return array
     */
    public function getAllTables()
    {
        if (empty($this->_aTables)) {
            $aTables = oxDb::getDb()->getAll("show tables");

            foreach ($aTables as $aTableInfo) {
                if ($this->validateTableName($aTableInfo[0])) {
                    $this->_aTables[] = $aTableInfo[0];
                }
            }
        }

        return $this->_aTables;
    }

    /**
     * return all DB tables for the language sets
     *
     * @param string $table table name to check
     *
     * @return array
     */
    public function getAllMultiTables($table)
    {
        $result = $this->getLanguageSetTables($table);
        array_shift($result); //first array element is for core table itself
        return $result;
    }

    /**
     * Get sql for new multi-language table set creation
     *
     * @param string $coreTable core table name
     * @param string $tableSet  table set to be created
     *
     * @return string
     *
     */
    protected function _getCreateTableSetSql($coreTable, $tableSet)
    {
        $result = oxDb::getDb()->getAll("show create table {$coreTable}");
        $collation = $this->getConfig()->isUtf() ? '' : 'COLLATE latin1_general_ci';
        $query = "CREATE TABLE `{$tableSet}` (" .
                 "`OXID` char(32) $collation NOT NULL, " .
                 "PRIMARY KEY (`OXID`)" .
                 ") " . strstr($result [0][1], 'ENGINE=');

        return $query;
    }

    /**
     * Get sql for new multi-language field creation
     *
     * @param string $sTable     core table name
     * @param string $sField     field name
     * @param string $sNewField  new field name
     * @param string $sPrevField previous field in table
     * @param string $sTableSet  table to change (if not set take core table)
     *
     * @return string
     */
    public function getAddFieldSql($sTable, $sField, $sNewField, $sPrevField, $sTableSet = null)
    {
        if (!$sTableSet) {
            $sTableSet = $sTable;
        }
        $aRes = oxDb::getDb()->getAll("show create table {$sTable}");
        $sTableSql = $aRes[0][1];

        // removing comments;
        $sTableSql = preg_replace('/COMMENT \\\'.*?\\\'/', '', $sTableSql);
        preg_match("/.*,\s+(['`]?" . preg_quote($sField, '/') . "['`]?\s+[^,]+),.*/", $sTableSql, $aMatch);
        $sFieldSql = $aMatch[1];

        $sSql = "";
        if (!empty($sFieldSql)) {
            $sFieldSql = preg_replace("/" . preg_quote($sField, '/') . "/", $sNewField, $sFieldSql);
            $sSql = "ALTER TABLE `$sTableSet` ADD " . $sFieldSql;
            if ($this->tableExists($sTableSet) && $this->fieldExists($sPrevField, $sTableSet)) {
                $sSql .= " AFTER `$sPrevField`";
            }
        }

        return $sSql;
    }


    /**
     * Get sql for new multi-language field index creation
     *
     * @param string $sTable    core table name
     * @param string $sField    field name
     * @param string $sNewField new field name
     * @param string $sTableSet table to change (if not set take core table)
     *
     * @return string
     */
    public function getAddFieldIndexSql($sTable, $sField, $sNewField, $sTableSet = null)
    {
        $aRes = oxDb::getDb()->getAll("show create table {$sTable}");

        $sTableSql = $aRes[0][1];

        preg_match_all("/([\w]+\s+)?\bKEY\s+(`[^`]+`)?\s*\([^)]+\)/iU", $sTableSql, $aMatch);
        $aIndex = $aMatch[0];

        $blUsingTableSet = $sTableSet ? true : false;

        if (!$sTableSet) {
            $sTableSet = $sTable;
        }

        $aIndexSql = array();
        $aSql = array();
        if (count($aIndex)) {
            foreach ($aIndex as $sIndexSql) {
                if (preg_match("/\([^)]*\b" . $sField . "\b[^)]*\)/i", $sIndexSql)) {

                    //removing index name - new will be added automaticly
                    $sIndexSql = preg_replace("/(.*\bKEY\s+)`[^`]+`/", "$1", $sIndexSql);

                    if ($blUsingTableSet) {
                        // replacing multiple fields to one (#3269)
                        $sIndexSql = preg_replace("/\([^\)]+\)/", "(`$sNewField`)", $sIndexSql);
                    } else {
                        //replacing previous field name with new one
                        $sIndexSql = preg_replace("/\b" . $sField . "\b/", $sNewField, $sIndexSql);
                    }

                    $aIndexSql[] = "ADD " . $sIndexSql;
                }
            }
            if (count($aIndexSql)) {
                $aSql = array("ALTER TABLE `$sTableSet` " . implode(", ", $aIndexSql));
            }
        }

        return $aSql;
    }

    /**
     * @deprecated in b-dev
     *
     * Get max language ID used in shop. For checking is used table "oxarticle"
     * field "oxtitle"
     *
     * @return int
     */
    public function getCurrentMaxLangId()
    {
        if (isset($this->_iCurrentMaxLangId)) {
            return $this->_iCurrentMaxLangId;
        }

        $sTable = $sTableSet = "oxarticles";
        $sField = $sFieldSet = "oxtitle";
        $iLang = 0;
        while ($this->tableExists($sTableSet) && $this->fieldExists($sFieldSet, $sTableSet)) {
            $iLang++;
            $sTableSet = getLangTableName($sTable, $iLang);
            $sFieldSet = $sField . '_' . $iLang;
        }

        $this->_iCurrentMaxLangId = --$iLang;

        return $this->_iCurrentMaxLangId;
    }

    /**
     * @deprecated in b-dev
     *
     * Get next available language ID
     *
     * @return int
     */
    public function getNextLangId()
    {
        return $this->getCurrentMaxLangId() + 1;
    }

    /**
     * Get table multi-language fields.
     *
     * @param string $sTable table name
     *
     * @return array
     */
    public function getMultilangFields($table)
    {
        $fields = $this->getFields($table);
        $multiLanguageFields = array();

        foreach ($fields as $field) {
            if (preg_match("/({$table}\.)?(?<field>[^_]+)_([a-zA-Z0-9_]+)?$/", $field, $matches)) {
                $multiLanguageFields[$matches['field']] = $matches['field'];
            }
        }
        $multiLanguageFields = array_keys($multiLanguageFields);

        return $multiLanguageFields;
    }

    public function getSinglelangFields($table, $languageId)
    {
        $table = strtolower($table);
        $languageTable = $this->getTableSetForLanguageAbbreviation($languageId, $table);

        $baseFields = $this->getFields($table);
        $languageFields = $this->getFields($languageTable);

        //Some fields (for example OXID) must be taken from core table.
        $languageFields = $this->filterCoreFields($languageFields);

        $fields = array_merge($baseFields, $languageFields);
        $singleLanguageFields = array();

        foreach ($fields as $fieldName => $field) {
            if (preg_match("/(({$table}|{$languageTable})\.)?(?<field>[^_]+)_(?<lang>[a-zA-Z0-9_]+)$/", $field, $matches)) {
                if (ltrim($matches['lang'], '_') == strtoupper($languageId)) {
                    $singleLanguageFields[$matches['field']] = $field;
                }
            } else {
                $tmp = explode('.', $field);
                if (false === strpos($tmp[1], '_')) {
                    $singleLanguageFields[$fieldName] = $field;
                }
            }
        }

        return $singleLanguageFields;
    }

    /**
     * @deprecated in b-dev
     *
     * Add new multi-languages fields to table. Duplicates all multi-language
     * fields and fields indexes with next available language ID
     *
     * @param string $table table name
     */
    public function addNewMultilangField($table)
    {
        $newLang = $this->getNextLangId();
        $this->ensureMultiLanguageFields($table, $newLang);
    }

    /**
     * Resetting all multi-language fields with specific language id
     * to default value in selected table
     *
     * @param int    $iLangId    Language id
     * @param string $sTableName Table name
     *
     * @return null
     */
    public function resetMultilangFields($iLangId, $sTableName)
    {
        $aSql = array();

        $aFields = $this->getMultilangFields($sTableName);
        if (is_array($aFields) && count($aFields) > 0) {
            foreach ($aFields as $sFieldName) {
                $sFieldName = $sFieldName . "_" . $iLangId;

                if ($this->fieldExists($sFieldName, $sTableName)) {
                    //resetting field value to default
                    $aSql[] = "UPDATE {$sTableName} SET {$sFieldName} = DEFAULT;";
                }
            }
        }

        if (!empty($aSql)) {
            $this->executeSql($aSql);
        }
    }

    /**
     * @deprecated in b-dev
     *
     * Add new language to database. Scans all tables and adds new
     * multi-language fields
     */
    public function addNewLangToDb()
    {
        //reset max count
        $this->_iCurrentMaxLangId = null;

        $aTable = $this->getAllTables();

        foreach ($aTable as $sTableName) {
            $this->addNewMultilangField($sTableName);
        }

        //updating views
        $this->updateViews();
    }

    /**
     * Add new language to database. Scans all tables and adds new
     * multi-language fields
     *
     * @param $languageId Language id/abbreviation to add.
     */
    public function addNewLanguageToDb($languageId)
    {
        $this->safeGuardAdditionalMultiLanguageTables();
        $allTables = $this->getAllTables();

        foreach ($allTables as $table) {
            $this->ensureMultiLanguageFields($table, $languageId);
        }

        //updating views
        $this->updateViews();
    }

    /**
     * Resetting all multi-language fields with specific language id
     * to default value in all tables. Only if language ID > 0.
     *
     * @param int $iLangId Language id
     *
     * @return null
     */
    public function resetLanguage($iLangId)
    {
        if (empty($iLangId)) {
            return;
        }

        $aTables = $this->getAllTables();

        // removing tables which does not requires reset
        foreach ($this->_aSkipTablesOnReset as $sSkipTable) {

            if (($iSkipId = array_search($sSkipTable, $aTables)) !== false) {
                unset($aTables[$iSkipId]);
            }
        }

        foreach ($aTables as $sTableName) {
            $this->resetMultilangFields($iLangId, $sTableName);
        }
    }

    /**
     * Executes array of sql strings
     *
     * @param array $aSql SQL query array
     */
    public function executeSql($aSql)
    {
        $oDb = oxDb::getDb();

        if (is_array($aSql) && !empty($aSql)) {
            foreach ($aSql as $sSql) {
                $sSql = trim($sSql);
                if (!empty($sSql)) {
                    $oDb->execute($sSql);
                }
            }
        }
    }

    /**
     * Updates all views
     *
     * @param array $aTables array of DB table name that can store different data per shop like oxArticle
     *
     * @return bool
     */
    public function updateViews($aTables = null)
    {
        set_time_limit(0);

        $oDb = oxDb::getDb();
        $oConfig = oxRegistry::getConfig();

        $this->safeGuardAdditionalMultiLanguageTables();

        $aShops = $oDb->getAll("select * from oxshops");

        $aTables = $aTables ? $aTables : $oConfig->getConfigParam('aMultiShopTables');

        $bSuccess = true;
        foreach ($aShops as $aShop) {
            $sShopId = $aShop[0];
            $oShop = oxNew('oxShop');
            $oShop->load($sShopId);
            $oShop->setMultiShopTables($aTables);
            $aMallInherit = array();
            foreach ($aTables as $sTable) {
                $aMallInherit[$sTable] = $oConfig->getShopConfVar('blMallInherit_' . $sTable, $sShopId);
            }
            if (!$oShop->generateViews(false, $aMallInherit) && $bSuccess) {
                $bSuccess = false;
            }
        }

        return $bSuccess;
    }

    /**
     * Get last multilanguage field abbreviation.
     *
     * @param $table
     *
     * @return string
     */
    public function getPreviousMultilanguageField($table, $matchField)
    {
        $match = null;
        if (!$this->tableExists($table)){
            return $match;
        }

        $allFields = $this->getFields($table);
        $matchField = strtoupper($matchField) . '_';
        $match = null;

        foreach ($allFields as $key => $field) {
            if (false !== strpos($key, $matchField)) {
                $match = $key;
            }
        }
        return $match;
    }

    /**
     * Determine which set table has a free slot for a new language abbreviation.
     */
    public function getNextFreeSlotInTableSet($table = 'oxarticles', $field = 'oxtitle')
    {
        $result = null;
        $map = $this->getLanguage2TableSetMap($table, $field);
        $languagesPerTable = getLanguagesPerTable();

        //core table is first in line and this one already contains the template multilanguage fields (OXTITLE etc.)
        $languageCount = array($table => 1);

        foreach ($map as $languageAbbreviation => $setTable) {
            $languageCount[$setTable] += 1;
        }

        foreach ($languageCount as $setTable => $counted) {
            if ($languagesPerTable > $counted){
                $result = $setTable;
                break;
            }
        }

        if (is_null($result)) {
            $result = $this->getNextLanguageSetTable($table);
        }

        return $result;
    }

    /**
     * Figure out which set the language is assigned or should be assigned to.
     *
     * @param $languageAbbreviation
     *
     * @return null| string
     */
    public function getTableSetForLanguageAbbreviation($languageAbbreviation, $table = 'oxarticles')
    {
        $return = null;
        $multilanguageFields = $this->getMultilangFields($table);
        $field = strtolower($multilanguageFields[0]);
        $languageAbbreviation = strtolower($languageAbbreviation);

        $map = $this->getLanguage2TableSetMap($table, $field);
        if (isset($map[$languageAbbreviation])) {
            $return = $map[$languageAbbreviation];
        }

        if (is_null($return)) {
            $return = $this->getNextFreeSlotInTableSet($table, $field);
        }

        return $return;
    }

    /**
     * Get language abbreviation to table set relation.
     *
     * @param string $coreTable
     * @param string $coreField
     *
     * @return array
     */
    public function getLanguage2TableSetMap($coreTable = 'oxarticles', $coreField = 'oxtitle')
    {
        $language2TableSet = array();
        $existingTableSets = $this->getLanguageSetTables($coreTable);
        $coreField = strtolower($coreField);

        foreach ($existingTableSets as $tableSet) {
            $language2TableSet = array_merge($language2TableSet, $this->getTableSetLanguages($tableSet, $coreField));
        }

        return $language2TableSet;
    }

    /**
     * Get existing language set tables.
     *
     * @return array
     */
    public function getLanguageSetTables($table = 'oxarticles')
    {
        $suffix = getLanguageTableSuffix();
        $counter = 1;

        $tables = array(0 => $table);
        $tableSet = $table . $suffix . $counter;

        while ($this->tableExists($tableSet)) {
            $tables[$counter] = $tableSet;
            $counter++;
            $tableSet = $table . $suffix . $counter;
        }

        return $tables;
    }

    /**
     * Get name of next table set to be created.
     *
     * @return array
     */
    public function getNextLanguageSetTable($table = 'oxarticles')
    {
        $existingTableSets = $this->getLanguageSetTables($table);
        $lastTableName = array_pop($existingTableSets);
        $suffix = getLanguageTableSuffix();

        $temporary = explode($suffix, $lastTableName);
        $counter = (int) $temporary[1];
        $counter++;

        $result = $table . $suffix . $counter;
        return $result;
    }

    /**
     * Get all language abbreviations that are already available in a set table.
     *
     * @param $tableSet
     * @param $coreField
     *
     * @return array
     */
    protected function getTableSetLanguages($tableSet, $coreField)
    {
        $result = array();
        $rawFields = oxDb::getDb()->MetaColumns($tableSet);
        if (!is_array($rawFields)) {
            return $result;
        }

        foreach ($rawFields as $fieldObject) {
            $field = strtolower($fieldObject->name);
            if (0 === strpos($field, $coreField . '_')) {
                $temp = explode('_', $field);
                $temp = explode($temp[0] . '_', $field);
                if (!empty($temp[1])) {
                    $result[$temp[1]] = $tableSet;
                }
            }
        }
        return $result;
    }


    /**
     * Make sure that e.g. OXID is always used from core table when creating views.
     * Otherwise we might have unwanted side effects from rows with OXIDs null in view tables.
     *
     * @param $fields Language fields array we need to filter for core fields.
     *
     * @return array
     */
    protected function filterCoreFields($fields)
    {
        foreach ($this->forceOriginalFields as $fieldname) {
            if (array_key_exists($fieldname, $fields)) {
                unset($fields[$fieldname]);
            }
        }
        return $fields;
    }

    /**
     * Ensure that all *_set* tables for all tables in config parameter 'aMultiLangTables'
     * are created.
     *
     * @return null
     */
    protected function safeGuardAdditionalMultiLanguageTables()
    {
        $multiLanguageTables = $this->getConfig()->getConfigParam('aMultiLangTables');
        if (!is_array($multiLanguageTables) || empty($multiLanguageTables)) {
            return; //nothing to do
        }

        $languages = array_keys($this->getLanguage2TableSetMap());

        foreach ($multiLanguageTables as $table) {
            if ($this->tableExists($table)) {
                //run over all languageIds that have fields for core tables (and their set tables)
                foreach ($languages as $languageId) {
                    $this->ensureMultiLanguageFields($table, $languageId);
                }
            }
        }
    }

    /**
     * Make sure that all *_set* tables with all required multilanguage fields are created.
     *
     * @param $table
     * @param $languageAbbreviation
     *
     * @return null
     */
    protected function ensureMultiLanguageFields($table, $languageAbbreviation)
    {
        $multilanguageFields = $this->getMultilangFields($table);
        $tableSet = $this->getTableSetForLanguageAbbreviation($languageAbbreviation, $table);
        $abbreviations = array_keys($this->getLanguage2TableSetMap($table, $multilanguageFields[0]));
        $firstAbbreviation = array_shift($abbreviations);
        $queries = array();

        if (is_array($multilanguageFields) && count($multilanguageFields) > 0) {

            if (!$this->tableExists($tableSet)) {
                $queries[] = $this->_getCreateTableSetSql($table, $tableSet);
            }

            foreach ($multilanguageFields as $field) {
                $newFieldName = strtoupper($field . "_" . $languageAbbreviation);
                $previousField = $this->getPreviousMultilanguageField($tableSet, $field);
                $templateField = strtoupper($field . "_" . $firstAbbreviation);

                if (!$this->tableExists($tableSet) || !$this->fieldExists($newFieldName, $tableSet)) {

                    //getting add field sql
                    $queries[] = $this->getAddFieldSql($table, $templateField, $newFieldName, $previousField, $tableSet);

                    //getting add index sql on added field
                    $queries = array_merge($queries, (array) $this->getAddFieldIndexSql($table, $templateField, $newFieldName, $tableSet));
                }
            }
        }

        $this->executeSql($queries);
    }

    /**
     * Mark view tables as invalid.
     *
     * @param string $tableName
     *
     * @return bool
     */
    protected function validateTableName($tableName)
    {
        $result = strpos($tableName, "oxv_") !== 0;
        return $result;
    }

    /**
     * Check if language
     * id matches field language.
     *
     * @param $field
     * @param $languageId
     *
     * @return bool
     */
    protected function doesFieldLanguageMatch($field, $languageId)
    {
        $return = false;
        $tmp = explode('.', $field);
        $raw = $tmp[count($tmp)-1];
        $tmp = explode('_', $raw);
        $core = $tmp[0];
        $tmp = explode($core . '_', $raw);
        if( $languageId == $tmp[count($tmp)-1] ) {
            $return = true;
        }
        return $return;
    }
}
