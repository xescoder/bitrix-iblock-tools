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

    private $arIBlockIds;
    private $arPropertyIds;
    private $arPropertyValueIds;

    private function __construct() {

        $cache = new CPHPCache();
        $cache_time = 2592000; // month
        $cache_id = 'CIBlockTools';
        $cache_path = '/CIBlockTools/';

        if($cache->InitCache($cache_time, $cache_id, $cache_path))
        {
            $vars = $cache->GetVars();
            $this->arIBlockIds = $vars['arIBlockIds'];
            $this->arPropertyIds = $vars['arPropertyIds'];
            $this->arPropertyValueIds = $vars['arPropertyValueIds'];
        }
        else
        {
            $cache->StartDataCache($cache_time, $cache_id, $cache_path);

            if(CModule::IncludeModule("iblock")){
                $this->SetIBlocks();
                $this->SetProperties();
            }

            $cache->EndDataCache(array(
                'arIBlockIds' => $this->arIBlockIds,
                'arPropertyIds'  => $this->arPropertyIds,
                'arPropertyValueIds' => $this->arPropertyValueIds,
            ));
        }
    }

    private function SetIBlocks(){
        $this->arIBlockIds = array();

        $db = CIBlock::GetList(
            array('ID' => 'ASC'),
            array(
                'ACTIVE' => 'Y',
            )
        );
        while($arr = $db->Fetch()){
            if($arr['CODE']){
                $this->arIBlockIds[$arr['CODE']] = intval($arr['ID']);
            }
        }
    }

    private function SetProperties(){
        $this->arPropertyIds = array();
        $this->arPropertyValueIds = array();

        $db = CIBlockProperty::GetList(
            false,
            array('ACTIVE' => 'Y')
        );
        while($arr = $db->Fetch()){
            if(!$this->arPropertyIds[$arr['IBLOCK_ID']])
                $this->arPropertyIds[$arr['IBLOCK_ID']] = array();

            if($arr['CODE']){
                $this->arPropertyIds[$arr['IBLOCK_ID']][$arr['CODE']] = intval($arr['ID']);

                if($arr['PROPERTY_TYPE'] == 'L'){
                    if(!$this->arPropertyValueIds[$arr['ID']])
                        $this->arPropertyValueIds[$arr['ID']] = array();

                    $resProp = CIBlockPropertyEnum::GetList(
                        false,
                        array('PROPERTY_ID' => $arr['ID'])
                    );
                    while($arrProp=$resProp->Fetch()){
                        if($arrProp['XML_ID']){
                            $this->arPropertyValueIds[$arr['ID']][$arrProp['XML_ID']] = intval($arrProp['ID']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get IBlock ID
     * @param string $iblockCode IBlock CODE
     * @return integer|null
     */
    public function GetIBlockId($iblockCode){
        if(isset($this->arIBlockIds[$iblockCode]))
            return $this->arIBlockIds[$iblockCode];

        return null;
    }

    /**
     * Get IBlock property ID
     * @param string $iblockCode IBlock CODE
     * @param string $propCode Property CODE
     * @return integer|null
     */
    public function GetPropertyId($iblockCode, $propCode){
        $iblockId = $this->GetIBlockId($iblockCode);
        if(!$iblockId) return null;

        if(isset($this->arPropertyIds[$iblockId]) && isset($this->arPropertyIds[$iblockId][$propCode]))
            return $this->arPropertyIds[$iblockId][$propCode];

        return null;
    }

    /**
     * Get IBlock property enum value ID
     * @param string $iblockCode IBlock CODE
     * @param string $propCode Property CODE
     * @param string $xmlId Property value XML_ID
     * @return integer|null
     */
    public function GetPropertyEnumValueId($iblockCode, $propCode, $xmlId){
        $propId = $this->GetPropertyId($iblockCode, $propCode);
        if(!$propId) return null;

        if(isset($this->arPropertyValueIds[$propId]) && isset($this->arPropertyValueIds[$propId][$xmlId]))
            return $this->arPropertyValueIds[$propId][$xmlId];

        return null;
    }

    public function __get($name){
        $name = strtolower($name);
        return $this->GetIBlockId($name);
    }
}