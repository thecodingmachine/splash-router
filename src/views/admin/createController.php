<?php /* @var $this Mouf\Mvc\Splash\Controllers\Admin\SplashCreateControllerController */?>
<h1>Create a new controller</h1>

<script type="text/javascript">
globalData = <?php echo json_encode(array(
	"sourceDirectory"=>$this->sourceDirectory,
	"namespace"=>$this->controllerNamespace,
	"tdbmExists"=>file_exists(ROOT_PATH.'../database.tdbm')
)); ?>
</script>

<div ng-app="myApp">
<form ng-submit="submit()" class="form-horizontal" ng-controller="CreateControllerCtrl">
<input type="hidden" id="selfedit" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit) ?>" />

<?php if (!$this->autoloadDetected) { ?>
<div class="alert">Warning! Splash could not detect the autoload section of your <code>composer.json</code> file.
Unless you are developing your own autoload system, you should configure <code>composer.json</code> to <a href="http://getcomposer.org/doc/01-basic-usage.md#autoloading" target="_blank">define a source directory and a root namespace using PSR-0</a>.</div>
<?php } ?>

<div class="control-group" ng-class="{error:controllerNameError}">
	<label class="control-label" for="controllerclass">Controller class name:</label>
	<div class="controls">
		<input type="text" id="controllerclass" name="controllerclass" ng-model="controllerName" ui-event="{keyup: 'changeInstanceName($event)'}"></input><span class="help-inline" ng-show="controllerNameError">{{controllerNameError}}</span>
		<span class="help-block">The controller class name, <strong>without the namespace</strong>. It is good practice to start it with an upper-case letter and end it with the word "Controller".</span>
	</div>
</div>

<div class="control-group" ng-class="{error:sourceDirectoryError}">
	<label class="control-label" for="sourcedirectory">Source directory:</label>
	<div class="controls">
		<input type="text" id="sourcedirectory" name="sourcedirectory" ng-model="sourceDirectory"></input><span class="help-inline" ng-show="sourceDirectoryError">{{sourceDirectoryError}}</span>
		<span class="help-block">This is the directory containing your source code (it should be configured in the "autoload" section of your <em>composer.json</em> file.)</span>
	</div>
</div>

<div class="control-group" ng-class="{error:namespaceError}">
	<label class="control-label" for="namespace">Namespace:</label>
	<div class="controls">
		<input type="text" id="namespace" name="namespace" ng-model="namespace"></input><span class="help-inline" ng-show="namespaceError">{{namespaceError}}</span>
		<span class="help-block">The namespace of the controller. The vendor name (the first part of the namespace) should be referred in the "autoload" section of your <em>composer.json</em> file.</span>
	</div>
</div>

<div class="control-group" ng-class="{error:instanceError}">
	<label class="control-label" for="instancename">Instance name:</label>
	<div class="controls">
		<input type="text" id="instancename" name="instancename" ng-model="instanceName"></input><span class="help-inline" ng-show="instanceError">{{instanceError}}</span>
		<span class="help-block">The name of the instance for this controller. Usually the class name, but camel-cased.</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label" for="instancename">Injected instances:</label>
	<div class="controls">
		<label class="checkbox inline">
	    	<input type="checkbox" ng-model="injectLogger" /> Logger
	    </label>
	    <label class="checkbox inline">
	    	<input type="checkbox" ng-model="injectTemplate" /> Template and content block
	    </label>
	    <label class="checkbox inline" ng-show="tdbmExists">
	    	<input type="checkbox" ng-model="injectDaoFactory" /> DAO Factory
	    </label>
	</div>
</div>

