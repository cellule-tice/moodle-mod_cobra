/**
 * Created by jmeuriss on 14/01/2015.
 */
angular.module('cobra.filters', [])

.filter('unsafe', function($sce) {
    return function(val) {
        return $sce.trustAsHtml(val);
    };
})