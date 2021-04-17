<?php

namespace App\Controller;

use Exception;
use App\Document\Tag;
use App\Document\Friend;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\SerializerInterface as Serializer;

/**
 * @Route("/friends")
 */
class FriendController extends AbstractController
{
    /**
     * @Route("/", name="friends" ,methods={"GET"})
     */
    public function getFriends(Request $request, Serializer $serializer, DocumentManager $dm): Response
    {
        $filters = $this->getFilteringOptions($request);

        $result = $dm->getRepository(Friend::class)->findAll();

        $tag = null;
        if ($request->query->get('tag')) {
            $tag = $dm->getRepository(Tag::class)->findOneBy(['label' => $request->query->get('tag')]);

            if (!$tag) {
                $tags = $dm->getRepository(Tag::class)->findAll();

                return new Response('The following tag ' .  $request->query->get('tag') . ' does not exist. It is only possible to filter using existing tags. 
                    Please use one of the following tags: ' . $serializer->serialize($tags, 'json', ['groups' => ['list']]));
            }
        }

        if ($filters) {
            $result = $dm->getRepository(Friend::class)->getFilteredFriends($filters, $tag);
        }

        return new Response($serializer->serialize($result, 'json', ['groups' => ['list']]));
    }

    /**
     * @Route("/create", name="create_friend" ,methods={"POST"})
     */
    public function createFriend(Request $request,  DocumentManager $dm): Response
    {
        $data = json_decode($request->getContent());

        try {
            $friend = new Friend();

            if ($data->friendship < 0 || $data->friendship > 100) {
                return new Response('Error: The friendship level must be between 0 and 100. The given value was: ' . $data->friendship, 500);
            }

            if (!in_array($data->type, Friend::ALLOWED_TYPES)) {
                return new Response('Error: The type is incorrect. The type must be one of the following: ' . json_encode(Friend::ALLOWED_TYPES), 500);
            }

            $friend->setName($data->name);
            $friend->setType($data->type);
            $friend->setFriendship($data->friendship);
            foreach ($data->tags as $t) {
                $tag = new Tag();
                $tag->setLabel($t->label);
                $dm->persist($tag);

                $friend->addTag($tag);
            }

            $dm->persist($friend);
            $dm->flush();
        } catch (Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }

        return new Response('Friend created!');
    }

    /**
     * @Route("/update/{id}", name="update_friend" ,methods={"PUT"})
     */
    public function updateFriend(Request $request, DocumentManager $dm, string $id): Response
    {
        $friend = $dm->getRepository(Friend::class)->findOneBy(['id' => $id]);

        $data = json_decode($request->getContent());
        foreach ($data as $k => $v) {
            if ($k === 'friendship') {
                if (Friend::TYPE_GOD !== $friend->getType()) {
                    $friend->setFriendship($v);
                } else {
                    return new Response('Error: Cannot modify the friendship level of a Friend of type God');
                }
            }
        }

        $dm->persist($friend);
        $dm->flush();

        return new Response('Friend updated!');
    }

    /**
     * @Route("/delete/{id}", name="delete_friend" ,methods={"DELETE"})
     */
    public function deleteFriend(DocumentManager $dm, string $id): JsonResponse
    {
        $friend = $dm->getRepository(Friend::class)->findOneBy(['id' => $id]);
        if ($friend) {
            $dm->remove($friend);
        }

        $dm->flush();

        return new JsonResponse('Friend deleted!');
    }

    /**
     * @Route("/eat/{friendId}", name="eat_friend", methods={"PUT"}, defaults={"friendId"=null})
     */
    public function eatFriend(?string $friendId, DocumentManager $dm)
    {
        $friend = $dm->getRepository(Friend::class)->findOneBy(['id' => $friendId, 'active' => true]);

        if (!$friendId) {
            $friend = $dm->createAggregationBuilder(Friend::class);
            $friend->sample(1);
            $friend = $friend->hydrate(Friend::class)->execute()->current();
        }

        if (Friend::TYPE_GOD === $friend->getType()) {
            return new Response("You cannot eat a God, mortal.");
        }

        if (Friend::TYPE_UNICORN === $friend->getType()) {
            return new Response("Unicorn blood doesn't even taste that good.");
        }

        $friend->setActive(false);
        $dm->persist($friend);
        $dm->flush();

        return new Response("Oh no! This friend has just been eaten: " . $friend->getName());
    }

    /**
     * @Route("/eaten-friends/{friendId}", name="list_eaten_friends", methods={"GET"}, defaults={"friendId"=null})
     */
    public function listEatenFriend(?string $friendId, DocumentManager $dm, Serializer $serializer)
    {
        $response = $dm->getRepository(Friend::class)->findBy(['active' => false]);

        if ($friendId) {
            $response = $dm->getRepository(Friend::class)->findOneBy(['id' => $friendId, 'active' => false]);
            if (!$response) {
                return new Response('Good news! The friend you\'re looking for is still alive!');
            }
        }

        return new Response($serializer->serialize($response, 'json', ['groups' => ['list']]));
    }

    protected function getFilteringOptions(Request $request)
    {
        $options = [];

        if ($request->query->has('name')) {
            $options['name'] = $request->query->get('name');
        }

        if ($request->query->has('type')) {
            $options['type'] = $request->query->get('type');
        }

        if ($request->query->has('friendship')) {
            $options['friendship'] = $request->query->get('friendship');
        }

        return $options;
    }
}
