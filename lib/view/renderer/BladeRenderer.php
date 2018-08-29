<?php
namespace CsrDelft\view\renderer;
use CsrDelft\model\security\LoginModel;
use CsrDelft\Orm\DependencyManager;
use eftec\bladeone\BladeOne;

/**
 * @author G.J.W. Oolbekkink <g.j.w.oolbekkink@gmail.com>
 * @since 24/08/2018
 */
class BladeRenderer implements Renderer {
	private $bladeOne;
	private $data;
	private $template;

	public function __construct($template, $variables = []) {
		$this->bladeOne = new BladeOne(TEMPLATE_PATH, BLADE_CACHE_PATH, BladeOne::MODE_AUTO);
		$this->data = $variables;

		// Tijden compilen doet dit er niet toe.
		if (MODE !== 'TRAVIS') {
			$this->bladeOne->setInjectResolver(function ($className) {
				if (is_a($className, DependencyManager::class, true)) {
					/** @var $className DependencyManager */
					return $className::instance();
				} else {
					return new $className();
				}
			});

			// @auth en @guest maken puur onderscheid tussen ingelogd of niet.
			if (LoginModel::mag('P_LOGGED_IN')) {
				$this->bladeOne->setAuth(LoginModel::getUid());
			}
			$this->bladeOne->authCallBack = [LoginModel::class, 'mag'];
		}

		$this->bladeOne->directive('icon', function ($expr) {
			$options = trim($expr, "()");

			return "<?php echo call_user_func_array([\"CsrDelft\Icon\", \"getTag\"], [$options]); ?>";
		});

		$this->bladeOne->directive('cycle', function ($expr) {
			$numOptions = count(explode(',', $expr));
			$options = trim($expr, "()");
			$varName = uniqid('i_');

			// Create the variable if it does not exist.
			return "<?php \$this->$varName = @\$this->$varName; echo [$options][(\$this->$varName++) % $numOptions]; ?>";
		});
		$this->template = $template;
	}

	public function assign($field, $value) {
		$this->data[$field] = $value;
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function render() {
		return $this->bladeOne->run($this->template, $this->data);
	}

	/**
	 * @throws \Exception
	 */
	public function display() {
		echo $this->render();
	}

	/**
	 * @throws \Exception
	 */
	public function compile() {
		$this->bladeOne->compile($this->template, true);
	}
}