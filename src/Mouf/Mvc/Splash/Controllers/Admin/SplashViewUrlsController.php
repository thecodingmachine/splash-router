<?php 
namespace Mouf\Mvc\Splash\Controllers\Admin;

use Mouf\Mvc\Splash\Services\SplashUrlManager;

use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * The controller that will display all the URLs managed by Splash.
 *
 * @Component
 */
class SplashViewUrlsController extends Controller {

	/**
	 * The template used by the Splash page.
	 *
	 * @Property
	 * @Compulsory
	 * @var TemplateInterface
	 */
	public $template;
	
	/**
	 *
	 * @var HtmlBlock
	 */
	public $content;
	
	protected $splashUrlsList;
	protected $selfedit;
	
	/**
	 * Displays the config page. 
	 *
	 * @Action
	 */
	public function defaultAction($selfedit = "false") {
		$this->selfedit = $selfedit;
		$this->splashUrlsList = SplashUrlManager::getUrlsList($selfedit == "true");
		
		$this->content->addFile(dirname(__FILE__)."/../../../../../views/admin/splashUrlsList.php", $this);
		$this->template->toHtml();
	}
	
}

?>