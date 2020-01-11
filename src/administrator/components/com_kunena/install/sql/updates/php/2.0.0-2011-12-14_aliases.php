<?php
/**
 * Kunena Component
 *
 * @package        Kunena.Installer
 *
 * @copyright      Copyright (C) 2008 - 2020 Kunena Team. All rights reserved.
 * @license        https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link           https://www.kunena.org
 **/

namespace Kunena\Forum\Administrator\Updates\Php;

defined('_JEXEC') or die();

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;
use Kunena\Forum\Libraries\Forum\Category\Helper;
use Kunena\Forum\Libraries\Factory\KunenaFactory;
use Kunena\Forum\Libraries\Route\KunenaRoute;
use Kunena\Forum\Libraries\Route\Legacy;
use function defined;

// Kunena 2.0.0: Create category aliases (all that K1.7 accepts)
/**
 * @param   string  $parent parent
 *
 * @return  array
 *
 * @since   Kunena 6.0
 *
 * @throws  Exception
 */
function kunena_200_2011_12_14_aliases($parent)
{
	$config = KunenaFactory::getConfig();

	// Create views
	foreach (KunenaRoute::$views as $view => $dummy)
	{
		kCreateAlias('view', $view, $view, 1);
	}

	// Create layouts
	foreach (KunenaRoute::$layouts as $layout => $dummy)
	{
		kCreateAlias('layout', "category.{$layout}", "category/{$layout}", 1);
		kCreateAlias('layout', "category.{$layout}", $layout, 0);
	}

	// Create legacy functions
	foreach (Legacy::$functions as $func => $dummy)
	{
		kCreateAlias('legacy', $func, $func, 1);
	}

	$categories = Helper::getCategories(false, false, 'none');
	$aliasLit   = $aliasUtf = [];

	// Create SEF: id
	foreach ($categories as $category)
	{
		kCreateCategoryAlias($category, $category->id);

		// Create SEF names
		$aliasUtf[$category->id] = kStringURLSafe($category->name);
		$aliasLit[$category->id] = OutputFilter::stringURLSafe($category->name);
	}

	// Sort aliases by category id (oldest ID accepts also sefcat format..
	ksort($categories);

	// Create SEF: id-name and id-Name (UTF8)
	foreach ($categories as $id => $category)
	{
		$created = false;

		if ($config->get('sefutf8'))
		{
			$name = $aliasUtf[$category->id];

			if (!empty($name))
			{
				$created = kCreateCategoryAlias($category, "{$id}-{$name}", 1);
			}
		}

		$name = $aliasLit[$category->id];

		if (!empty($name))
		{
			kCreateCategoryAlias($category, "{$id}-{$name}", !$created);
		}
	}

	// Create SEF: name and Name (UTF8)
	if ($config->get('sefcats'))
	{
		foreach ($categories as $category)
		{
			$created = false;

			if ($config->get('sefutf8'))
			{
				$name = $aliasUtf[$category->id];
				$keys = array_keys($aliasUtf, $name);

				if (!empty($name))
				{
					$created = kCreateCategoryAlias($category, $name, count($keys) == 1);
				}
			}

			$name = $aliasLit[$category->id];
			$keys = array_keys($aliasLit, $name);

			if (!empty($name))
			{
				kCreateCategoryAlias($category, $name, !$created && count($keys) == 1);
			}
		}
	}

	return ['action' => '', 'name' => Text::_('COM_KUNENA_INSTALL_200_ALIASES'), 'success' => true];
}

/**
 * @param        $type
 * @param        $item
 * @param        $alias
 * @param   int  $state  state
 *
 * @return  boolean
 * @since   Kunena 6.0
 */
function kCreateAlias($type, $item, $alias, $state = 0)
{
	$state = (int) $state;
	$db    = Factory::getDbo();
	$query = "INSERT IGNORE INTO `#__kunena_aliases` (alias, type, item, state) VALUES ({$db->quote($alias)},{$db->quote($type)},{$db->quote($item)},{$db->quote($state)})";
	$db->setQuery($query);
	$success = $db->execute() && $db->getAffectedRows();

	if ($success && $state)
	{
		// There can be only one primary alias
		$query = "UPDATE `#__kunena_aliases` SET state=0 WHERE type={$db->quote($type)} AND item={$db->quote($item)} AND alias!={$db->quote($alias)} AND state=1";
		$db->setQuery($query);
		$db->execute();
	}

	return $success;
}

/**
 * @param        $category
 * @param        $alias
 * @param   int  $state  state
 *
 * @return  boolean
 * @since   Kunena 6.0
 */
function kCreateCategoryAlias($category, $alias, $state = 0)
{
	$state = (int) $state;
	$db    = Factory::getDbo();
	$query = "INSERT IGNORE INTO `#__kunena_aliases` (alias, type, item) VALUES ({$db->quote($alias)},'catid',{$db->quote($category->id)})";
	$db->setQuery($query);
	$success = $db->execute() && $db->getAffectedRows();

	if ($success && $state)
	{
		// Update primary alias into category table
		$query = "UPDATE `#__kunena_categories` SET alias={$db->quote($alias)} WHERE id={$db->quote($category->id)}";
		$db->setQuery($query);
		$db->execute();
	}

	return $success;
}

/**
 * @param $str
 *
 * @return  string
 * @since   Kunena 6.0
 */
function kStringURLSafe($str)
{
	return StringHelper::trim(preg_replace(['/(\s|\xE3\x80\x80)+/u', '/[\$\&\+\,\/\:\;\=\?\@\'\"\<\>\#\%\{\}\|\\\^\~\[\]\`\.\(\)\*\!]/u'], ['-', ''], $str));
}
