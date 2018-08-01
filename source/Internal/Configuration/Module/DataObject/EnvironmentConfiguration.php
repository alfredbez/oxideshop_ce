<?php
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\ModuleConfiguration\DataObject;

/**
 * @internal
 */
class EnvironmentConfiguration
{
    /**
     * @param int $shopId
     *
     * @return ShopConfiguration
     */
    public function getShopConfiguration(int $shopId): ShopConfiguration
    {
        return new ShopConfiguration();
    }

    /**
     * @param int               $shopId
     * @param ShopConfiguration $shopConfiguration
     */
    public function setShopConfiguration(int $shopId, ShopConfiguration $shopConfiguration)
    {
    }
}