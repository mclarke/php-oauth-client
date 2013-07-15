# Introduction
This project provides an OAuth 2.0 "Authorization Code Grant" client as 
described in RFC 6749, section 4.1.

The client can be controlled through a PHP API that is used from the 
application trying to access an OAuth 2.0 protected resource server. 

# License
Licensed under the GNU Lesser General Public License as published by the Free 
Software Foundation, either version 3 of the License, or (at your option) any 
later version.

    https://www.gnu.org/licenses/lgpl.html

This roughly means that if you write some PHP application that uses this client 
you do not need to release your application under (L)GPL as well. Refer to the 
license for the exact details.

# Application Integration
If you want to integrate this OAuth client in your application you need to 
answer some questions:

* Where am I going to store the access tokens?
* How do I make an endpoint URL available in my application that can be used as 
  a redirect URL for the callback

Next to this you need OAuth client credentials and API documentation
to know how to use the API. You for instance need to know the 
`authorize_endpoint`, the `token_endpoint`, the `client_id` and 
`client_secret`.

As for storing access tokens, this library includes two backends. One for 
storing the tokens in a PDO database and one for storing them in the user 
session. The first one requires some setup, the second one is very easy to 
use (no configuration) but will not allow the client to access data at the 
resource server without the session data being available. A more robust 
implementation would use the PDO backed storage. For testing purposes or very
simple setups the session implementation makes the most sense.

The example below will walk through all the steps you need in order to get the
client working.

Initializing the API is easy:

    $api = new Api();

Next you can add the client configuration to the object. You can fetch this 
from a configuration file in your application if desired.

    $clientConfig = ClientConfig::fromArray(array(
        "authorize_endpoint" => "http://localhost/oauth/php-oauth/authorize.php",
        "client_id" => "foo",
        "client_secret" => "foobar",
        "token_endpoint" => "http://localhost/oauth/php-oauth/token.php",
    ));

Add it to the API:

    $api->setClientConfig("foo", $clientConfig);

The first parameter specifies the name you want to give this client
configuration. Then you can set the token storage backend:

    $api->setStorage(new SessionStorage());

The session storage backend has no configuration and just uses the default 
PHP session. Now you can give Api a Guzzle Client instance. If you want
(extensive) logging you can prepare the Guzzle object to write logs. See the
Guzzle documentation for more information.

    $api->setHttpClient(new Client());

To specify the user to bind the tokens to you can set the user ID here, this
typically is the identifier that you use for the user in your application.

    $api->setUserId("john");

Next is the request scope for which you want to request authorization:

    $api->setScope(array("authorizations"));

After this is all setup you can see if an access token you can use is available:

    $accessToken = $api->getAccessToken();
    
This call returns `false` if no access token is available for this `user_id` 
and `scope` and none could be obtained through the backchannel using a refresh 
token. This means that there never was a token or it expired. The token could 
be revoked, but we'll find that out later when trying to use it.

Assuming the `getAccessToken()` call returns `false` we have to obtain 
authorization:

    if (false === $accessToken) {
        header("HTTP/1.1 302 Found");
        header("Location: " . $api->getAuthorizeUri());
        exit;
    }

This is the simplest way if your application is not using any framework. 
Usually a framework is available to do proper redirects without setting the
HTTP headers yourself. You should use this.

After this the flow of this script ends and the user is redirected to the
authorization server. Once there the user accepts the client request and is 
redirected back to the `redirect_uri`. You also need to put some code at this
callback location, see below.

Assuming you did have an access token, i.e.: the response from 
`getAccessToken()` was not `false` you can now try to get the resource. This 
example uses Guzzle as well:

    $apiUrl = 'http://www.example.org/resource';
    
    try {
        $client = new Client();
        $bearerAuth = new BearerAuth($accessToken);
        $client->addSubscriber($bearerAuth);
        $response = $client->get($apiUri)->send();
        header("Content-Type: application/json");
        echo $response->getBody();
    } catch (BearerErrorResponseException $e) {
        if ("invalid_token" === $e->getBearerReason()) {
            // the token we used was invalid, possibly revoked, we throw it away
            $api->deleteAccessToken();
        }
        echo sprintf('ERROR: %s (%s)', $e->getBearerReason() , $e->getMessage());
    } catch (\Guzzle\Http\Exception\BadResponseException $e) {
        // something was wrong with the request, server did not accept it, not
        // related to OAuth... deal with it appropriately
        echo sprintf('ERROR: %s', $e->getMessage());
    }
    
Pay special attention to the `BearerErrorResponseException` where a token is
deleted when it turned out not to work. On the next call of the script there
will be no access token and the user will be redirected to the authorization
server if necessary, or not if there was still a valid `refresh_token`.