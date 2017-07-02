# ZNonce

* [Introduction](#introduction)
* [Installation](#installation)
* [Hos to use it](#how-to-use-it)
	* [Creating a nonce](#creating-a-nonce)
		* [Adding a nonce to a URL](#adding-a-nonce-to-a-url)
		* [Adding a nonce to a form](#adding-a-nonce-to-a-form)
		* [Creating a nonce for use in some other way](#creating-a-nonce-for-use-in-some-other-way)
	* [Verifying a nonce](#verifying-a-nonce)
		* [Verifying a nonce passed from an admin screen](#verifying-a-nonce-passed-from-an-admin-screen)
		* [Verifying a nonce passed in an AJAX request](#verifying-a-nonce-passed-in-an-ajax-request)
		* [Verifying a nonce passed in some other context](#verifying-a-nonce-passed-in-some-other-context)
* [Testing](#testing)
* [License](#license)
	


# Introduction
 
ZNonce library provides WordPress nonce functionality in more object-oriented way. 
Nonces are used to help protect URLs and forms from certain types of misuse, malicious or otherwise. 
Please see https://codex.wordpress.org/WordPress_Nonces for details.


----------


# Installation

The recomended way to install ZNonce is using composer:

    composer require zahardoc/z-nonce

----------------

# How to use it

After installation ensure you required composer autoload.php file anywhere in your code before using ZNonce:

    require_once 'path/to/folder/vendor/autoload.php';

Then you could get ZNonce everywhere in your code using ZNonce::init() function:

    $znonce = Zahardoc\ZNonce\ZNonce::init();

Then you can create or verify a nonce.

Note that nonces has lifetime after which they expire. By default it is 24 hours, but you can modify it by set_nonce_life() method. Just call it with number of seconds you want nonce to live.
Example: set nonce time to 1 minute:

    $znonce->set_nonce_life(60);

Also, you can always check nonce life time by get_nonce_life():

    $life = $znonce->get_nonce_life();



## Creating a nonce
When you creating a nonce try to use as more specific action as you can. For example, if you are dealing with post, add post id to your action. There are 3 ways to use creating a nonce functionality:



### Adding a nonce to a URL 
To add a nonce to a URL, call nonce_url() method specifying the bare URL and a string representing the action:

    $action_url = $znonce->nonce_url( $bare_url, 'your_action_'.$post_id );


### Adding a nonce to a form
To add a nonce to a form, use nonce_field():

    $znonce->nonce_field( 'your_action_'.$post_id ); 

It will create 2 hidden fields with nonce and referer values, which you could verify later.


### Creating a nonce for use in some other way
If you need nonce itself to use it in some other way, call create_nonce() method:

    $nonce = $znonce->create_nonce( 'your-action_'.$post_id );




## Verifying a nonce

If nonce is valid, all verify methods return 1 or 2 depending on how much time ago it has been created. If it is less then half of expire time - method returns 1, otherwise - 2.

### Verifying a nonce passed from an admin screen

To verify a nonce passed from an admin screen, call check_admin_referer() specifying the string representing the action.

    $znonce->check_admin_referer( 'your_action' );

This call checks the nonce and the referrer, and if the check fails it terminates script execution with a "403 Forbidden" response and an error message. 


### Verifying a nonce passed in an AJAX request
To verify a nonce passed from an AJAX request, call check_ajax_referer() method specifying the string representing the action.

    $znonce->check_ajax_referer( 'your_action' );

This call checks the nonce (but not the referrer), and if the check fails then it terminates script execution.


### Verifying a nonce passed in some other context
If you want just verify a nonce and then do some of your custom actions, use verify_nonce() method:

    $result = $znonce->verify_nonce( $_REQUEST['your_nonce'], 'your-action_'.$post_id );




----------
# Testing
ZNonce is provided with phpunit tests. To run them, please follow these steps:

1. Go to the library root directory: `cd /your/vendor/path/zahardoc/z-nonce`
2. Install environment `tests/install.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]`
Note: use database user with privileges to create new database. Don't specify existing database here.
3. Install library dependencies: `composer update`
4. Run tests: `vendor/bin/phpunit` 

----------


# License

This library is released under the [MIT](https://github.com/zahardoc/z-nonce/blob/master/LICENSE) license, you can use it free of charge on your personal or commercial sites.
