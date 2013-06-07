# Introduction
This project provides a full OAuth 2.0 "Authorization Code Grant" client as 
described in RFC 6749, section 4.1.

The client can be controlled through a simple PHP API that is used from the 
application trying to access an OAuth 2.0 protected resource server. As an 
application developer you don't need to worry about obtaining an access
token and handling browser redirects: you only need to worry about calling 
the client API and the REST API you try to access.

![arch](https://github.com/fkooman/php-oauth-client/raw/master/docs/architecture.png)

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

    $ composer install

To set file permissions and setup the configuration file run:

    $ sh bin/configure.sh

To initialize the database run:

    $ php bin/initDatabase.php

# Apache Configuration
The `configure.sh` script displays an example Apache configuration file, you 
can put the contents that are displayed on your screen in 
`/etc/httpd/conf.d/php-oauth-client.conf` on Fedora/RHEL/CentOS and in 
`/etc/apache2/conf.d/php-oauth-client.conf` on Debian/Ubuntu. Don't forget to
restart Apache.

# Configuration
Configuring the OAuth client is very easy. One modifies the `config.yaml` file 
in the `config` directory. You can add clients here under the `registration`
section. By default some entries are available, you still need to set the
`client_id` and `client_secret` for those if you want to use them. Or you 
can add your own. The example here is SURFconext:

    SURFconext:
        authorize_endpoint : 'https://api.surfconext.nl/v1/oauth2/authorize'
        client_id          : REPLACE_ME_WITH_CLIENT_ID
        client_secret      : REPLACE_ME_WITH_CLIENT_SECRET
        token_endpoint     : 'https://api.surfconext.nl/v1/oauth2/token'
        
Here, the `callbackId`, the parameter you provide the constructor with (see 
below), is `SURFconext`. The options under this `SURFconext` section are the 
parameters for the registration.

This `callbackId` is part of the redirect URI you need to provide during 
registration. Assuming you installed the client at 
`http://localhost/php-oauth-client`, the redirect URI will be:

    http://localhost/php-oauth-client/callback.php?id=SURFconext

# Application Integration
If you want to integrate this OAuth client in your application you need to know
a few things: the `callbackId` for the client you used above for your 
application, the location of the OAuth client on your filesystem and the URL 
and scope value you need to request. Usually this information is available 
during the registration process.

Below is an example of how to use the API to access an OAuth 2.0 protected 
resource server:

    <?php
    require_once "/PATH/TO/php-oauth-client/vendor/autoload.php";

    try { 
        $client = new \fkooman\OAuth\Client\Api("SURFconext");
        $client->setUserId("foo");
        $client->setScope(array("read"));
        $client->setReturnUri("http://localhost/demo/index.php");
        $response = $client->makeRequest("http://api.example.org/resource");
        header("Content-Type: application/json");
        echo $response->getContent();
    } catch (\fkooman\OAuth\Client\ApiException $e) {
        die($e->getMessage());
    }
    ?>

The `callbackId` is specified in the constructor of the class, here 
`SURFconext`. 

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
log level in `config/config.yaml`. The log is by default written to the 
`data/logs` directory. If you want to log every possible request you can set the
following in the configuration file: `level = 100`.

# Google API
In order to be able to access the Google APIs using this client, you need to
specify two extra fields, `credentials_in_request_body`, and set it to `true` 
because Google [violates](https://tools.ietf.org/html/rfc6749#section-2.3.1) 
the OAuth specification by not accepting HTTP Basic authentication on the 
token endpoint. The other field is `redirect_uri` as it is not sufficient to
specify this during the registration process at Google, you also need to 
explicitly provide it during the authorization code request:

    drive:
        authorize_endpoint          : 'https://accounts.google.com/o/oauth2/auth'
        client_id                   : REPLACE_ME_WITH_CLIENT_ID
        client_secret               : REPLACE_ME_WITH_CLIENT_SECRET
        credentials_in_request_body : true
        redirect_uri                : 'http://localhost/php-oauth-client/callback.php?id=drive'
        token_endpoint              : 'https://accounts.google.com/o/oauth2/token'

The credentials can be obtained from Google's API console which can be found
[here](https://code.google.com/apis/console/).

The following is an example application for Google Drive to list your files:

    <html>
    <head>
    <title>Google Drive File List</title>
    </head>
    <body>
    <h1>Google Drive File List</h1>
    <p>This demonstration lists the files on your Google Drive.</p>
    <?php
    require_once '/PATH/TO/php-oauth-client/lib/_autoload.php';

    try {
        $client = new \fkooman\OAuth\Client\Api("drive");
        $client->setUserId("foo");
        $client->setScope(array("https://www.googleapis.com/auth/drive.readonly"));
        $client->setReturnUri("http://localhost/client.php");
        $response = $client->makeRequest("https://www.googleapis.com/drive/v2/files");
        $jsonData = $response->getContent();
        $data = json_decode($jsonData, TRUE);
        foreach ($data['items'] as $i) {
            echo "<ul>";
            if ("drive#file" === $i['kind']) {
                echo "<li>" . $i['title'] . "</li>";
            }
            echo "</ul>";
        }
    } catch (\fkooman\OAuth\Client\ApiException $e) {
        echo $e->getMessage();
    }
    ?>
    </body>
    </html>
