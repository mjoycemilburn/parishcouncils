Detailed steps are not described here as it is assumed that the reader is vary familiar with the procedures for opening an ISP account and creating additional urls as sub-domains.

In order to install the software from this repository and create a demo website for a dummy "council_a" parish council, proceed as follows:

1. Having opened the ISP account with a master url, create an "addon url" for council_a

2. Copy the council_a and council_shared_code folders from the repository into public_html for the master url

3. Create a database called parishcouncilsdb and initialise this with the script contained in demo_database_script.sql

4. Copy the connect and disconnect.php files from the repository into the root folder for the url and initialise these with the keys for the database you have just created

5. You should now be able to able to view the demo website for council_a by running url http://council_a and you should be able to enter the maintenance system for its entries in the shared database by running url http://council_a/manager.php

6. To add a new council council_b, say, clone the council_a folder into a new council_b folder and edit the config.js within it to allocate a new council_id and council_name fields. You may also want to replace the standdard favicon,ico file with a version speecific to council_b. 

At this stage running http://council_b should produce a scratch council_b website that just displays the council name - you need to populate the shared database with slides, sections and entries for council_b. Start by adding a user_id and password for council-b to the users table in the shared parishcouncilsdb database. You should now be able to use these credentials to login to http://council_b/manager.php

It is assumed that the addition of further councils to the system will be handled in a similar ad-hoc way - the exact nature of arrangements in this area are likely to vary considerably. At present, for example, the only way in which new access rights can be added and old ones changed is by direct manipulation of the users table in the database using the phpMyAdmin package (or similar). For the present this aspect of the application is not addressed. It shouldn't be too difficult to provide more advanced facilities if this is felt worthwhile.