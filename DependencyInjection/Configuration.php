<?php
declare(strict_types=1);

/*
 * This file is part of the Stinger Entity Search package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\DoctrineEntitySearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface {

	/**
	 *
	 * {@inheritDoc}
	 *
	 */
	public function getConfigTreeBuilder(): TreeBuilder {
		$configName = 'stinger_soft_doctrine_entity_search';
		if(Kernel::VERSION_ID < 40300) {
			$treeBuilder = new TreeBuilder();
			// $rootNode = $treeBuilder->root($configName);
		} else {
			$treeBuilder = new TreeBuilder($configName);
			// $rootNode = $treeBuilder->getRootNode();
		}	
		// @formatter:off
		// @formatter:on

		return $treeBuilder;
	}
}
