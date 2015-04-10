<?php
namespace Initvector\Colonize\Table;

class User extends BaseTable {

    /**
     * A reference to our object instance.
     * @var User
     */
    protected static $instance;

    /**
     * Format of the prepared statement.
     * @var string
     */
    protected $insertStatement = "insert into GDN_User
        (Name, Title, Password, HashMethod, Location, About, Email) values
        (?, ?, 'vanilla', 'text', ?, ?, ?)";

    /**
     * Type mapping of placeholders in the prepared statement.
     * @var string
     */
    protected $insertPlaceholders = 'sssss';

    /**
     * Run after fake content is generated.
     */
    protected function afterGenerate() {
        parent::afterGenerate();
        $this->db->query('update GDN_User set Admin = 1 limit 1');
    }

    /**
     * Defines the process for adding a new row.
     */
    protected function addRow() {
        $title = \Faker\Name::name();
        $fields = array(
            'Name' =>\Faker\Internet::userName($title),
            'Title' => $title,
            'Location' => \Faker\Address::city(),
            'About' => \Faker\Lorem::sentence(10),
            'Email' => \Faker\Internet::email($title)
        );

        $this->prepareAndInsert($fields);
    }

    /**
     * Run before fake content is generated.
     */
    protected function beforeGenerate() {
        parent::beforeGenerate();
        echo "\nUsers\n";
    }
}
