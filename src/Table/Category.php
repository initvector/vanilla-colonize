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
    protected $insertStatement = "insert into GDN_Category
        (ParentCategoryID, Depth, Name, UrlCode, Description)
        values (-1, 1, ?, ?, ?)";

    /**
     * Type mapping of placeholders in the prepared statement.
     * @var string
     */
    protected $insertPlaceholders = 'sss';

    /**
     * Defines the process for adding a new row.
     */
    protected function addRow() {
        $name = \Faker\Lorem::word();
        $fields = array(
            'Name' => $name,
            'UrlCode' => \Faker\Internet::slug($name),
            'Description' => \Faker\Lorem::sentence(12)
            );

        $this->prepareAndInsert($fields);
    }

    /**
     * Run before fake content is generated.
     */
    protected function beforeGenerate() {
        parent::beforeGenerate();
        echo "\nCategories\n";
    }
}
