<?php

namespace wcf\system\exporter;

use wcf\system\database\exception\DatabaseQueryException;
use wcf\system\database\exception\DatabaseQueryExecutionException;
use wcf\system\exception\SystemException;
use wcf\system\importer\ImportHandler;

/**
 * @author      Julian Pfeil <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    de.pehbeh.links.importer
 * @subpackage system.exporter
 *
 */
class EasylinkExporter extends AbstractExporter
{
    public $validationTable = 'item';

    public $linkEntrySourceTable = 'item';

    public $linkEntryCategorySourceTable = 'category';

    public const LINKS_OBJECT_TYPE_CATEGORY = 'de.pehbeh.links.category';

    public const LINKS_OBJECT_TYPE_LINK_ENTRY = 'de.pehbeh.links.linkEntry';

    /**
     * @inheritDoc
     */
    protected $methods = [
        self::LINKS_OBJECT_TYPE_CATEGORY => 'LinkEntryCategories',
        self::LINKS_OBJECT_TYPE_LINK_ENTRY => 'LinkEntries',
    ];

    /**
     * @inheritDoc
     */
    protected $limits = [
        self::LINKS_OBJECT_TYPE_CATEGORY => 200,
        self::LINKS_OBJECT_TYPE_LINK_ENTRY => 200,
    ];

