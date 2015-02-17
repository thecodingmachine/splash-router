<?php
namespace Mouf\Mvc\Splash\Controllers\Admin;

use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\MoufUtils;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\Html\Utils\WebLibraryManager\WebLibrary;
use Psr\Log\LoggerInterface;
use Mouf\MoufManager;
use Mouf\MoufCache;

/**
 * A controller used to create controllers in Splash.
 */
class SplashCreateControllerController extends Controller
{
	/**
	 * The template used by the Splash page.
	 *
	 * @var TemplateInterface
	 */
	public $template;

	/**
	 *
	 * @var HtmlBlock
	 */
    public $content;

	protected $selfedit;
	protected $sourceDirectory;
	protected $controllerNamespace;
	protected $autoloadDetected;

	/**
	 * Displays the create controller page.
	 *
	 * @Action
	 * @param string $sourcedirectory
	 * @param string $controllernamespace
	 * @param string $selfedit
	 */
	public function index($sourcedirectory = null, $controllernamespace = null, $selfedit = "false")
	{
		$this->selfedit = $selfedit;
		$this->sourceDirectory = $sourcedirectory;
		$this->controllerNamespace = $controllernamespace;

		if ($this->sourceDirectory == null && $this->controllerNamespace == null) {
			$autoloadNamespaces = MoufUtils::getAutoloadNamespaces();
			if ($autoloadNamespaces) {
				$this->autoloadDetected = true;
				$rootNamespace = $autoloadNamespaces[0]['namespace'].'\\';
				$this->sourceDirectory = $autoloadNamespaces[0]['directory'];
				$this->controllerNamespace = $rootNamespace."Controllers";
			} else {
				$this->autoloadDetected = false;
				$this->sourceDirectory = "src/";
				$this->controllerNamespace = "YourApplication\\Controllers";
			}
		} else {
			$this->autoloadDetected = true;
		}

		$this->template->getWebLibraryManager()->addLibrary(new WebLibrary(
				array(
						"../mvc.splash-common/src/views/javascript/angular.min.js",
						"../mvc.splash-common/src/views/javascript/ui-utils.min.js",
						"../mvc.splash-common/src/views/javascript/createController.js"
				)));

		$this->content->addFile(dirname(__FILE__)."/../../../../../views/admin/createController.php", $this);
		$this->template->toHtml();
	}

