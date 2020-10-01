<?php
namespace App\Listener;

use Vich\UploaderBundle\Event\Event;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use App\Entity\Property;

class ImageUploadListener
{
    /**
     * @var cacheManager
     */
    private $cacheManager;

    /**
     * @var uploaderHelper
     */
    private $uploaderHelper;

    public function __construct(CacheManager $cacheManager, UploaderHelper $uploaderHelper)
    {
        $this->cacheManager = $cacheManager;
        $this->uploaderHelper = $uploaderHelper;
    }

    public function onVichUploaderPreRemove(Event $event)
    {
        $entity = $event->getObject();

        if(!$entity instanceof Property) {
            return;
        }

        $this->cacheManager->remove($this->uploaderHelper->asset($entity, 'imageFile'));
    }
}
