<?php

/**
 * IBlock tools service
 */
class CIBlockTools
{
    private static $tools = null;

    /**
     * Init service
     * @return CIBlockTools
     */
    public static function Init(){
        if(!self::$tools) self::$tools = new CIBlockTools();
        return self::$tools;
    }

    private $arBlocks;
    private $arEnums;
    private $arConsts;

    private function __construct() {

        $cache = new CPHPCache();
        $cache_time = 86400; // сутки
        $cache_id = 'Settings';
        $cache_path = '/' . SITE_ID . '/Settings/';

        if($cache->InitCache($cache_time, $cache_id, $cache_path))
        {
            $vars = $cache->GetVars();
            $this->arBlocks = $vars['arBlocks'];
            $this->arEnums = $vars['arEnums'];
            $this->arConsts = $vars['arConsts'];
        }
        else
        {
            $cache->StartDataCache($cache_time, $cache_id, $cache_path);

            if(CModule::IncludeModule("iblock")){
                $this->SetBlocks();
                $this->SetEnums();
                $this->SetConsts();
            }

            $cache->EndDataCache(array(
                'arBlocks' => $this->arBlocks,
                'arEnums'  => $this->arEnums,
                'arConsts' => $this->arConsts,
            ));
        }
    }

    private function SetBlocks(){
        $this->arBlocks = array();

        $db = CIBlock::GetList(
            array('ID' => 'ASC'),
            array(
                'ACTIVE' => 'Y',
            )
        );
        while($arr = $db->Fetch()){
            $this->arBlocks[$arr['CODE']] = intval($arr['ID']);
        }
    }

    private function SetEnums(){
        $this->arEnums = array();

        $dbProp = CIBlockProperty::GetList(
            array('ID' => 'ASC'),
            array(
                'PROPERTY_TYPE' => 'L',
                'ACTIVE' => 'Y'
            )
        );
        while($arrProp = $dbProp->Fetch()){
            $iblockId = intval($arrProp['IBLOCK_ID']);
            $code = $arrProp['CODE'];

            $this->arEnums[$iblockId][$code] = array();

            $dbVal = CIBlockPropertyEnum::GetList(
                array('ID' => 'ASC'),
                array(
                    'PROPERTY_ID' => $arrProp['ID']
                )
            );
            while($arVal = $dbVal->Fetch()){
                $this->arEnums[$iblockId][$code][$arVal['XML_ID']] = intval($arVal['ID']);
            }
        }
    }


    public function GetIblockId($iblockCode){
        return $this->arBlocks[$iblockCode];
    }

    public function GetEnumId($iblockCode, $propCode, $valCode){
        $iblockId = $this->GetIblockId($iblockCode);
        return $this->arEnums[$iblockId][$propCode][$valCode];
    }

    public function __get($name) {
        $name = strtolower($name);
        $iblockId = $this->GetIblockId($name);

        if($iblockId){
            return $iblockId;
        }

        return null;
    }
}