	/**
	 * Triggers the controller generation.
	 *
	 * @Action
	 * @param string $controllerName
	 * @param string $instanceName
	 * @param string $sourceDirectory
	 * @param string $namespace
	 * @param string $injectLogger
	 * @param string $injectTemplate
	 * @param string $injectDaoFactory
	 * @param array $actions
	 */
	public function generate($controllerName, $instanceName, $sourceDirectory, $namespace, $injectLogger = false,
			$injectTemplate = false, $injectDaoFactory = false,    $actions = array()) {

		$injectLogger = ($injectLogger=='false') ? false : $injectLogger;
		$injectTemplate = ($injectTemplate=='false') ? false : $injectTemplate;
		$injectDaoFactory = ($injectDaoFactory=='false') ? false : $injectDaoFactory;

		$moufManager = MoufManager::getMoufManagerHiddenInstance();

		$sourceDirectory = trim($sourceDirectory, '/\\');

		$errors = array();
		if (!file_exists(ROOT_PATH.'../../../'.$sourceDirectory)) {
			$errors['sourceDirectoryError'] = 'This directory does not exist.';
		}
		if (!preg_match('/^[a-z_]\w*$/i', $controllerName)) {
			$errors['controllerNameError'] = 'This is not a valid PHP class name.';
		}
		if (!preg_match('/^[a-z_][\w\\\\]*$/i', $namespace)) {
			$errors['namespaceError'] = 'This is not a valid PHP namespace.';
		}

		$namespace = trim($namespace, '\\');

		if (!file_exists(ROOT_PATH.'../database.tdbm') && $injectDaoFactory) {
			$injectDaoFactory = false;
		}

		// Check that instance does not already exists
        if ($moufManager->has($instanceName)) {
			$errors['instanceError'] = 'This instance already exists.';
		}

		$injectTwig = false;
		$importJsonResponse = false;
		$importHtmlResponse = false;
		$importRedirectResponse = false;

		foreach ($actions as $key => $action) {
			 // Check if the view file exists
            if ($injectTemplate && $action['view'] == 'twig') {
				$injectTwig = true;
				$importHtmlResponse = true;
				$twigFile = ltrim($action['twigFile'], '/\\');

				$viewDirName = ROOT_PATH.'../../../'.dirname($twigFile);
				$result = $this->createDirectory($viewDirName);
				if (!$result) {
					$errors['actions'][$key]['twigTemplateFileError'] = 'Unable to create directory "'.$viewDirName.'"';
				}

				if (file_exists(ROOT_PATH.'../../../'.$twigFile)) {
					$errors['actions'][$key]['twigTemplateFileError'] = 'This file already exists.';
				}
			}
			if ($injectTemplate && $action['view'] == 'php') {
				$importHtmlResponse = true;

				$phpFile = ltrim($action['phpFile'], '/\\');

				$viewDirName = ROOT_PATH.'../../../'.dirname($phpFile);
				$result = $this->createDirectory($viewDirName);
				if (!$result) {
					$errors['actions'][$key]['phpTemplateFileError'] = 'Unable to create directory "'.$viewDirName.'"';
				}

				if (file_exists(ROOT_PATH.'../../../'.$phpFile)) {
					$errors['actions'][$key]['phpTemplateFileError'] = 'This file already exists.';
				}
			}
			if ($action['view'] == 'redirect') {
				if (!isset($action['redirect']) || empty($action['redirect'])) {
					$errors['actions'][$key]['redirectError'] = 'Redirection URL cannot be empty.';
				}
				$importRedirectResponse = true;
			}
			if ($action['view'] == 'json') {
				$importJsonResponse = true;
			}

		}

		// TODO: check that URLs are not in error.


		if (!$errors) {
			$controllerDirectory = ROOT_PATH.'../../../'.($sourceDirectory ? $sourceDirectory.'/' : '').($namespace ? str_replace('\\', '/', $namespace).'/' : '');
			$result = $this->createDirectory($controllerDirectory);
			if (!$result) {
				$errors['namespaceError'] = 'Unable to create directory: "'.$controllerDirectory.'"';
			} elseif (file_exists($controllerDirectory.$controllerName.'.php')) {
				$errors['namespaceError'] = 'The file "'.$controllerDirectory.$controllerName.'.php already exists."';
			} elseif (!is_writable($controllerDirectory)) {
				$errors['namespaceError'] = 'Unable to write file in directory: "'.$controllerDirectory.'"';
			}

			if (!$errors) {
				ob_start();
				echo '<?php
';
				?>
namespace <?= $namespace ?>;

use Mouf\Mvc\Splash\Controllers\Controller;
<?php if ($injectTemplate) { ?>
use Mouf\Html\Template\TemplateInterface;
use Mouf\Html\HtmlElement\HtmlBlock;
<?php } ?>
<?php if ($injectLogger) { ?>
use Psr\Log\LoggerInterface;
<?php } ?>
<?php if ($injectDaoFactory) { ?>
use <?= $moufManager->getVariable('tdbmDefaultDaoNamespace')."\\".$moufManager->getVariable('tdbmDefaultDaoFactoryName') ?>;
<?php } ?>
<?php if ($injectTwig) { ?>
use \Twig_Environment;
use Mouf\Html\Renderer\Twig\TwigTemplate;
<?php } ?>
<?php if ($importJsonResponse) { ?>
use Symfony\Component\HttpFoundation\JsonResponse;
<?php } ?>
<?php if ($importRedirectResponse) { ?>
use Symfony\Component\HttpFoundation\RedirectResponse;
<?php } ?>
<?php if ($importHtmlResponse) { ?>
use Mouf\Mvc\Splash\HtmlResponse;
<?php } ?>

/**
 * TODO: write controller comment
 */
class <?= $controllerName ?> extends Controller {

<?php if ($injectLogger) { ?>
    /**
     * The logger used by this controller.
     * @var LoggerInterface
     */
    private $logger;

<?php } ?>
<?php if ($injectTemplate) { ?>
    /**
     * The template used by this controller.
     * @var TemplateInterface
     */
    private $template;

    /**
     * The main content block of the page.
     * @var HtmlBlock
     */
    private $content;

<?php } ?>
<?php if ($injectDaoFactory) { ?>
    /**
     * The DAO factory object.
     * @var DaoFactory
     */
    private $daoFactory;

<?php } ?>
<?php if ($injectTwig) { ?>
    /**
     * The Twig environment (used to render Twig templates).
     * @var Twig_Environment
     */
    private $twig;

<?php } ?>

    /**
     * Controller's constructor.
<?php
if ($injectLogger) {
	echo "     * @param LoggerInterface \$logger The logger\n";
}
if ($injectTemplate) {
	echo "     * @param TemplateInterface \$template The template used by this controller\n";
	echo "     * @param HtmlBlock \$content The main content block of the page\n";
}
if ($injectDaoFactory) {
	echo "     * @param DaoFactory \$daoFactory The object in charge of retrieving DAOs\n";
}
if ($injectTwig) {
	echo "     * @param Twig_Environment \$twig The Twig environment (used to render Twig templates)\n";
}
?>
     */
    public function __construct(<?php
$parameters = array();
if ($injectLogger) {
    $parameters[] = 'LoggerInterface $logger';
}
if ($injectTemplate) {
    $parameters[] = 'TemplateInterface $template';
    $parameters[] = 'HtmlBlock $content';
}
if ($injectDaoFactory) {
    $parameters[] = 'DaoFactory $daoFactory';
}
if ($injectTwig) {
    $parameters[] = 'Twig_Environment $twig';
}
echo implode(', ', $parameters);
?>) {
<?php if ($injectLogger) {?>
        $this->logger = $logger;
<?php }
if ($injectTemplate) {?>
        $this->template = $template;
        $this->content = $content;
<?php }
if ($injectDaoFactory) {?>
        $this->daoFactory = $daoFactory;
<?php }
if ($injectTwig) {?>
        $this->twig = $twig;
<?php } ?>
    }

<?php foreach ($actions as $action):
	// First step, let's detect the {parameters} in the URL and add them if necessarry
    // TODO
    // TODO
    // TODO
    // TODO

?>
    /**
     * @URL <?= $action['url'] ?>

<?php if ($action['anyMethod'] == 'false') {
	if ($action['getMethod'] == 'true') {
		echo "     * @Get\n";
	}
	if ($action['postMethod'] == 'true') {
		echo "     * @Post\n";
	}
	if ($action['putMethod'] == 'true') {
		echo "     * @Put\n";
	}
	if ($action['deleteMethod'] == 'true') {
		echo "     * @Delete\n";
	}
}
if (isset($action['parameters'])) {
	$parameters = $action['parameters'];
	foreach ($parameters as $parameter) {
		echo '     * @param '.$parameter["type"].' $'.$parameter['name']."\n";
	}
} else {
	$parameters = array();
}
?>
     */
    public function <?= $action['method'] ?>(<?php
$parametersCode = array();
foreach ($parameters as $parameter) {
	$parameterCode = '$'.$parameter['name'];
	if ($parameter['optionnal'] == 'true') {
		if ($parameter['type'] == 'int') {
			$defaultValue = (int) $parameter['defaultValue'];
		} elseif ($parameter['type'] == 'number') {
			$defaultValue = (float) $parameter['defaultValue'];
		} else {
			$defaultValue = $parameter['defaultValue'];
		}
		$parameterCode .= ' = '.var_export($defaultValue, true);
	}
	$parametersCode[] = $parameterCode;
}
echo implode(', ', $parametersCode);
?>) {
        // TODO: write content of action here

<?php if ($injectTemplate && $action['view'] == 'twig'): ?>
        // Let's add the twig file to the template.
        $this->content->addHtmlElement(new TwigTemplate($this->twig, <?php var_export($action['twigFile']); ?>, array("message"=>"world")));

        return new HtmlResponse($this->template);
<?php elseif ($injectTemplate && $action['view'] == 'php'): ?>
        // Let's add the view to the content.
        // Note: $this is passed as the scope, so in the view file, you can refer to protected
        // and public variables and methods of this constructor using "$this".
        $this->content->addFile(ROOT_PATH.<?php var_export($action['phpFile']) ?>, $this);

        return new HtmlResponse($this->template);
<?php elseif ($action['view'] == 'json'): ?>

        return new JsonResponse([ "status"=>"ok" ]);
<?php elseif ($action['view'] == 'redirect'): ?>

        return new RedirectResponse(<?php var_export($action['redirect']); ?>);
<?php endif; ?>
    }
<?php endforeach; ?>
}
<?php
				$file = ob_get_clean();

				file_put_contents($controllerDirectory.$controllerName.'.php', $file);
				chmod($controllerDirectory.$controllerName.'.php', 0664);

				// Now, let's create the views files
                foreach ($actions as $action) {
					if ($injectTemplate && $action['view'] == 'twig') {
						$twigTemplateFile = $this->generateTwigView();

						$twigFile = ltrim($action['twigFile'], '/\\');

						file_put_contents(ROOT_PATH.'../../../'.$twigFile, $twigTemplateFile);
						chmod(ROOT_PATH.'../../../'.$twigFile, 0664);
					} elseif ($injectTemplate && $action['view'] == 'php') {
						$phpTemplateFile = $this->generatePhpView($namespace.'\\'.$controllerName);

						$phpFile = ltrim($action['phpFile'], '/\\');

						file_put_contents(ROOT_PATH.'../../../'.$phpFile, $phpTemplateFile);
						chmod(ROOT_PATH.'../../../'.$phpFile, 0664);
					}
				}

				// Now, let's create the instance
                $controllerInstance = $moufManager->createInstance($namespace."\\".$controllerName);
				$controllerInstance->setName($instanceName);
				if ($injectLogger) {
					if ($moufManager->has('psr.errorLogLogger')) {
						$controllerInstance->getProperty("logger")->setValue($moufManager->getInstanceDescriptor('psr.errorLogLogger'));
					}
				}
				if ($injectTemplate) {
					if ($moufManager->has('bootstrapTemplate')) {
						$controllerInstance->getProperty("template")->setValue($moufManager->getInstanceDescriptor('bootstrapTemplate'));
					}
					if ($moufManager->has('block.content')) {
						$controllerInstance->getProperty("content")->setValue($moufManager->getInstanceDescriptor('block.content'));
					}
				}
				if ($injectDaoFactory) {
					if ($moufManager->has('daoFactory')) {
						$controllerInstance->getProperty("daoFactory")->setValue($moufManager->getInstanceDescriptor('daoFactory'));
					}
				}
				if ($injectTwig) {
					if ($moufManager->has('twigEnvironment')) {
						$controllerInstance->getProperty("twig")->setValue($moufManager->getInstanceDescriptor('twigEnvironment'));
					}
				}

				$moufManager->rewriteMouf();

				// There is a new class, let's purge the cache
                $moufCache = new MoufCache();
				$moufCache->purgeAll();

				// TODO: purge cache

			}
		}

		header("Content-type: application/json");
		if ($errors) {
			$errors['status'] = 'ko';
			echo json_encode($errors);
		} else {
			echo json_encode(array('status'=>'ok'));
		}
	}

	private function generateTwigView()
	{
		return "<p>Hello {{message}}</p>";
	}

	private function generatePhpView($controllerFQCN)
	{
		return '<?php /* @var $this '.$controllerFQCN.' */ ?>
This is your PHP view. You can access the controller protected and public variables / functions using the $this object';
	}

	/**
	 *
	 * @param string $directory
	 * @return bool
	 */
	private function createDirectory($directory)
	{
		if (!file_exists($directory)) {
			// Let's create the directory:
            $old = umask(0);
			$result = @mkdir($directory, 0775, true);
			umask($old);
			return $result;
		}
		return true;
	}
}
?>
