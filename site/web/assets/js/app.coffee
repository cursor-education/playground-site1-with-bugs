angular.module("app", [
    'ngRoute'
    'ngMockE2E'
    'ngMaterial'
    'ngMessages'
])
  .run(($httpBackend, debug) ->
    console.log 'run' if debug

    $httpBackend
      .when('GET', '/api/v1/data')
      .respond('test')

    $httpBackend.whenGET(/^\/templates\//).passThrough()

    null
  )

  .constant('debug', !!window.debug)

#angular.element(document).ready ->
#  angular.bootstrap document, ['app']