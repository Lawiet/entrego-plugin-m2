<?php

namespace Klikealo\Entrego\Controller\Adminhtml\System;

use Magento\Framework\App\ResponseInterface;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportTablerates extends \Klikealo\Carrier\Controller\Adminhtml\System\Config\ExportTablerates
{
    protected $_carrierCode = 'entrego';
}
