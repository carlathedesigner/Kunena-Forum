<?php
/**
 * Kunena Plugin
 *
 * @package         Kunena.Plugins
 * @subpackage      Comprofiler
 *
 * @copyright       Copyright (C) 2008 - 2020 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Plugin\Kunena\Comprofiler;

defined('_JEXEC') or die();

use Exception;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use function defined;

/**
 * Class KunenaIntegrationComprofiler
 *
 * @since   Kunena 6.0
 */
class KunenaIntegrationComprofiler
{
	/**
	 * @var     boolean
	 * @since   Kunena 6.0
	 */
	protected static $open = false;

	/**
	 * @return  void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public static function open()
	{
		if (self::$open)
		{
			return;
		}

		self::$open = true;
		$params     = [];
		self::trigger('onStart', $params);
	}

	/**
	 * Triggers CB events
	 *
	 * Current events: profileIntegration=0/1, avatarIntegration=0/1
	 *
	 * @param   string  $event  event
	 * @param   object  $params params
	 *
	 * @return  void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public static function trigger($event, &$params)
	{
		global $_PLUGINS;
		$config            = KunenaFactory::getConfig();
		$params ['config'] = $config;
		$_PLUGINS->loadPluginGroup('user');
		$_PLUGINS->trigger('kunenaIntegration', [$event, &$config, &$params]);
	}

	/**
	 * @return  void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  Exception
	 */
	public static function close()
	{
		if (!self::$open)
		{
			return;
		}

		self::$open = false;
		$params     = [];
		self::trigger('onEnd', $params);
	}
}
