<?php

/*
 * This file is part of the Stinger Doctrine Entity Search package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace StingerSoft\DoctrineEntitySearchBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use StingerSoft\EntitySearchBundle\StingerSoftEntitySearchBundle;

/**
 */
class StingerSoftDoctrineEntitySearchBundle extends Bundle {

	public static function getRequiredBundles(string $env, array &$requiredBundles = []): array {

		if(isset($requiredBundles['StingerSoftDoctrineEntitySearchBundle'])) {
			return $requiredBundles;
		}

		$requiredBundles['StingerSoftDoctrineEntitySearchBundle'] = '\StingerSoft\DoctrineEntitySearchBundle\StingerSoftDoctrineEntitySearchBundle';
		StingerSoftEntitySearchBundle::getRequiredBundles($env, $requiredBundles);
		return $requiredBundles;
	}
}