<fieldset ng-repeat="action in actions">
	<a href="" ng-click="removeAction(action)" class="btn btn-danger pull-right"><i class="icon-remove icon-white"></i> Remove</a>
	<legend>Action "{{action.method}}"</legend>
	<div class="control-group">
		<label class="control-label">URL:</label>
		<div class="controls">
			<input type="text" name="url[]" ng-model="action.url"></input>
			<span class="help-block"><ul>
				<li>URL is relative to the root of you application (<code>ROOT_URL</code>)</li>
				<li>Pay attention to the trailing /. For instance: <code>user/</code> and <code>user</code> are two different URLs</li>
				<li>You can use placeholders. For instance: <code>user/{id}/edit</code> is a valid URL</li>
			</ul></span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label">HTTP method:</label>
		<div class="controls">
		    <label class="checkbox inline">
		    	<input type="checkbox" ng-model="action.anyMethod"> Any
		    </label>
		    <label class="checkbox inline">
		    	<input type="checkbox" ng-model="action.getMethod" ng-disabled="action.anyMethod"> GET
		    </label>
		    <label class="checkbox inline">
		    	<input type="checkbox" ng-model="action.postMethod" ng-disabled="action.anyMethod"> POST
		    </label>
		    <label class="checkbox inline">
		    	<input type="checkbox" ng-model="action.putMethod" ng-disabled="action.anyMethod"> PUT
		    </label>
		    <label class="checkbox inline">
		    	<input type="checkbox" ng-model="action.deleteMethod" ng-disabled="action.anyMethod"> DELETE
		    </label>
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label">Method name:</label>
		<div class="controls">
			<input type="text" name="method[]" ng-model="action.method" ui-event="{keyup: 'changeMethod(action)'}"></input>
			<span class="help-block">The name of the method for this action.</span>
		</div>
	</div>
	
	<div class="control-group">
		<label class="control-label">Parameters:</label>
		<div class="controls">
			<div class="well" ng-repeat="parameter in action.parameters">
				<a href="" ng-click="removeParameter(action, parameter)" class="btn btn-danger pull-right"><i class="icon-remove-sign icon-white"></i> Remove</a>
			
				<div class="control-group">
					<label class="control-label">Name:</label>
					<div class="controls">
						<input type="text" ng-model="parameter.name" />
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label">Type:</label>
					<div class="controls">
						<select ng-model="parameter.type" />
							<option value="string">string</option>
							<option value="int">int</option>
							<option value="number">number</option>
							<option value="array">array</option>
							<option value="mixed">mixed</option>
						</select>
					</div>
				</div>
				
				<div class="control-group">
					<label class="control-label">Optionnal:</label>
					<div class="controls">
						<input type="checkbox" ng-model="parameter.optionnal" />
					</div>
				</div>
				
				<div class="control-group" ng-show="parameter.optionnal">
					<label class="control-label">Default value:</label>
					<div class="controls">
						<input type="text" ng-model="parameter.defaultValue" />
					</div>
				</div>							
				
			</div>
			
			<a href="" ng-click="addParameter(action)" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Add parameter</a>
		
		</div>
				
	</div>
	
	<div class="control-group">
		<label class="control-label">Response:</label>
		<div class="controls">
		    <label class="checkbox inline" ng-show="injectTemplate">
		    	<input type="radio" ng-model="action.view" value="twig"> Twig view
		    </label>
		    <label class="checkbox inline" ng-show="injectTemplate">
		    	<input type="radio" ng-model="action.view" value="php"> PHP view
		    </label>
		    <label class="checkbox inline">
		    	<input type="radio" ng-model="action.view" value="json"> JSON
		    </label>
		    <label class="checkbox inline">
		    	<input type="radio" ng-model="action.view" value="redirect"> Redirect
		    </label>
		    <label class="checkbox inline">
		    	<input type="radio" ng-model="action.view" value="none"> No output
		    </label>
		</div>
	</div>
	
	<div class="control-group" ng-show="action.view == 'twig'" ng-class="{error:action.twigTemplateFileError}">
		<label class="control-label">Twig template file:</label>
		<div class="controls">
			<input type="text" ng-model="action.twigFile" class="input-xxlarge" /><span class="help-inline" ng-show="action.twigTemplateFileError">{{action.twigTemplateFileError}}</span>
		</div>
	</div>

	<div class="control-group" ng-show="action.view == 'php'" ng-class="{error:action.phpTemplateFileError}">
		<label class="control-label">PHP template file:</label>
		<div class="controls">
			<input type="text" ng-model="action.phpFile" class="input-xxlarge" /><span class="help-inline" ng-show="action.phpTemplateFileError">{{action.phpTemplateFileError}}</span>
		</div>
	</div>

	<div class="control-group" ng-show="action.view == 'redirect'" ng-class="{error:action.redirectError}">
		<label class="control-label">Redirect URL:</label>
		<div class="controls">
			<input type="text" ng-model="action.redirect" class="input-xxlarge" /><span class="help-inline" ng-show="action.redirectError">{{action.redirectError}}</span>
		</div>
	</div>
	
</fieldset>

<div class="form-actions">
	<a href="" ng-click="addAction()" class="btn btn-success"><i class="icon-plus icon-white"></i> Add another action</a>
	<button type="submit" class="btn btn-primary">Generate controller</button>
</div>

</form>
</div>
