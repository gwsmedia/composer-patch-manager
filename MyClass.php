<?php

namespace MyVendor;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class MyClass
{
    public static function postUpdate(Event $event)
    {
        $composer = $event->getComposer();
		//file_put_contents('Event.php', var_export($event, true));
    }

    public static function postPackageInstall(PackageEvent $event)
	{
		/** @var InstallOperation|UpdateOperation $operation */
		$operation = $event->getOperation();

		$package = method_exists($operation, 'getPackage')
			? $operation->getPackage()
			: $operation->getInitialPackage();

		var_dump($package->getName());
		//file_put_contents('PackageEvent.php', var_export($event, true));
	}
}
