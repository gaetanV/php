(function() {
    'use strict';

angular
            .module('app')
            .factory('parseUrl', parseUrl);
      parseUrl.$inject = ["$http","$rootScope"];
      
    function parseUrl($http,$rootScope) {

            var busy=false;
            var urlParse="../service/parseurl.php";
            var urlData=[];
            
           return({
                urlData:urlData,
                parse:parse,

            });
            
            
        function parse( url  ) {
           
            if(!busy){
                 urlData.push({});
                var ID=urlData.length-1;

                busy=true;
                $http.get(urlParse,{ params: { url: url }  }).success(function(data){
                       angular.copy(data, urlData[ID]);
                }).error( busy=false);
                return urlData[ID];

            }
        };
    }  
})();