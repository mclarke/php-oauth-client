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

You can create an client configuration object as shown below. You can fetch 
this from a configuration file in your application if desired. Below is an 
example of the generic `ClientConfig` class:

    $clientConfig = new ClientConfig(
        array(
            "authorize_endpoint" => "http://localhost/oauth/php-oauth/authorize.php",
            "client_id" => "foo",
            "client_secret" => "foobar",
            "token_endpoint" => "http://localhost/oauth/php-oauth/token.php",
        )
    );

There is also a `GoogleClientConfig` class that you can use with Google's 
`client_secrets.json` file format:
    
    // Google
    $googleClientConfig = new GoogleClientConfig(
        json_decode(file_get_contents("client_secrets.json"), true)
    );

Now you can initialize the `Api` object:

    $api = new Api("foo", $clientConfig, new SessionStorage(), new \Guzzle\Http\Client());
    
In this example we use the `SessionStorage` token storage backend. This is used 
to keep the obtained tokens in the user session. For testing purposes this is 
sufficient, for production deployments you will want to use the `PdoStorage` 
backend instead, see below.

You also need to provide an instance of Guzzle which is a HTTP client used to 
exchange authorization codes for access tokens, or use a refresh token to 
obtain a new access token.

## Requesting Tokens
In order to request tokens you can use two methods: `getAccessToken()` and 
`getAuthorizeUri()`. The first one is used to see if there is already a token 
available, the second to obtain an URL to which you have to redirect the 
browser from your application. The example below will show you how to use this.

Before you can call these methods you need to create a `Context` object to 
specify for which user you are requesting this access token and what the scope 
is you want to request at the authorization server.

    $context = new Context("john.doe@example.org", array("read"));
    
This means that you will request a token bound to `john.doe@example.org` with 
the scope `read`. The user you specify here is typically the user identifier 
you use in your application that wants to integrate with the OAuth 2.0 
protected resource. At your service the user can for example be 
`john.doe@example.org`, In order to find back the tokens on subsequent requests 
you need to bind it to the user identifier you know about this user.

Now you can see if an access token is already available:

    $accessToken = $api->getAccessToken($context);
    
This call returns `false` if no access token is available for this user and 
scope and none could be obtained through the backchannel using a refresh token. 
This means that there never was a token or it expired. The token could still be \
revoked, but we cannot see that right now, we'll figure that out later.

Assuming the `getAccessToken($context)` call returns `false`, i.e.: there was 
no token, we have to obtain authorization:

    if (false === $accessToken) {
        header("HTTP/1.1 302 Found");
        header("Location: " . $api->getAuthorizeUri($context));
        exit;
    }

This is the simplest way if your application is not using any framework. 
Usually a framework is available to do proper redirects without setting the 
HTTP headers yourself. You should use this!

After this the flow of this script ends and the user is redirected to the 
authorization server. Once there, the user accepts the client request and is 
redirected back to the redirection URL you registered at the OAuth 2.0 service 
provider. You also need to put some code at this callback location, see below.

Assuming you already had an access token, i.e.: the response from 
`getAccessToken($context)` was not `false` you can now try to get the resource. 
This example uses Guzzle as well:

    $apiUrl = 'http://www.example.org/resource';
    
    try {
        $client = new Client();
        $bearerAuth = new BearerAuth($accessToken->getAccessToken());
        $client->addSubscriber($bearerAuth);
        $response = $client->get($apiUri)->send();
        header("Content-Type: application/json");
        echo $response->getBody();
    } catch (BearerErrorResponseException $e) {
        if ("invalid_token" === $e->getBearerReason()) {
            // the token we used was invalid, possibly revoked, we throw it 
            // away
            $api->deleteAccessToken($context);
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

## Handling the Callback
The above situation assumed you already had a valid access token. If you didn't
you got redirected to the authorization server where you had to accept the 
request for access to your data. Assuming that all went well you will be 
redirected back the the redirection URI you registered at the OAuth 2.0 
service.

The of the `Callback` class is very similar to the `Api` class. We assume you
also create the `ClientConfig` object here, like in the `Api` case.

    try {
        $cb = new Callback("foo", $clientConfig, new SessionStorage(), new \Guzzle\Http\Client());
        $cb->handleCallback($_GET);

        header("HTTP/1.1 302 Found");
        header("Location: http://www.example.org/index.php");
    } catch (\Exception $e) {
        echo sprintf("ERROR: %s", $e->getMessage());
    }

This is all that is needed here. The authorization code will be extracted from
the callback URL and used to obtain an access token. The access token will be
stored in the token storage, here `SessionStorage` and the browser will be 
redirected back to the page where the `Api` calls are made. That script 
could be `index.php` and this one would than be `callback.php`. 

# Token Storage
You can store the tokens either in `SessionStorage` or `PdoStorage`. The first
one is already demonstrated above and requires no further configuration, it 
just works out of the box. 

    $tokenStorage = new SessionStorage();

The PDO backend requires you specifying the database you want to use:

    $db = new PDO("sqlite:/path/to/db/client.sqlite");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tokenStorage = new PdoStorage($db);

In both cases you can use `$tokenStorage` in the constructor where before we 
put `new SessionStorage()` there directly. See the PHP PDO documentation on how 
to specify other databases. 

Please note that if you use SQLite, please note that the *directory* you write 
the file to needs to be writable to the web server as well!