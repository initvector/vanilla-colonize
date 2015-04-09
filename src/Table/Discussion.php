<?php
namespace Initvector\Colonize\Table;

class Discussion extends BaseTable {

    /**
     * A reference to our object instance.
     * @var Initvector\Colonize\Table\Discussion
     */
    protected static $instance;

    /**
     * Format of the prepared statement.
     * @var string
     */
    protected $insertStatement = "insert into GDN_Discussion
        (CategoryID, InsertUserID, Name, Body, DateInserted)
        values (
            ?,?,?,?,
            (select now() - interval floor(rand() * 365) day)
        )";

    /**
     * Type mapping of placeholders in the prepared statement.
     * @var string
     */
    protected $insertPlaceholders = 'iiss';

    /**
     * Defines the process for adding a new row.
     */
    protected function addRow() {
        $name = \Faker\Lorem::word();
        $fields = array(
            'CategoryID' => Category::getInstance()->getRandomId(),
            'InsertUserID' => User::getInstance()->getRandomId(),
            'Name' => \Faker\Lorem::sentence(),
            'Body' => \Faker\Lorem::paragraph()
        );

        $this->prepareAndInsert($fields);
    }

    /**
     * Run before fake content is generated.
     */
    protected function beforeGenerate() {
        parent::beforeGenerate();
        echo "\nDiscussions\n";
    }
}
