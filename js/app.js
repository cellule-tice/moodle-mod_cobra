'use strict';

// Declare app level module which depends on views, and components.
angular.module('cobra',
    [
        'ngRoute',
        'ui.router',
        'cobra.controllers',
        'cobra.services',
        'cobra.filters'
    ]
)

.run(function($rootScope) {
    $rootScope.showGlossary = angular.element('#showglossary').text();
    $rootScope.courseId = angular.element('#courseid').text();

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
