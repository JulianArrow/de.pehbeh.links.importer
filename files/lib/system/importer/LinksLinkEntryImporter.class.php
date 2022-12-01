<?php

namespace wcf\system\importer;

use wcf\data\links\LinkEntry;
use wcf\data\links\LinkEntryEditor;
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
class LinksLinkEntryImporter extends AbstractImporter
{
    /**
     * @inheritDoc
     */
    protected $className = LinkEntry::class;
    
    /**
     * @inheritDoc
     */
    public function import($oldID, array $data, array $additionalData = [])
    {
        /**
         * @var $linkEntry LinkEntry
         */
        $linkEntry = LinkEntryEditor::create($data);
        ImportHandler::getInstance()->saveNewID(EasylinkExporter::LINKS_OBJECT_TYPE_LINK_ENTRY, $oldID, $linkEntry->linkEntryID);
        
        return $linkEntry->linkID;
    }
}
