<?php
/*
Copyright (c) 2011 Thibault Milan

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be included
in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function getHmacsha1Signature($key, $data) {
        //convert binary hash to BASE64 string
        return base64_encode(hmacsha1($key, $data));
}

// encrypt a base string w/ HMAC-SHA1 algorithm
function hmacsha1($key,$data) {
        $blocksize=64;
        $hashfunc='sha1';
        if (strlen($key)>$blocksize) {
                $key=pack('H*', $hashfunc($key));
        }
        $key=str_pad($key,$blocksize,chr(0x00));
        $ipad=str_repeat(chr(0x36),$blocksize);
        $opad=str_repeat(chr(0x5c),$blocksize);
        $hmac = pack( 'H*',$hashfunc( ($key^$opad).pack( 'H*',$hashfunc( ($key^$ipad).$data ) ) ) );
        return $hmac;
}

function xor_these($first, $second) {
        $results=array();
        for ($i=0; $i < strlen($first); $i++)
        {
                array_push($results, $first[$i]^$second[$i]);
        }
        return implode($results);
}

function hasNoComma($str) {
        return !preg_match('/\,/', $str);
}

function lftokenCreateData($now, $duration, $args=array()) {
        //Create the right data input for Livefyre authorization
        $filtered_args = array_filter($args,'hasNoComma');
        if (count($filtered_args)==0 or count($args)>count($filtered_args)) {
                return -1;
        }

        array_unshift($filtered_args, "lftoken", $now, $duration);
        $data=implode(',',$filtered_args);
        return $data;
}

function lftokenCreateToken($data, $key) {
        //Create a signed token from data
        $clientkey = hmacsha1($key,"Client Key");
        $clientkey_sha1 = sha1($clientkey, true);
        $temp = hmacsha1($clientkey_sha1,$data);
        $sig = xor_these($temp,$clientkey);
        $base64sig = base64_encode($sig);
        return implode(",",array($data,$base64sig));
}

function lftokenValidateResponse($data, $response, $key) {
        //Validate a response from Livefyre
        $serverkey = hmacsha1(base64_decode($key),"Server Key");
        $temp = hmacsha1($serverkey,$data);
        return ($response == $temp);
}
function ssoCreateToken($userID){
	$secret = get_option_tree( 'livefyre_api_secret' );
	$args=array('auth', 'moast.fyre.co', $userID);
	$data=lftokenCreateData(gmdate('c'), 86400, $args);
	$value= lftokenCreateToken($data,base64_decode($secret));
}
?>