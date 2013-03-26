# Introduction
This project provides a full OAuth 2.0 "Authorization Code Grant" client as 
described in RFC 6749, section 4.1.

The client can be controlled through a simple PHP API that is used from the 
application trying to access an OAuth 2.0 protected resource server. As an 
application developer you don't need to worry about obtaining an access
token, you only need to worry about calling the client API and the REST API you 
try to access.

    +-------------+          +--------+         +---------------+
    |             |  Client  | PHP    |  OAuth  | OAuth         |
    | Application +<-------->+ OAuth  +<------->+ Authorization |
    |             |   API    | Client |         | Server        |
    +------+------+          +--------+         +---------------+
           ^
           |                                    +----------+
           |    REST API                        | OAuth    |
           +----------------------------------->+ Resource |
                                                | Server   |
                                                +----------+

The application you write needs to be registered at the OAuth client and the 
OAuth client needs to be registered at the Authorization Server.

This approach was chosen to make it as easy as possible for application 
developers to integrate with OAuth 2.0 services as they don't have to deal with 
access tokens themselves and making available a redirect URI location inside 
the application. This is similar to the approach taken by simpleSAMLphp.

# License
Licensed under the GNU Lesser General Public License as published by the Free 
Software Foundation, either version 3 of the License, or (at your option) any 
later version.

    https://www.gnu.org/licenses/lgpl.html

This roughly means that if you write some PHP application that uses this client 
you do not need to release your application under (L)GPL as well. Refer to the 
license for the exact details.

# Installation
*NOTE*: in the `chown` line you need to use your own user account name!

    $ cd /var/www/html
    $ su -c 'mkdir php-oauth-client'
    $ su -c 'chown fkooman:fkooman php-oauth-client'
    $ git clone git://github.com/fkooman/php-oauth-client.git
    $ cd php-oauth-client

To install the required dependencies run:

    $ sh docs/install_dependencies.sh

To set file permissions and setup the configuration file run:

    $ sh docs/configure.sh

To initialize the database run:

    $ php docs/initDatabase.php

# Apache Configuration
The `configure.sh` script displays an example Apache configuration file, you 
can put the contents that are displayed on your screen in 
`/etc/httpd/conf.d/php-oauth-client.conf` on Fedora/RHEL/CentOS and in 
`/etc/apache2/conf.d/php-oauth-client.conf` on Debian/Ubuntu. Don't forget to
restart Apache.

# Configuration
Registering your application is very easy. One constructs a JSON object that is 
added to the database and from that point on can be used by the applications.

Example:

    {
        "wordpress": {
            "authorize_endpoint": "https://api.surfconext.nl/v1/oauth2/authorize", 
            "client_id": "REPLACE_ME_WITH_CLIENT_ID", 
            "client_secret": "REPLACE_ME_WITH_CLIENT_SECRET", 
            "token_endpoint": "https://api.surfconext.nl/v1/oauth2/token"
        }
    }

Now you can put this in a file `wordpress.json` and register the application 
with this command:

    $ php docs/registerApplications.php wordpress.json

This will configure the application with the `app_id` `wordpress`.
The `client_id` and `client_secret` will be provided to you by the OAuth 
Authorization Server. The redirect URI you need to provide them contains the
`app_id` as well. So assuming you installed the client at 
`http://localhost/php-oauth-client` the redirect URI will be:

    http://localhost/php-oauth-client/callback.php?id=wordpress

This should be all that is needed for configuration of the OAuth client.

# Application Integration
If you want to integrate this OAuth client in your application you need to know
a few things: the `app_id` you used above for your application and the 
location of the OAuth client on your filesystem.

Below is an example of how to use the API to access an OAuth 2.0 protected 
resource server:


    <?php
    require_once "/PATH/TO/php-oauth-client/lib/_autoload.php";

    try { 
        $client = new \OAuth\Client\Api("wordpress");
        $client->setUserId("foo");
        $client->setScope("authorizations");
        $client->setReturnUri("http://localhost/demo/index.php");
        $response = $client->makeRequest("http://api.example.org/resource");
        header("Content-Type: application/json");
        echo $response->getContent();
    } catch (\OAuth\Client\ApiException $e) {
        die($e->getMessage());
    }
    ?>

The `app_id` is specified in the constructor of the class, here `wordpress`. 

The `setUserId` method is used to bind the obtained access token to a specific 
user. Usually the application you want to integrate OAuth support to will have 
some user identifier, i.e.: the user needs to login to your application first 
using user name and password or something like SAML, use that identifier here.

The `setScope` method is used to determine what scope to request at the OAuth
authorization server. The client needs to be able to request this scope, this
may be part of the client registration.

The `makeRequest` method is used to perform the actual request. It will make
sure it has an access token with the requested scope before performing this
request. There will be some redirects involved which will redirect the browser 
to the OAuth "authorize" endpoint to obtain an authorization code which then
will be exchanged for an access token.

The OAuth client will by default redirect the browser back to the location from 
which the Api was called. If you want to override the return URL your can use 
the `setReturnUri` method as well, but usually this will not be necessary.

    $client->setReturnUri("https://myapp.example.org/2012/05/11?sort=ascending");

# Logging and Debugging
The client has extensive logging functionality available. You can configure the
log level in `config/config.ini`. The log is by default written to the 
`data/logs` directory. If you want to log every possible request you can set the
following in the configuration file: `logLevel = 100`.

# Google API
In order to be able to access the Google APIs using this client, you need to
specify one extra field, `credentials_in_request_body`, and set it to `true` 
because Google [violates](https://tools.ietf.org/html/rfc6749#section-2.3.1) 
the OAuth specification by not accepting HTTP Basic authentication on the 
token endpoint.

    {
        "gdrive": {
            "authorize_endpoint": "https://accounts.google.com/o/oauth2/auth",
            "client_id": "REPLACE_ME_WITH_CLIENT_ID",
            "client_secret": "REPLACE_ME_WITH_CLIENT_SECRET",
            "credentials_in_request_body": true,
            "redirect_uri": "http://localhost/php-oauth-client/callback.php?id=gdrive",
            "token_endpoint": "https://accounts.google.com/o/oauth2/token"
        }
    }

The credentials can be obtained from Google's API console which can be found
[here](https://code.google.com/apis/console/).
