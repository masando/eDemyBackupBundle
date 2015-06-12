<?php

namespace eDemy\BackupBundle\Controller;

use eDemy\MainBundle\Controller\BaseController;
use eDemy\MainBundle\Event\ContentEvent;

class BackupController extends BaseController
{
    public static function getSubscribedEvents()
    {
        return self::getSubscriptions('backup');
    }

    public function onFrontpage(ContentEvent $event)
    {
        $this->get('edemy.meta')->setTitlePrefix("Backup");
    }
}
