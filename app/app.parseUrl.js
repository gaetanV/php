
(function() {
    'use strict';
    angular
            .module('app')
            .controller('parseUrl', parseUrl);

    parseUrl.$inject = ['$scope','parseUrl'];
    
    function parseUrl($scope,parseUrl) { 

        $scope.url="";
        $scope.urlData={};

        $scope.$watch("urlData.image", function(images) {

            if(images){

                       for(var i=0; i<images.length; i++){

                                 var image = new Image();
                           
                                 image.src="../service/safeImage.php?url="+ images[i];
                                 //TO DO DYNAMIC FACTORY
                             
                                 image.onload=  function(){
                                      if(this.height>100&&this.width>100 ) 
                                     {    

                                        var aFile={};
                                         aFile.format=   this.width>this.height?"h":"v";


                                                var canvas = document.createElement("canvas");
                                                canvas.width = this.width;
                                                canvas.height = this.height;
                                                var ctx = canvas.getContext("2d");
                                                ctx.drawImage(this, 0, 0);

                                                aFile.image= canvas.toDataURL("image/jpeg", 1.0);
                                              //  aFile.image=this.src;  /// VOIR L'UN OU L'AUTRE


                                          var callback = function() {

                                                 if(!$scope.urlData.imageSoft){
                                                     $scope.urlData.imageSoft=new Array;
                                                 }
                                                 $scope.urlData.imageSoft.push(aFile);
                                          };


                                          $scope.$apply(callback);
                                     }


                                 }


                     }    
             }
        });


        $scope.submit=function(){
            if( $scope.url!=""){
                 $scope.urlData=parseUrl.parse($scope.url,$scope);    
            }
        };
    }
})();