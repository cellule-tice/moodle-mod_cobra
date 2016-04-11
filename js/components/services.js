angular.module('cobra.services', [])

.factory('dataService', ['$http', '$rootScope', function($http, $rootScope) {

    var glossaryEntries = [];
    var service =  {
        getEntries : function(textid, courseid) {
            var request = $http({
                method: 'POST',
                url: 'angularrelay.php',
                headers: {
                    'Content-Type': undefined
                },
                data: { action: 'loadGlossary', textid: textid, courseid: courseid}

            });

            return request.then(function(response) {
                if(response.data) {
                    glossaryEntries = response.data;
                }
                else {
                    glossaryEntries = [];
                }
                ///glossaryEntries = response.data;

                return glossaryEntries;
            });
        },
        getText : function(textId) {
            var request = $http({
                method: 'POST',
                url: 'angularrelay.php',
                headers: {
                    'Content-Type': undefined
                },
                data: { action: 'loadText', textId: textId}

            });

            return request.then(function(response) {
                //glossaryEntries = response.data;
                return response.data;
            });
        },
        addEntry2 : function(lingEntity, textId) {
            console.log(lingEntity, textId);
            var addRequest = $http({
                method: 'POST',
                url: 'angularrelay.php',
                headers: {
                    'Content-Type': undefined
                },
                data: { action: 'addToGlossary', lingEntity: lingEntity, textId: textId}

            });
            return addRequest.then(function(response) {
                $rootScope.$broadcast('entryAdded', lingEntity);
                return true;
            });
        },
        addEntry : function(lingEntity, textId) {
            var addRequest = $http({
                method: 'POST',
                url: 'angularrelay.php',
                headers: {
                    'Content-Type': undefined
                },
                data: { action: 'addToGlossary', lingEntity: lingEntity, textid: textId, courseid: $rootScope.courseid}

            });
            return addRequest.then(function(response) {
                $rootScope.$broadcast('entryAdded', lingEntity);
                return true;
            });
        },
        removeEntry : function(lingEntity) {
            console.log('in data service')
            var removeRequest = $http({
                method: 'POST',
                url: 'angularrelay.php',
                headers: {
                    'Content-Type': undefined
                },
                data: {action: 'removeFromGlossary', lingEntity: lingEntity, courseid:$rootScope.courseid}

            });
            return removeRequest.then(function (response) {
                console.log(response);
                $rootScope.$broadcast('entryDeleted', lingEntity);
                return true;
            });
        }
    };
    return service;
}])
