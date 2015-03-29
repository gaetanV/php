<?php

class safeImage{
    const OUTPUT_SELF = "SELF";
    const OUTPUT_URL = "URL";
    const OUTPUT_JSON = "JSON";
    
    public  $width , $height , $mime , $ratioX,$ratioY ;
    private $image , $_width, $_height;
    
    public function __construct($url){
        $this->url=$url;
        
        if(($size=getimagesize($this->url))!==false){
            $this->width = $size[0];
            $this->height = $size[1];
            $this->mime=$size["mime"];
            $this->ratioX=$this->width /  $this->height;
            $this->ratioY=$this->height /  $this->width;
        }else{
            return false;
        }
    }
     
    
    private function imagecreate(){
        switch ($this->mime) {
	case 'image/jpeg':
                    $image = imagecreatefromjpeg($this->url);
                    return $image;
	break;
	case 'image/gif':
                    $image = imagecreatefromgif($this->url);
                   return $image;
	break;
	case 'image/png':
                    $image = imagecreatefrompng($this->url);
                     return $image;
	break;
         }
         return false;
    }
    
    public function noresize(){
           if(!$this->createImage()) return false;
            self::renderImage($this->image);
    }
    
    
    public function resizeWith($width){
          if(!$this->createImage()) return false;
          $this->setWidth($width);
           $this->resize();
    }
    
    public function resizeHeight($height){
          if(!$this->createImage()) return false;
           $this->setHeight($height);
           $this->resize();
    }
    
   private function setHeight($height){
           $this->_height=$height;
           $this->_width=round($height*$this->ratioX);
    } 
    
    private function setWidth($width){   
           $this->_width=$width;
           $this->_height=round($width*$this->ratioY );
          
    }
    public function resizeBoth($width,$height,$ratio=true){
           if(!$this->createImage()) return false;
         
           if($ratio===false){
                 $this->_width=$width;
                 $this->_height=$height;
           }else{
                 ///A CALCULER
                 $ratioX=$width/$height;
                 if($ratioX>$this->ratioX){
                      $this->setWidth($width);
                      $this->_cropH=$height; 
                 }else{
                      $this->setHeight($height);
                      $this->_cropW=$width;
                 }
           }
           $this->resize();
    }
    
    private function createImage(){
           if( ($this->image=$this->imagecreate()) !=false){
                  return true;
           }
           return false;
    }
    
    
   private function resize(){
            $pattern = imagecreatetruecolor(  isset($this->_cropW)?$this->_cropW:$this->_width , isset($this->_cropH)?$this->_cropH:$this->_height);
            imagecopyresampled($pattern, $this->image, 0, 0, 0, 0, $this->_width, $this->_height, $this->width, $this->height);
            imagedestroy($this->image);
           self::renderImage($pattern);
     }
    
    static function renderJson($image){
          
    }
     
    static function renderImage($image){
            header('Content-Type: image/jpeg');
            imagejpeg($image, "", 100);
            imagedestroy($image);
    }
    
     static function renderSelf($image){
            imagejpeg($image, "", 100);
     }
    
}


if(isset($_GET['url'])){
            $safeImage= new safeImage($_GET['url']);
            $ratio=true;
             if(!isset($_GET['h'])&&!isset($_GET['w']) ){
                   $safeImage->noresize();  
             }
            if( isset($_GET['ratio']) &&$_GET['ratio']=="false" ){
                $ratio=false;
            }
            
            if(isset($_GET['h'])&&isset($_GET['w']) ){
                    $safeImage->resizeBoth($_GET['w'],$_GET['h'],$ratio);  
            }else{
                if(isset($_GET['h'])){
                    $safeImage->resizeHeight($_GET['h']);  
                }
                if(isset($_GET['w'])){
                     $safeImage->resizeWith($_GET['w']);
                }
            }
            
        

}



?>