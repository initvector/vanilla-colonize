<?php
namespace Initvector\Colonize\Table;

class Category extends BaseTable {

    /**
     * A reference to our object instance.
     * @var Category
     */
    protected static $instance;

    /**
     * Format of the prepared statement.
     * @var string
     */
    protected $insertStatement = "insert into :table:
        (ParentCategoryID, Depth, Name, UrlCode, Description)
        values :values:";

    /**
     * Defines the process for adding a new row.
     */
    protected function addRow() {
        $name = \Faker\Lorem::word();
        $fields = array(
            'ParentCategoryID' => -1,
            'Depth' => 1,
            'Name' => $name,
            'UrlCode' => \Faker\Internet::slug($name),
            'Description' => \Faker\Lorem::sentence(12)
        );

        $this->rowsToInsert[] = $fields;
    }

    /**
     * Run before fake content is generated.
     */
    protected function beforeGenerate() {
        parent::beforeGenerate();
        echo "\nCategories\n";
    }
}
