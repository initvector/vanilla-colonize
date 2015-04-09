Vanilla Colonize
==
Populate a fresh install of Vanilla with fake data for users, categories, discussions and comments.
Basic Usage
--
Edit colonize.php to change the hardcoded values for total users, categories, discussions and comments.  In the near future, these will be updated to use command line options.

Running the following command will get the ball rolling:

`
php colonize.php --database="vanilla" --host="localhost" --user="root"
`

Once complete, visit /dba/counts on your Vanilla install and update all items listed.
