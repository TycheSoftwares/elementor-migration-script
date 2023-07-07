# Elementor Migration Script

This repo contains the code used in migrating Elementor-related data in the DB from our staging site to the live site.

You can find more information from this post: [Enqueue the tyche constructor](https://github.com/TycheSoftwares/tyche-js-constructor)

## Steps
There would be two databases. The DB for the staging site and that of the live website.

Take a note of the last ID in the wp_posts table on the live website. It's important and we'll be making use of it in one of the scripts.

What we would do is to copy/import the following database tables from the staging site to the live website. For each of the database tables, we would append the number '2' to enable them not overwrite the already exisiting tables.
- wp_posts => would be imported as wp_posts2
- wp_postmeta => would be imported as wp_postmeta2
- wp_options => would be imported as wp_options2
- wp_terms => would be imported as wp_terms2
- wp_term_relationships => would be imported as wp_term_relationships2

## Run the scripts
It's best to run the script on the SSH terminal as you may encounter timeout erros if you run them on the browser.

You would run the scripts in this order:
1. wp_posts.php
2. wp_options.php
3. wp_post_meta.php
4. wp_terms.php
5. wp_term_relationship.php
6. wp_post_parent.php

Inspect the log files to be sure that no error messages were shown and that things have run smoothly.


