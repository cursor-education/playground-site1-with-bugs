angular.module("app")
  
  .config([
    '$routeProvider',
    '$locationProvider',
    '$logProvider',
    ($routeProvider, $locationProvider, $logProvider) ->
      console.log 'config'

      $logProvider.debugEnabled(!!window.debug)

      #$routeProvider
      #  .when '/view1',
      #    controller: 'Controller1'
      #    templateUrl: '/app/components/common/view1.tpl'

      #  .when '/view2/:someId',
      #    controller: 'Controller2'
      #    templateUrl: '/view2.tpl'      

      #  .otherwise
      #    redirectTo: '/home'

      #$locationProvider.html5Mode(true)
  ])