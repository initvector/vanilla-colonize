<?php
namespace Initvector\Colonize\Table;

class Discussion extends BaseTable {

    /**
     * A reference to our object instance.
     * @var Discussion
     */
    protected static $instance;

    /**
     * Format of the prepared statement.
     * @var string
     */
    protected $insertStatement = "insert into :table:
        (CategoryID, InsertUserID, Name, Body, DateInserted)
        value :values:";

    /**
     * Defines the process for adding a new row.
     */
    protected function addRow() {
        $name = \Faker\Lorem::word();
        $fields = array(
            'CategoryID' => Category::getInstance()->getRandomId(),
            'InsertUserID' => User::getInstance()->getRandomId(),
            'Name' => \Faker\Lorem::sentence(),
            'Body' => \Faker\Lorem::paragraph(),
            'DateInserted' => ':randomPastDate:'
        );

        $this->rowsToInsert[] = $fields;
    }

    /**
     * Run before fake content is generated.
     */
    protected function beforeGenerate() {
        parent::beforeGenerate();
        echo "\nDiscussions\n";
    }
}
