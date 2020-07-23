<?php
/**
 * Deployment configuration
 *
 * It's preferable to configure the script using this file instead of directly.
 *
 * Rename this file to `deploy-config.php` and edit the
 * configuration options here instead of directly in `deploy.php`.
 * That way, you won't have to edit the configuration again if you download the
 * new version of `deploy.php`.
 *
 * @version 1.3.1
 */

/**
 * Protect the script from unauthorized access by using a secret access token.
 * If it's not present in the access URL as a GET variable named `sat`
 * e.g. deploy.php?sat=Bett...s the script is not going to deploy.
 *
 * @var string
 */
define('SECRET_ACCESS_TOKEN', substr(file_get_contents("/home/ubuntu/website_config/deploy_token.txt", true), 0, -1));
// in this case the token is read from a file that is inaccessible from the internet (because the code is open source :) ).

/**
 * The address of the remote Git repository that contains the code that's being
 * deployed.
 * If the repository is private, you'll need to use the SSH address.
 *
 * @var string
 */
define('REMOTE_REPOSITORY', 'https://github.com/carlobrok/website.git');

/**
 * The branch that's being deployed.
 * Must be present in the remote repository.
 *
 * @var string
 */
define('BRANCH', 'master');

/**
 * The location that the code is going to be deployed to.
 * Don't forget the trailing slash!
 *
 * @var string Full path including the trailing slash
 */
define('TARGET_DIR', '/home/ubuntu/website/');

/**
 * Time limit for each command.
 *
 * @var int Time in seconds
 */
define('TIME_LIMIT', 30);

/**
 * OPTIONAL
 * Email address to be notified on deployment failure.
 *
 * @var string A single email address, or comma separated list of email addresses
 *      e.g. 'someone@example.com' or 'someone@example.com, someone-else@example.com, ...'
 */
define('EMAIL_ON_ERROR', false);
