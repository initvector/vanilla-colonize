<?php
namespace Initvector\Colonize\Table;

class Comment extends BaseTable {

    /**
     * A reference to our object instance.
     * @var Comment
     */
    protected static $instance;

    /**
     * Keep track of inserted rows by ID
     */
    protected $trackRows = false;

    /**
     * Format of the prepared statement.
     * @var string
     */
    protected $insertStatement = "insert into GDN_Comment
        (DiscussionID, InsertUserID, Body, DateInserted)
        values (
            ?,?,?,
            (select now() - interval floor(rand() * 365) day)
        )";

    /**
     * Type mapping of placeholders in the prepared statement.
     * @var string
     */
    protected $insertPlaceholders = 'iis';

    /**
     * Defines the process for adding a new row.
     */
    protected function addRow() {
        $fields = array(
            'DiscussionID' => Discussion::getInstance()->getRandomId(),
            'InsertUserID' => User::getInstance()->getRandomId(),
            'Body' => \Faker\Lorem::paragraph()
        );

        $this->prepareAndInsert($fields);
    }

    /**
     * Run before fake content is generated.
     */
    protected function beforeGenerate() {
        parent::beforeGenerate();
        echo "\nComments\n";
    }
}
