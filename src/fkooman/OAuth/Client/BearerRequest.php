<?php

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
 
namespace fkooman\OAuth\Client;

class BearerRequest
{
    private $_guzzle;
    private $_bearerToken;

    public function __construct(\Guzzle\Http\Client $guzzle, $bearerToken)
    {
        $this->_guzzle = $guzzle;
        $this->_bearerToken = $bearerToken;
    }

    public function makeRequest($requestUri, $requestMethod = "GET", $requestHeaders = array(), $postParameters = array())
    {
        $request = $this->_guzzle->createRequest($requestMethod, $requestUri);
        foreach ($requestHeaders as $k => $v) {
            $request->setHeader($k, $v);
        }

        $request->setHeader("Authorization", "Bearer " . $this->_bearerToken);

        if ("POST" === $requestMethod) {
            $request->addPostFields($postParameters);
        }

        try {
            $response = $request->send();

            return $response;
        } catch (ClientErrorResponseException $e) {
            if (401 === $e->getResponse()->getStatusCode()) {
                // FIXME: check whether error WWW-Authenticate type is "invalid_token",
                // only then it makes sense to try again...
                throw new BearerRequestException("invalid bearer token");
            }
            throw $e;
        }
    }
}
