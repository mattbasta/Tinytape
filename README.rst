==========
 Tinytape 
==========
------
 v4.0 
------

To install Tinytape to a server, it must already be running a copy of
Interchange that's properly configured. This repository should then be overlaid
up on the Interchange root (the directory specified by `IXG_PATH_PREFIX`).

Setting up the DBs
==================

This is some good times.

#. Open `libraries/prefixes.php`
#. Modify the Cloud database instantiation to match your MySQL information
#. Open `libraries/redis.php`
#. Modify the PHPRedis client initialization to match your Redis configuration
#. Open `libraries/sphinx.php`
#. Modify the Sphinx client initialization to match your Sphinx configuration

MySQL
-----

To set up your MySQL instance, simply set up a new database (call it `tinytape`
for the sake of simplicity). Then, run `setup/tinytape.sql` to create the
tables.

Note that in order to get to the admin stuff, you'll need a user with admin
priviliges, so sign up for an account and then manually promote the user to
admin status by changing the `admin` column for their record to `1`. Log out
and log back in for the changes to take effect.

Redis
-----

So if you've ever used Redis before, you know that it can be pretty finnicky
when it comes to not faceplanting. Memory is a huge issue: too little memory or
disk space, especially in non-AOF configurations, will result in a complete
loss of all data up until the last time Redis was able to build your dump.

The best configuration for Tinytape is append-only. Create a CRON job to run
every night (or every few hours if you're paranoid) to rewrite the AOF and
push it to S3 for backup.

Otherwise, any old Redis installation will do. Tinytape expects a password (the
`auth` command), and the password should be stored at `/var/auth/redis`.

Sphinx
------

Set up Sphinx to index MySQL. Beyond loading in a config file, there's really
no other configuration necessary.

# TODO : Post a config file

