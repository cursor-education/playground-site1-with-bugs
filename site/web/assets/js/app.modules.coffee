angular.module("app")

  .service('MyService', ($q, $timeout) ->
    console.log 'service.MyService'

    @getData = ->
      delay = 300
      origPromise = $http.get('someUrl')
      deferred = $q.defer()

      $timeout ->
        deferred.resolve origPromise
      , delay

      defered.promise
  )

  .directive('footer', ->
    restrict: 'A'
    replace: false
    templateUrl: '/templates/footer.html'
    controller: ['$scope', '$filter', ($scope, $filter) ->
      console.log 'footer'
    ]
  )

  .directive('header', ->
    restrict: 'A'
    replace: false
    templateUrl: '/templates/header.html'
    controller: ['$scope', '$filter', ($scope, $filter) ->
      console.log 'header'
    ]
  )

  .controller('baseController', ($scope, $http, $httpBackend) ->
    console.log 'baseController'

    $scope.status = 'wait..'

    $http.get('/api/v1/data')
      .success (data, status, headers) ->
        $scope.status = data
      .error ->
        $scope.status = 'error'
  )

  .controller('defaultController', ($rootScope, $scope, $mdDialog, $http, $route, $log, $controller) ->
    $controller('baseController', {$scope: $scope})

    console.log(angular.module("app").service('MyService'))

    $rootScope.pageTitle = 'test'

    $scope.items = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21]
    #$rootScope.pageTitle = 'default'

    console.log 'defaultController'

    #$log.debug 'test'
    #$log.error 'test'
    #$log.info 'test'
    #$log.log 'test'
    #$log.warn 'test'

    #title = '1'

    #$scope.$route = $route
    $scope.isLoading = false

    $scope.showAlert = (ev) ->
      $mdDialog.show(
        $mdDialog.alert()
          .parent(angular.element(document.querySelector('#popupContainer')))
          .clickOutsideToClose(true)
          .title('This is an alert title')
          .textContent('You can specify some description text in here.')
          .ariaLabel('Alert Dialog Demo')
          .ok('Got it!')
          .targetEvent(ev)
      )

    $scope.dosome = ->
      $rootScope.pageTitle = 'test22'

      $scope.isLoading = true
      $scope.loadingProgress = Math.random() * 90 + 10

      $scope.items = []
      #console.log '000'
      #for i=0;Math.random() * 10 + 1
      #  console.log 111

      $scope.status = 'wait..'

      $http.get('/api/v1/data')
        .success (data, status, headers) ->
          $scope.isLoading = false
          $scope.status = data
        .error ->
          $scope.status = 'error'

    #$scope.title = title
    $scope.pageSize = 2

    #$scope.articles = []

    #$scope.getTitle = -> title
  )