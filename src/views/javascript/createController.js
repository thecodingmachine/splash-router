//var myApp = angular.module('myApp',['ui.event']);
angular.module('myApp', ['ui.utils']);

function CreateControllerCtrl($scope, $http) {
	$scope.controllerName = "SampleController";
	$scope.instanceName = "sampleController";
	$scope.namespace = globalData.namespace;
	$scope.tdbmExists = globalData.tdbmExists;
	$scope.injectLogger = true;
	$scope.injectTemplate = true;
	$scope.injectDaoFactory = true;
	$scope.actions = [{
		url: "",
		method: "index",
		parameters: [],
		anyMethod: true,
		getMethod: false,
		postMethod: false,
		putMethod: false,
		deleteMethod: false,
		view: "twig"
	}];
	
	
	var oldControllerName = $scope.controllerName;
	
	$scope.changeInstanceName = function($event) {
		var targetInstanceName = "";
		if (oldControllerName.length>=1) {
			targetInstanceName = oldControllerName.substring(0,1).toLowerCase()+oldControllerName.substring(1);
		}
		
		if ($scope.instanceName == targetInstanceName) {
			var newTargetInstanceName = "";
			if ($scope.controllerName.length>=1) {
				newTargetInstanceName = $scope.controllerName.substring(0,1).toLowerCase()+$scope.controllerName.substring(1);
			}
			$scope.instanceName = newTargetInstanceName;
		}
		
		oldControllerName = $scope.controllerName;
	}
	
	
	
	$scope.addAction = function() {
		$scope.actions.push({
			url : '',
			method: 'index',
			parameters: [],
			anyMethod: true,
			getMethod: false,
			postMethod: false,
			putMethod: false,
			deleteMethod: false,
			view: "twig",
			twigFile: "views/"+$scope.instanceName.replace("Controller", "")+"/index.twig",
			phpFile: "views/"+$scope.instanceName.replace("Controller", "")+"/index.php",
			redirect: "" 
		});
		
	}
	
	$scope.removeAction = function(action) {
		$scope.actions = jQuery.grep($scope.actions, function(item) {
			return item != action; 
		})
	}
	
	$scope.addParameter = function(action) {
		action.parameters.push({
			name: "",
			type: "string",
			optionnal: false,
			defaultValue: ""
		});
	}
	
	$scope.removeParameter = function(action, parameter) {
		action.parameters = jQuery.grep(action.parameters, function(item) {
			return item != parameter; 
		})
	}
	
	$scope.$watch('instanceName', function() {
		// Let's update the actions path:
		angular.forEach($scope.actions, function(action) {
			action.twigFile = "views/"+$scope.instanceName.replace("Controller", "")+"/"+action.method+".twig";
			action.phpFile = "views/"+$scope.instanceName.replace("Controller", "")+"/"+action.method+".php";
		});
	});
	
	$scope.changeMethod = function(action) {
		action.twigFile = "views/"+$scope.instanceName.replace("Controller", "")+"/"+action.method+".twig";
		action.phpFile = "views/"+$scope.instanceName.replace("Controller", "")+"/"+action.method+".php";
	}
	
	$scope.submit = function() {
		$scope.controllerNameError = false;
		$scope.namespaceError = false;
		$scope.instanceError = false;
		angular.forEach($scope.actions, function(value, key) {
			$scope.actions[key].twigTemplateFileError = false;
			$scope.actions[key].phpTemplateFileError = false;
		});
		
		$http({
			url:'generate',
			data : $.param({
				controllerName: $scope.controllerName,
				instanceName: $scope.instanceName,
				namespace: $scope.namespace,
				injectLogger: $scope.injectLogger,
				injectTemplate: $scope.injectTemplate,
				injectDaoFactory: $scope.injectDaoFactory,
				actions: $scope.actions			
			}),
			method : 'POST',
			headers : {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}
		}).success(function(data) {
			if (data.status == 'ok') {
				window.location = '../ajaxinstance/?name='+$scope.instanceName;
			} else {
				if (typeof(data)=='string') {
					addMessage(data);
				} else {
					$scope = $.extend(true, $scope, data);
					/*angular.forEach(data, function(value, key) {
						$scope[key] = value;
					});*/
				}
			}
		}).error(function(data, status, headers, config) {
			addMessage("An error occured while posting data: "+data+" - "+status);
		});
	}
}