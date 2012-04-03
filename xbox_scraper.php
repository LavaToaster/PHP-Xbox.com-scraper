<?php
/*
 * @author       Adam LAvin
 * @copyright    2012 Adam Lavin
 * @license      Copyright (c) 2012, Adam Lavin
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met: 

1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer. 
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution. 

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * @link         http://www.lavoaster.co.uk
 */


class xbox 
{
    function __construct($email, $password)
    {
        $this->email = $email;
        $this->pass = $password;
        $this->login();
    }
    
    public function load($url, $postData='')
    {
        $useragent = "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2.10) Gecko/20100914 BRI/1 Firefox/3.6.10 ( .NET CLR 3.5.30729)";     

        $curl = curl_init();    
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HEADER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
        curl_setopt($curl, CURLOPT_POST, !empty($postData));
        if(!empty($postData)) curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);        
        curl_setopt($curl, CURLOPT_COOKIEFILE, "cookie.txt");
        curl_setopt($curl, CURLOPT_COOKIEJAR, "cookie.txt");
        $page = curl_exec ($curl);
        curl_close ($curl);

        return $page;    
    }
    
    private function login()
    {
        $url = "https://login.live.com/login.srf?wa=wsignin1.0&rpsnv=11&ct=".date("U")."&rver=6.0.5286.0&wp=MBI&wreply=https:%2F%2Flive.xbox.com:443%2Fxweb%2Flive%2Fpassport%2FsetCookies.ashx%3Frru%3Dhttp%253a%252f%252fwww.xbox.com%252fen-US%252f%253flc%253d2057&lc=1033&cb=reason%3D0%26returnUrl%3Dhttp%253a%252f%252fwww.xbox.com%252fen-US%252f%253flc%253d2057&id=66262";

        $page = $this->load($url);
        
        @preg_match("/srf_uPost='(.+?)'/",$page, $target);    
        @preg_match("/<input type=\"hidden\" name=\"PPFT\" id=\"i0327\" value=\"(.+?)\"\/>/",$page, $PPFT);
        @preg_match("/srf_sRBlob='(.+?)'/",$page, $PPSX);
        @preg_match("/<title>(.+?)<\/title>/",$page, $title); 
        
        $target = $target[1];
        $PPFT = $PPFT[1];
        $PPSX = $PPSX[1];
        $title = $title[1];
        $continue = FALSE;
        
        if($title != "Continue"){
            $postData = "login=".urlencode($this->email);
            $postData .= "&passwd=".urlencode($this->pass);
            $postData .= "&type=11";
            $postData .= "&LoginOptions=3";     
            $postData .= "&PPSX=".$PPSX;
            $postData .= "&PPFT=".$PPFT;
             
            $page = $this->load($target, $postData);
            
            $continue = TRUE;
        }
        if($title == "Continue" or $continue == TRUE){
            
            preg_match("/<form name=\"fmHF\" id=\"fmHF\" action=\"(.+?)\" method=\"post\" target=\"_self\">/",$page, $target2);    
            preg_match("/<input type=\"hidden\" name=\"NAPExp\" id=\"NAPExp\" value=\"(.+?)\">/",$page, $NAPExp);
            preg_match("/<input type=\"hidden\" name=\"NAP\" id=\"NAP\" value=\"(.+?)\">/",$page, $NAP);
            preg_match("/<input type=\"hidden\" name=\"ANON\" id=\"ANON\" value=\"(.+?)\">/",$page, $ANON);
            preg_match("/<input type=\"hidden\" name=\"ANONExp\" id=\"ANONExp\" value=\"(.+?)\">/",$page, $ANONExp);
            preg_match("/<input type=\"hidden\" name=\"t\" id=\"t\" value=\"(.+?)\">/",$page, $t);
     
            $target2 = $target2[1];
            $postData1 = "NAPExp=".$NAPExp[1];
            $postData1 .= "&NAP=".$NAP[1];
            $postData1 .= "&ANON=".$ANON[1];
            $postData1 .= "&ANONExp=".$ANONExp[1];
            $postData1 .= "&t=".$t[1];  
            
            $page = $this->load($target2, $postData1);
        }
    }
    
    public function getGames($gamerTag)
    {
        $url = "http://live.xbox.com/en-US/Activity?compareTo=".urlencode($gamerTag);
        
        $token_v = '/<input name="__RequestVerificationToken" type="hidden" value="(.+?)" \/>/';
        $gt_url = '/Game.Id ,"(.+?)"/';
        
        $get_token = $this->load($url);
        
        preg_match($token_v, $get_token, $token);
        preg_match($gt_url, $get_token, $gt_url); 
        
        $target = "http://live.xbox.com/en-GB/Activity/Summary?CompareTo=".urlencode($_GET['gamerTag']);
        $postData = "__RequestVerificationToken=".urlencode($token[1]);
        
        $page = $this->load($target, $postData);
        
        return json_decode($page, TRUE);       
    }
    
    public function getAchievements($gamerTag, $titleId)
    {
        $url = "http://live.xbox.com/en-US/Activity/Details?compareTo=".urlencode($gamerTag)."&titleId=".$titleId;
        $page = $this->load($url);
        
        $achievements = "/broker\.publish\(routes\.activity\.details\.load, (.+?)\);/";
    
        preg_match($achievements, $rc, $data);
        
        return json_decode($data[1], TRUE);
    }
}

?>
