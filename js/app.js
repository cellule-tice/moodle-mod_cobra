'use strict';

// Declare app level module which depends on views, and components
angular.module('elex',
    [
        'ngRoute',
        'ui.router',
        'elex.controllers',
        'elex.services',
        'elex.filters'
    ]
)

.run(function($rootScope) {
    $rootScope.showGlossary = angular.element('#showglossary').text();
    $rootScope.courseid = angular.element('#courseid').text();

})

.config(function ($stateProvider) {
    $stateProvider
        .state("glossary", {
            url: "/:textId",
            templateUrl: "views/text.html",
            controller: 'TextController'
        })
        .state("next", {
            template: "<a ui-sref='glossary'>Back</a>"
        });
});
