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

class State extends Token
{
    const RANDOM_LENGTH = 8;

    /** state VARCHAR(255) NOT NULL */
    protected $state;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $state = array_key_exists('state', $data) ? $data['state'] : null;
        $this->setState($state);
    }

    public function setState($state)
    {
        if (null === $state) {
            $this->state = bin2hex(openssl_random_pseudo_bytes(RANDOM_LENGTH));
        } else {
            $this->state = $state;
        }
    }

    public function getState()
    {
        return $this->state;
    }
}
