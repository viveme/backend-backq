<?php
/**
 *  The MIT License (MIT)
 *
 * Copyright (c) 2016 Sergey Shilko <contact@sshilko.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 **/

namespace BackQ\Adapter\IO;

class SocketIO extends AbstractIO
{
    private $sock = null;

    public function __construct($host, $port, $timeout)
    {
        $this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        socket_set_option($this->sock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
        socket_set_option($this->sock, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0));

        if (!socket_connect($this->sock, $host, $port)) {
            $errno = socket_last_error($this->sock);
            $errstr = socket_strerror($errno);
            throw new \Exception ("Error Connecting to server($errno): $errstr ");
        }

        socket_set_block($this->sock);
        socket_set_option($this->sock, SOL_TCP, TCP_NODELAY, 1);
    }

    public function read($n)
    {
        $res = '';
        $read = 0;

        $buf = socket_read($this->sock, $n);
        while ($read < $n && $buf !== '') {
            $read += strlen($buf);
            $res .= $buf;
            $buf = socket_read($this->sock, $n - $read);
        }

        if (strlen($res)!=$n) {
            throw new \Exception("Error reading data. Received " .
                strlen($res) . " instead of expected $n bytes");
        }

        return $res;
    }

    public function write($data)
    {
        $len = strlen($data);

        while (true) {
            $sent = socket_write($this->sock, $data, $len);
            if ($sent === false) {
                throw new \Exception ("Error sending data");
            }
            // Check if the entire message has been sented
            if ($sent < $len) {
                // If not sent the entire message.
                // Get the part of the message that has not yet been sented as message
                $data = substr($data, $sent);
                // Get the length of the not sented part
                $len -= $sent;
            } else {
                break;
            }
        }
    }

    public function close()
    {
        if (is_resource($this->sock)) {
            socket_close($this->sock);
        }
        $this->sock = null;
    }

    public function selectWrite($sec, $usec)
    {
        $read   = null;
        $write  = array($this->sock);
        $except = null;
        return socket_select($read, $write, $except, $sec, $usec);
    }

    public function selectRead($sec, $usec)
    {
        $read   = array($this->sock);
        $write  = null;
        $except = null;
        return socket_select($read, $write, $except, $sec, $usec);
    }
}