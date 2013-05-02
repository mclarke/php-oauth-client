<?php

require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "_autoload.php";

use \OAuth\Client\Client as Client;
use \OAuth\Client\ClientException as ClientException;

class ClientTest extends PHPUnit_Framework_TestCase
{

    public function testSimple()
    {
        $data = array("client_id" => "foo", "client_secret" => "bar", "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => "http://www.example.org/token", "credentials_in_request_body" => TRUE);
        $c = Client::fromArray($data);
        $this->assertEquals("foo", $c->getClientId());
        $this->assertEquals("bar", $c->getClientSecret());
        $this->assertEquals("http://www.example.org/authorize", $c->getAuthorizeEndpoint());
        $this->assertEquals("http://www.example.org/token", $c->getTokenEndpoint());
        $this->assertTrue($c->getCredentialsInRequestBody());
        $this->assertEquals($data, $c->toArray());
    }

    /**
     * @dataProvider validClients
     */
    public function testValidClients(array $data)
    {
        Client::fromArray($data);
        Client::fromJson(json_encode($data));
    }

    /**
     * @dataProvider invalidClients
     */
    public function testInvalidClients(array $data, $message)
    {
        try {
            Client::fromArray($data);
            $this->assertTrue(FALSE);
        } catch (ClientException $e) {
            $this->assertEquals($message, $e->getMessage());
        }
    }

    public function validClients()
    {
        return array (
            array(
              array ("client_id" => "foo", "client_secret" => "bar", "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => "http://www.example.org/token"),
            ),
            array(
              array ("client_id" => "foo", "client_secret" => "bar", "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => "http://www.example.org/token", "credentials_in_request_body" => TRUE),
            ),
            array(
              array ("foo" => "bar", "xyz" => "abc", "client_id" => "foo", "client_secret" => "bar", "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => "http://www.example.org/token"),
            ),
            // empty client_secret is allowed
            array(
              array ("client_id" => "foo", "client_secret" => "", "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => "http://www.example.org/token"),
            ),
        );
    }

    public function invalidClients()
    {
        return array(
            array(
                array(),
                "client_id must be set"
            ),
            array(
                array("client_id" => "", "client_secret" => "", "authorize_endpoint" => "", "token_endpoint" => ""),
                "client_id must be non empty string"
            ),
            array(
                array("client_id" => "foo"),
                "client_secret must be set"
            ),
            array(
                array("client_id" => "foo", "client_secret" => "bar"),
                "authorize_endpoint must be set"
            ),
            array(
                array("client_id" => "foo", "client_secret" => "bar", "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => 5),
                "endpoint must be non empty string"
            ),
            array(
                array("client_id" => "foo", "client_secret" => "bar", "authorize_endpoint" => "http://www.example.org/authorize"),
                "token_endpoint must be set"
            ),
            array(
                array("client_id" => "foo", "client_secret" => "bar", "authorize_endpoint" => "not_a_url", "token_endpoint" => "http://www.example.org/token#foo"),
                "endpoint must be valid URL"
            ),
            array(
                array("client_id" => "foo", "client_secret" => "bar", "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => "http://www.example.org/token#foo"),
                "endpoint must not contain a fragment"
            ),
            array(
              array ("client_id" => "foo:abc", "client_secret" => "bar", "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => "http://www.example.org/token"),
                "client_id and/or client_secret cannot contain colon ':'"
            ),
            array(
              array ("client_id" => "foo", "client_secret" => "∑", "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => "http://www.example.org/token"),
                "invalid character(s) in client_id or client_secret"
            ),
            array(
              array ("client_id" => "foo", "client_secret" => 5, "authorize_endpoint" => "http://www.example.org/authorize", "token_endpoint" => "http://www.example.org/token"),
                "client_secret must be string"
            ),

        );
    }
}
