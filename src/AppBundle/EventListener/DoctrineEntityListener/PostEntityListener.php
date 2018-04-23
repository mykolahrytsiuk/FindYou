<?php

namespace AppBundle\EventListener\DoctrineEntityListener;

use AppBundle\Entity\Photo;
use AppBundle\Entity\Post;
use AppBundle\Entity\User;
use AppBundle\Exception\PostLimitException;
use AppBundle\Repository\PostRepository;
use AppBundle\Service\Google\GoogleGeocodeService;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class PostEntityListener
{
    protected $requestStack;

    protected $googleGeocodeService;

    protected $tokenStorage;

    public function __construct(RequestStack $requestStack, GoogleGeocodeService $googleGeocodeService, TokenStorage $tokenStorage)
    {
        $this->requestStack = $requestStack;

        $this->googleGeocodeService = $googleGeocodeService;

        $this->tokenStorage = $tokenStorage;
    }

    /**
     * CheckPostLimit.
     *
     * @ORM\PrePersist()
     *
     * @param LifecycleEventArgs $args
     */
    public function checkPostLimit(Post $entity, LifecycleEventArgs $args)
    {
        $postLimit = 15;

        $em = $args->getEntityManager();

        $user = $this->tokenStorage->getToken()->getUser();

        if ($user instanceof User) {
            /**
             * @var PostRepository $repository
             */
            $repository = $em->getRepository('AppBundle:Post');

            $createdTodayProductCount = $repository->getCreatedTodayProductCount($user);

            if ($createdTodayProductCount >= $postLimit) {

                throw new PostLimitException;
            }

        }
    }

    /**
     * Set photo.
     *
     * @ORM\PrePersist()
     *
     * @param LifecycleEventArgs $args
     */
    public function setPhoto(Post $entity, LifecycleEventArgs $args)
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request instanceof  Request) {
            $em = $args->getEntityManager();

            $repository = $em->getRepository('AppBundle:Photo');

            $photoIds = $request->get('photos');

            foreach ($photoIds as $photoId) {
                $photo = $repository->findOneById($photoId);

                if ($photo instanceof Photo) {
                    $entity->addPhoto($photo);
                    $photo->setPost($entity);
                    $photo->setIsUsed(1);

                    $em->persist($photo);
                }
            }
        }
    }

    /**
     * Set place.
     *
     * @ORM\PrePersist()
     *
     * @param LifecycleEventArgs $args
     */
    public function setPlace(Post $entity, LifecycleEventArgs $args)
    {
        $places = null;

        $placeId = $entity->getPlaceId();

        $coords = $this->requestStack->getCurrentRequest()->request->get('coords');

        if ($placeId) {
            $places = $this->googleGeocodeService->getPlaceById($placeId, $args->getEntityManager());
        }
        elseif ($coords) {
            $places = $this->googleGeocodeService->getPlaceByCoords($coords, $args->getEntityManager());
        }

        if ($places) {
            foreach ($places as $key => $place) {
                $addressComponents = $place[0]['address_components'];
                $places[$key] = $addressComponents[1]['long_name'].', '.
                    $addressComponents[2]['long_name'].', '.
                    $addressComponents[3]['long_name'].', '.
                    $addressComponents[4]['long_name'];
            }

            $entity->setPlace($places);
        }
    }
}
