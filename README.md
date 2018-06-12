Vanilla Colonize
==

Populate a fresh install of Vanilla with fake data for users, categories, discussions and comments.

Basic Usage
--

Edit manifest.json to change the values for total users, categories, discussions and comments.  You can also specify a different manifest file with the --manifest option.

Running the following command will get the ball rolling:

`
php colonize.php --database="vanilla" --host="localhost" --user="root"
`

Once complete, visit /dba/counts on your Vanilla install and update all items listed.
