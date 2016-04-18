/**
 * Created by jmeuriss on 13/01/2015.
 */

angular.module('cobra.controllers', ['ngRoute'])

.controller('TextController',
    ['$scope', '$rootScope', '$stateParams', 'DataService',
        function($scope, $rootScope, $stateParams, DataService) {

    $scope.dataLoaded = false;
    $scope.showGlossary = $rootScope.showGlossary;
    $scope.text = '';
    $scope.glossaryEntries = {};
    $scope.newEntries = [];
    $rootScope.textId = $stateParams.textId;
    DataService.getText($stateParams.textId).then(function(data) {
        $scope.text = data;
    }).then(function() {
        if ('SHOW' == $scope.showGlossary) {
            DataService.getEntries($stateParams.textId, $rootScope.courseId).then(function(data) {
                $scope.glossaryEntries = data;
                $scope.dataLoaded = true;
                $("#glossary").css('height', $("#cobracontent").css('height'));
            });
        }
    });

    $scope.$on('entryAdded', function(event, args) {
        $scope.newEntries.push(args);
        $scope.newEntry = args;
        DataService.getEntries($stateParams.textId, $rootScope.courseId).then(function(data) {
            $scope.glossaryEntries = data;
            angular.forEach ($scope.glossaryEntries, function(value, key) {
                if ($scope.newEntries.indexOf(value.ling_entity) > -1) {
                     value.new = true;
                }

            });
        });
    });

    $scope.$on('entryDeleted', function(event, args) {
        var indexToRemove = false;
        DataService.getEntries($stateParams.textId, $rootScope.courseId).then(function(data) {
            $scope.glossaryEntries = data;
            angular.forEach ($scope.newEntries, function(value, key) {
                if (value == args) {
                    indexToRemove = key;
                }
            });
            if (indexToRemove !== false){
                $scope.newEntries.splice(indexToRemove, 1);
            }

            angular.forEach ($scope.glossaryEntries, function(value, key) {
                if ($scope.newEntries.indexOf(value.ling_entity) > -1) {
                    value.new = true;
                }
            });
        });

    });

    $scope.addEntry = function(lingEntity) {
        DataService.addEntry(lingEntity, $rootScope.textId)
            .then(function(result) {
            });
    }

    $scope.removeEntry = function(lingEntity) {
        DataService.removeEntry(lingEntity)
            .then(function(result) {
            });
    };

    // Test to populate ng-click dynamically...
    $scope.displayLemma = function(object) {
        console.log(object);
        console.log(object.target.attributes.name.value);
    }
}])
