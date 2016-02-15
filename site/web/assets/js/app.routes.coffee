angular.module("app")
  
  .config([
    '$routeProvider'
    '$locationProvider'
    '$logProvider'
    '$mdThemingProvider'
    ($routeProvider, $locationProvider, $logProvider, $mdThemingProvider) ->
      console.log 'config', $mdThemingProvider

      $logProvider.debugEnabled(!!window.debug)

      $mdThemingProvider.theme('default')
        .primaryPalette('deep-orange',
          'default': '900'
          'hue-2': '500'
        )
        .backgroundPalette('grey',
          'default': '200'
        )
        .accentPalette('orange')

      $routeProvider
        .when '/login',
          controller: 'loginCtrl'
          templateUrl: '/templates/test.html'

        .when '/home',
          controller: 'homeCtrl'
          templateUrl: '/templates/test.html'
          access:
            requiresLogin: true

        .when '/admin',
          controller: 'adminCtrl'
          templateUrl: '/templates/test.html'
          access:
            requiresLogin: true
            requiredPermissions: ['Admin', 'UserManager']
            permissionType: 'AtLeastOne'

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

  # delay http response
  .config([
    '$provide',
    ($provide) ->
      $provide.decorator '$httpBackend', ($delegate) ->
        proxy = (method, url, data, callback, headers) ->
          interceptor = ->
            _this = this
            _arguments = arguments

            setTimeout ->
              callback.apply(_this, _arguments)
            , Math.random() * 1500 + 100

          $delegate.call(this, method, url, data, interceptor, headers)

        for key of $delegate
          proxy[key] = $delegate[key]

        proxy
  ])