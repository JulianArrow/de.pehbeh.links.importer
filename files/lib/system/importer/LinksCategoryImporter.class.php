<?php

namespace wcf\system\importer;

use wcf\data\object\type\ObjectTypeCache;
use wcf\system\exporter\EasylinkExporter;

/**
 * @author      Julian Pfeil <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    de.pehbeh.links.importer
 * @subpackage system.importer
 *
 */
class TicketsystemCategoryImporter extends AbstractCategoryImporter
{
    /**
     * @inheritDoc
     */
    protected $objectTypeName = EasylinkExporter::LINKS_OBJECT_TYPE_CATEGORY;
    
    /**
     * Creates a new `MediaCategoryImporter` object.
     */
    public function __construct()
    {
        $this->objectTypeID = ObjectTypeCache::getInstance()->getObjectTypeByName(
            'com.woltlab.wcf.category',
            $this->objectTypeName
        )->objectTypeID;
    }
}
