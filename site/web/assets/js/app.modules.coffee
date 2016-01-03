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

  .controller('baseCtrl', ($scope, $http, $httpBackend) ->
    console.log 'baseCtrl'

    $scope.status = 'wait..'

    $http.get('/api/v1/data')
      .success (data, status, headers) ->
        $scope.status = data
      .error ->
        $scope.status = 'error'
  )

  .controller('defaultCtrl', ($rootScope, $scope, $route, $log, $controller) ->
    $controller('baseCtrl', {$scope: $scope})

    console.log(angular.module("app").service('MyService'))

    $scope.items = [1,2,3,4,5,6,7,8,9,10,11]
    #$rootScope.pageTitle = 'default'


    console.log 'defaultCtrl'

    #$log.debug 'test'
    #$log.error 'test'
    #$log.info 'test'
    #$log.log 'test'
    #$log.warn 'test'

    #title = '1'

    #$scope.$route = $route

    #$scope.title = title
    $scope.pageSize = 2

    #$scope.articles = []

    #$scope.getTitle = -> title
  )