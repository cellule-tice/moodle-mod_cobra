angular.module('cobra.services', [])

.factory('DataService', ['$http', '$rootScope', function($http, $rootScope) {

    var glossaryEntries = [];
    var service = {
        getEntries : function(textId, courseId) {
            var request = $http({
                method: 'POST',
                url: 'angularrelay.php',
                headers: {
                    'Content-Type': undefined
                },
                data: {action: 'loadGlossary', textid: textId, courseid: courseId}

            });

            return request.then(function(response) {
                if(response.data) {
                    glossaryEntries = response.data;
                }
                else {
                    glossaryEntries = [];
                }

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
                data: {action: 'loadText', textid: textId}
            });

            return request.then(function(response) {
                return response.data;
            });
        },
        addEntry : function(lingEntity, textId) {
            var addRequest = $http({
                method: 'POST',
                url: 'angularrelay.php',
                headers: {
                    'Content-Type': undefined
                },
                data: { action: 'addToGlossary', lingentity: lingEntity, textid: textId, courseid: $rootScope.courseId}

            });
            return addRequest.then(function(response) {
                $rootScope.$broadcast('entryAdded', lingEntity);
                return true;
            });
        },
        removeEntry : function(lingEntity) {
            var removeRequest = $http({
                method: 'POST',
                url: 'angularrelay.php',
                headers: {
                    'Content-Type': undefined
                },
                data: {action: 'removeFromGlossary', lingentity: lingEntity, courseid:$rootScope.courseId}

            });
            return removeRequest.then(function (response) {
                $rootScope.$broadcast('entryDeleted', lingEntity);
                return true;
            });
        }
    };
    return service;
}])