    /**
     * @inheritDoc
     */
    public function getSupportedData()
    {
        return [
            self::LINKS_OBJECT_TYPE_CATEGORY => [],
            self::LINKS_OBJECT_TYPE_LINK_ENTRY => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function validateDatabaseAccess()
    {
        parent::validateDatabaseAccess();

        $this->countDataFromTable($this->databasePrefix . $this->validationTable);
    }

    /**
     * @inheritDoc
     */
    public function validateFileAccess()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getQueue()
    {
        $queue = [];

        // category
        if (\in_array(self::LINKS_OBJECT_TYPE_CATEGORY, $this->selectedData)) {
            $queue[] = self::LINKS_OBJECT_TYPE_CATEGORY;
        }

        // link-entry
        if (\in_array(self::LINKS_OBJECT_TYPE_LINK_ENTRY, $this->selectedData)) {
            $queue[] = self::LINKS_OBJECT_TYPE_LINK_ENTRY;
        }

        return $queue;
    }

    /**
     * Counts easy-link categories.
     *
     * @return int
     * @throws SystemException
     */
    public function countLinkEntryCategories()
    {
        return $this->countDataFromTable($this->databasePrefix . $this->linkEntryCategorySourceTable);
    }

    /**
     * Exports easy-link categories.
     *
     * @param int $offset
     * @param int $limit
     *
     * @throws DatabaseQueryException
     * @throws DatabaseQueryExecutionException
     * @throws SystemException
     */
    public function exportLinkEntryCategories($offset, $limit)
    {
        $sql = 'SELECT *
                FROM   ' . $this->databasePrefix . $this->linkEntryCategorySourceTable;
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();

        while ($row = $statement->fetchArray()) {
            // import category
            ImportHandler::getInstance()->getImporter(self::LINKS_OBJECT_TYPE_CATEGORY)->import(
                $row['categoryID'],
                [
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'showOrder' => $row['position'],
                    'time' => $row['time'],
                ]
            );
        }
    }

    /**
     * Counts tickets.
     *
     * @return mixed
     * @throws DatabaseQueryException
     * @throws DatabaseQueryExecutionException
     */
    public function countTickets()
    {
        return $this->countDataFromTable($this->databasePrefix . $this->linkEntrySourceTable);
    }

    /**
     * Exports tickets.
     *
     * @param integer $offset
     * @param integer $limit
     *
     * @throws DatabaseQueryException
     * @throws DatabaseQueryExecutionException
     * @throws SystemException
     */
    public function exportLinkEntries($offset, $limit)
    {
        // get tickets
        $sql = "SELECT	* from " . $this->databasePrefix . $this->linkEntrySourceTable;
        $statement = $this->database->prepare($sql, $limit, $offset);
        $statement->execute();

        while ($row = $statement->fetchArray()) {
            $categoryID = ImportHandler::getInstance()->getNewID(
                self::LINKS_OBJECT_TYPE_CATEGORY,
                $row['categoryID']
            );
            if (empty($categoryID)) {
                $categoryID = 0;
            }

            $data = [
                'subject' => $row['title'],
                'text' => $row['message'],
                'time' => $row['time'],
                'categoryID' => $categoryID,
                'userID' => $row['userID'],
                'username' => $row['username'],
                'url' => $row['link'],
                'views' => $row['views'],
                'hits' => $row['visits'],
                'hasEmbeddedObjects' => $row['hasEmbeddedObjects'],
                'enableComments' => $row['enableComments'],
                'isDisabled' => $row['isDisabled'],
                'lastEditTime' => $row['lastEditTime'],
            ];

            // import easy-link
            if ($newLinkID = ImportHandler::getInstance()->getImporter(self::TICKETSYSTEM_OBJECT_TYPE_TICKET)->import(
                $row['itemID'],
                $data
            )) {
                if ($data['attachments'] > 0) {
                    $this->transferAttachments($row['itemID'], $newLinkID);
                }

                if ($data['screenshot'] != '') {
                    $screenshotPath = EASYLINK_DIR . '/screenshots/';
                    $screenshotFileName = $data['screenshot'];
                    $screenshotFile = $screenshotPath . $screenshotFileName;

                    $imageFilePath = WCF_DIR . '/images/links/';
                    $imageFileName = $newLinkID . '-' . $screenshotFileName;
                    $imageFile = $imageFilePath . $imageFileName;
                    
                    // copy screenshots to link images and resize
                    $adapter = ImageHandler::getInstance()->getAdapter();
                    $adapter->loadFile($screenshotFile);

                    $originalWidth = $adapter->getWidth();
                    $originalHeight = $adapter->getHeight();
                    
                    if ($originalWidth / LINKS_ENTRY_THUMBNAIL_WIDTH > $originalHeight / LINKS_ENTRY_THUMBNAIL_HEIGHT) {
                        $newWidth = $originalWidth / ($originalHeight / LINKS_ENTRY_THUMBNAIL_HEIGHT);
                        
                        $adapter->resize(0, 0, $originalWidth, $originalHeight, $newWidth, LINKS_ENTRY_THUMBNAIL_HEIGHT);
                    } else {
                        $newHeight = $originalHeight / ($originalWidth / LINKS_ENTRY_THUMBNAIL_WIDTH);
                        
                        $adapter->resize(0, 0, $originalWidth, $originalHeight, LINKS_ENTRY_THUMBNAIL_WIDTH, $newHeight);
                    }
                    
                    $adapter->writeImage($adapter->getImage(), $imageFile);
                }
            }
        }
    }

    /**
     * Transfer all easy-link attachments to the link-entry
     */
    public function transferAttachments($oldItemID, $newLinkID)
    {
        try {
            $action = new AttachmentAction([], 'copy', [
                'sourceObjectType' => 'com.cls.easylink.item',
                'targetObjectType' => 'de.pehbeh.links.linkEntry',
                'sourceObjectID' => $oldItemID,
                'targetObjectID' => $newLinkID,
            ]);
            $action->executeAction();
        } catch (SystemException $e) {
            // skip attachment
        }
    }

    /**
     * Returns the table with prefix
     *
     * @param $table
     *
     * @return string
     */
    private function getTableWithPrefix($table)
    {
        return $this->databasePrefix . $table;
    }

    /**
     * Returns the total count of all data from given table
     *
     * @param $table
     *
     * @return mixed
     * @throws DatabaseQueryException
     * @throws DatabaseQueryExecutionException
     */
    private function countDataFromTable($table)
    {
        $sql = "SELECT	COUNT(*) AS count
			FROM	" . $table;
        $statement = $this->database->prepare($sql);
        $statement->execute();
        $row = $statement->fetchArray();

        return $row['count'];
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDatabasePrefix()
    {
        return "easylink" . WCF_N . "_";
    }
}
