$(document).ready ->
    $('select').material_select()

    angular.element(document).ready ->
      angular.bootstrap document, ['app']

angular.module("app", [
    'ngRoute'
    'ngMockE2E'
])
  
  .run(($httpBackend, debug) ->
    console.log 'run'

    #$httpBackend = angular.injector(['ng']).get('$httpBackend')
  
    $httpBackend
      .when('GET', '/api/v1/data')
      .respond('test')

    null
  )

  .constant('debug', !!window.debug)

