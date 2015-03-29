<?php

$result =new parse($_GET["url"]);

echo($result->getJson());   

class parse{
    public $url , $mime , $domain, $type;
    
    public function __construct($url){
        $this->url=$url;
        $this->parseUrl($url);
    }
    
    public function getJson(){
       return json_encode($this);
    }
    
    private function parseUrl($url) { 
         $ch= self::getSslPage($url);

         $this->mime=$ch->mime;
         $this->url=$ch->url;
         $this->domain=self::getDomaine($this->url);
         $this->type=$this->mime;

          if($this->mime=="text/html;charset=utf-8" || $this->mime=="text/html"){  ///SUB STRING DEBUT
                     $this->type="html";
                     
                     $dom =self::getDom($ch->html);
                     $this->getMeta($dom);
                     
                     switch($this->domain){
                            case"youtube.com":
                                    $param= self::getParam($this->url);
                                    if($param["v"]){
                                            $vkey=$param["v"];
                                            $this->type="video";
                                            $this->image=array ("http://i1.ytimg.com/vi/$vkey/hqdefault.jpg");    
                                            $this->media_id=$vkey;
                                    }

                                break;
                            case "dailymotion.com" :
                                  $tokens = explode('/', $this->url);
                                  $url= $tokens[sizeof($tokens)-1];
                                    $ch = getSslPage("https://api.dailymotion.com/video/$url");
                                    $hash=json_decode ($ch->html);
                                    if(!isset($hash->error)){
                                            $this->type="video";
                                            $this->media_id=$hash->id;
                                            $ch = self::getSslPage("http://www.dailymotion.com/thumbnail/160x120/video/$this->video_code");
                                            $this->image=array ($ch->url);   
                                    }
                                break;
                            case "vimeo.com" :
                                  $tokens = explode('/', $this->url);
                                   $vkey= $tokens[sizeof($tokens)-1];
                                   if(( $ch=@file_get_contents("http://vimeo.com/api/v2/video/$vkey.php") )!== FALSE){
                                       $this->type="video";
                                       $hash = unserialize($ch);

                                       $image=$hash[0]["thumbnail_large"];
                                        $this->media_id=$vkey;
                                        $this->image= array($image  );   
                                        $this->url=$hash[0]["url"];
                                   }

                                break;
                            case "soundcloud.com":
                             
                                $ch=@file_get_contents( "http://soundcloud.com/oembed?format=json&url=$this->url&iframe=true");
                                if($ch){
                                     $hash=json_decode($ch);

                                      $doc = self::getDom($hash->html);
                                      $nodes = $doc->getElementsByTagName('*'); 
                                       foreach($nodes as $node) {
                                            if($url=urldecode($node->getAttribute('src'))){
                                                    parse_str($url,$url);
                                                    $url=($url["url"]);
                                                    $tokens = explode('/', $url);
                                                    $this->media_type= $tokens[sizeof($tokens)-2];
                                                    $vkey = strval( + $tokens[sizeof($tokens)-1]);
                                                    $this->media_id=$vkey;
                                                    break;
                                               }
                                      }
                                      $this->type="sound";
                                      $this->image= array($hash->thumbnail_url  );   
                                }
                                break;
                            default:
                                 $this->getImage($dom);
                     }
          }else{

                  if($this->mime=="image/gif" || $this->mime=="image/jpeg" || $this->mime=="image/png" || $this->mime=="image/svg+xml"){
                       $this->type="image";
                       parseImage($url);
                  }
                  if($this->mime=="application/pdf" ){
                       $this->type="image";
                  }     
          }

    }
    
    
    static function getSslPage($url) {
            $timeout = 5; 
            $useragent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0'; 

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent); 
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 

            $result = curl_exec($ch);
            $mimeType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $mimeType=  str_replace(' ','',strtolower($mimeType));
            $urlFinal=curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

            curl_close($ch);
            $return =new stdClass();
            $return->url=$urlFinal;
            $return->mime=$mimeType;
            $return->html=$result;
            return $return;
    }
    
   static function getDomaine($url) {
        $hostname = parse_url($url, PHP_URL_HOST);
        $hostParts = explode('.', $hostname);
        $numberParts = sizeof($hostParts);
        $domain='';

        if(1 === $numberParts) {
            $domain = current($hostParts);
        }
        elseif($numberParts>=2) {
            $hostParts = array_reverse($hostParts);
            $domain = $hostParts[1] .'.'. $hostParts[0];
        }
        return $domain;
    }      
    
    static function getParam($url){
        $query_str = parse_url( $url, PHP_URL_QUERY);
        parse_str($query_str, $query_params);
        return $query_params;
    }
    
  
     static function getDom($html){
                  $doc = new DOMDocument();
                  @$doc->loadHTML($html);
                  return $doc;
     }
    
    
    private function getMeta($doc){
                    $titlenode = $doc->getElementsByTagName('title'); 
                    $title = $titlenode->item(0)->nodeValue;
                    $this->title=$title;

                    $metas=$doc->getElementsByTagName('meta'); 
                     foreach ($metas as $meta) {
                        if($meta->getAttribute('name')=="description"){
                            $description=$meta->getAttribute('content');
                            break;
                        }
                     }
                    $this->description=isset($description)?$description:"";
    }
   private function getImage($doc){
                             $imagenode = $doc->getElementsByTagName('img');
                            $images= Array();
                            foreach ($imagenode as $image) {
                                if( ($imageSrc=$image->getAttribute('src'))!="" ){
                                    if (!in_array($imageSrc, $images)) {
                              
                                            array_push($images, $this->absLink($imageSrc));
                                    }
                                }
                            }
                               $this->image=$images;
    }
 
  private function parseImage($url) { 
        $image=@getimagesize($url);
        var_dump($image);
    }
   
public function absLink($link){
      $hostname = parse_url($link, PHP_URL_HOST);
      if($hostname){
            return $link;
      }else{
         if( ($c=mb_substr_count( $link,"../"))>0){
                $cUrl= parse_url($this->url, PHP_URL_PATH);
                $pos = strripos($link, '../')+3;

                $cUrl=self::strRep($cUrl);
                
                $tokens = explode('/', $cUrl);
                for($i=0; $i<$c ; $i++){
                         array_pop($tokens);       
               }
               $rep=implode("/",$tokens);
               $rep=$rep===""?$rep:$rep."/";
           
               
               return  $this->domain."/".$rep. substr($link, $pos);
         }else{
             return self::strRep($this->url)."/". self::strRep($link);
         }
      }
}

static function strRep($str){
          if($str[0]==="/"){ $str = substr($str,1);    };
          if($str[strlen($str)-1]==="/"){$str = substr($str, 0, -1); };
          return $str;
}

}


?>

