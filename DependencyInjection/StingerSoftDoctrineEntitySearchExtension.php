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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * A Keen Metronic Admin Theme (http://www.keenthemes.com/) Bundle for Symfony2
 * with some additional libraries and PecPlatform specific customization.
 */
class StingerSoftDoctrineEntitySearchExtension extends Extension {

	/**
	 *
	 * {@inheritDoc}
	 *
	 */
	public function load(array $configs, ContainerBuilder $container): void {
		$configuration = new Configuration();
		$this->processConfiguration($configuration, $configs);

		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		$loader->load('services.yml');
	}
}
