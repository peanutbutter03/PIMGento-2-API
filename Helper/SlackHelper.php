<?php

namespace Pimgento\Api\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class SlackHelper extends AbstractHelper
{
    const XML_PATH = 'module-pimgento-2-api/';

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH . 'general/' . $code, $storeId);
    }

}
