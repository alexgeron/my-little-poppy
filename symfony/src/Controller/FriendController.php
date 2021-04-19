<?php

namespace App\Controller;

use Exception;
use App\Document\Tag;
use App\Document\Friend;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
                $response = $this->createResponse(500, 'The following tag ' .  $request->query->get('tag') . ' does not exist. It is only possible to filter using existing tags. Please use one of the following tags:', $serializer->serialize($tags, 'json', ['groups' => ['list']]));
                return new Response($response);
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
    public function createFriend(Request $request,  DocumentManager $dm, Serializer $serializer): Response
    {
        $data = json_decode($request->getContent());

        try {
            $friend = new Friend();

            if ($data->friendship < 0 || $data->friendship > 100) {
                $response = $this->createResponse(500, "The friendship level must be between 0 and 100. The given value was: " . $data->friendship);
                return new Response($response);
            }

            if (!in_array($data->type, Friend::ALLOWED_TYPES)) {
                $response = $this->createResponse(500, "Error: The type is incorrect. The type must be one of the following:", json_encode(Friend::ALLOWED_TYPES));
                return new Response($response);
            }

            $friend->setName($data->name);
            $friend->setType($data->type);
            $friend->setFriendship($data->friendship);
            foreach ($data->tags as $t) {
                $tag = $tag = $dm->getRepository(Tag::class)->findOneBy(['label' => $t->label]);
                if  (!$tag) {
                    $tag = new Tag();
                    $tag->setLabel($t->label);
                }
                $dm->persist($tag);

                $friend->addTag($tag);
            }

            $dm->persist($friend);
            $dm->flush();
        } catch (Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }

        $response = $this->createResponse(200, "Friend created!", $serializer->serialize($friend, 'json', ['groups' => ['list']]));

        return new Response($response);
    }

    /**
     * @Route("/update/{id}", name="update_friend" ,methods={"PUT"})
     */
    public function updateFriend(Request $request, DocumentManager $dm, string $id, Serializer $serializer): Response
    {
        $friend = $dm->getRepository(Friend::class)->findOneBy(['id' => $id]);

        $data = json_decode($request->getContent());
        foreach ($data as $k => $v) {
            if ($k === 'friendship') {
                if (Friend::TYPE_GOD !== $friend->getType()) {
                    $friend->setFriendship($v);
                } else {
                    $response = $this->createResponse(500, 'Cannot modify the friendship level of a Friend of type God');
                    return new Response(json_encode($response));
                }
            }
        }

        $dm->persist($friend);
        $dm->flush();

        $response = $this->createResponse(200, 'Friend updated',$serializer->serialize($friend, 'json', ['groups' => ['list']]));

        return new Response(json_encode($response));
    }

    /**
     * @Route("/delete/{id}", name="delete_friend" ,methods={"DELETE"})
     */
    public function deleteFriend(DocumentManager $dm, string $id): Response
    {
        $friend = $dm->getRepository(Friend::class)->findOneBy(['id' => $id]);
        if ($friend) {
            foreach ($friend->getTags() as $tag) {
                $dm->remove($tag);
            }

            $dm->remove($friend);
        }

        $dm->flush();

        $response = $this->createResponse(200, 'Friend deleted');

        return new Response(json_encode($response));
    }

    /**
     * @Route("/eat/{friendId}", name="eat_friend", methods={"PUT"}, defaults={"friendId"=null})
     */
    public function eatFriend(?string $friendId, DocumentManager $dm): Response
    {
        $friend = $friendId ? $dm->getRepository(Friend::class)->findOneBy(['id' => $friendId]) : $this->getRandomFriend(1 ,$dm);

        if(!$friend){
            return new Response(json_encode($this->createResponse(500, 'No friend to eat')));
        }

        if(!$friend->getActive()){
            return new Response(json_encode($this->createResponse(500, $friend->getNAme()." is already dead, RIP.")));
        }
        
        if (Friend::TYPE_GOD === $friend->getType()) {
            return new Response(json_encode($this->createResponse(500, "You cannot eat a God, mortal.")));
        }

        if (Friend::TYPE_UNICORN === $friend->getType()) {
            return new Response(json_encode($this->createResponse(500, "Unicorn blood doesn't even taste that good.")));
        }

        $friend->setActive(false);
        $dm->persist($friend);
        $dm->flush();

        $response = $this->createResponse(200, "Oh no! This friend has just been eaten: " . $friend->getName());

        return new Response(json_encode($response));
    }

    /**
     * @Route("/eaten-friends/{friendId}", name="list_eaten_friends", methods={"GET"}, defaults={"friendId"=null})
     */
    public function listEatenFriend(?string $friendId, DocumentManager $dm, Serializer $serializer): Response
    {
        $response = $dm->getRepository(Friend::class)->findBy(['active' => false]);

        if (!$response) {
            return new Response(json_encode($this->createResponse(500, "Good news! every friends are alive.")));
        }

        if ($friendId) {
            $response = $dm->getRepository(Friend::class)->findOneBy(['id' => $friendId, 'active' => false]);
            if (!$response) {
                return new Response(json_encode($this->createResponse(500, "Good news! The friend you're looking for is still alive!")));
            }
        }

        return new Response($serializer->serialize($response, 'json', ['groups' => ['list']]));
    }

    /**
     * @Route("/rez/{friendId}", name="rez_friend", methods={"PUT"}, defaults={"friendId"=null})
     */
    public function rezFriend(?string $friendId, DocumentManager $dm): Response
    {
        $friend = $friendId ? $dm->getRepository(Friend::class)->findOneBy(['id' => $friendId]) : $this->getRandomFriend(0 ,$dm);

        if(!$friend){
            return new Response(json_encode($this->createResponse(500, "No friend to rez")));
        }

        if($friend->getActive()){
            return new Response(json_encode($this->createResponse(500, "Good news, ".$friend->getNAme(). " is still alive.")));
        }
        
        if (Friend::TYPE_GOD === $friend->getType()) {
            return new Response(json_encode($this->createResponse(500, "A GOD never die, mortal.")));
        }

        if (Friend::TYPE_UNICORN === $friend->getType()) {
            return new Response(json_encode($this->createResponse(500, "The beautiful unicorn cannot die.")));
        }

        $friend->setActive(true);
        $dm->persist($friend);
        $dm->flush();

        $response = $this->createResponse(200, "It's a miracle, ". $friend->getName()." just go back from the hell.");

        return new Response(json_encode($response));
    }
     
    /**
     * @Route("/battle/{friendIdOne}/{friendIdTwo}", name="battle_friend", methods={"PUT"}, defaults={"friendIdOne"=null,"friendIdTwo"=null})
     */
    public function battle(?string $friendIdOne, ?string $friendIdTwo,  DocumentManager $dm): Response
    {
        $friendOne = $friendIdOne ? $dm->getRepository(Friend::class)->findOneBy(['id' => $friendIdOne]) : $this->getRandomFriend(1 ,$dm);
        $friendTwo = $friendIdTwo ? $dm->getRepository(Friend::class)->findOneBy(['id' => $friendIdTwo]) : $this->getRandomFriend(1 ,$dm);

        if(!$friendOne || !$friendTwo){
            return new Response(json_encode($this->createResponse(500, "No enough alive friends")));
        }

        if($friendOne === $friendTwo){
            return new Response(json_encode($this->createResponse(500, "Please choose two differents friends")));
        }

        $friendshipOne = $friendOne->getFriendship();
        $friendshipTwo = $friendTwo->getFriendship();

        $looser = $friendshipOne > $friendshipTwo ? $friendTwo : $friendOne;
        $winner = $friendshipOne > $friendshipTwo ? $friendOne : $friendTwo;

        $looser->setActive(false);
        $dm->persist($looser);
        $dm->flush();

        $response = $this->createResponse(200, $winner->getName().' win the battle versus '.$looser->getName()."(".$winner->getFriendship()." vs ".$looser->getFriendship().")");

        return new Response(json_encode($response));
    }

    protected function getFilteringOptions(Request $request): array
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

    protected function getRandomFriend ($status, DocumentManager $dm): Friend
    {
        $friend = $dm->createAggregationBuilder(Friend::class);
        $friend->match()->field('active')->equals($status);
        $friend->sample(1);
        $friend = $friend->hydrate(Friend::class)->execute()->current();

        return $friend;

    }

    protected function createResponse(Int $status, String $message, String $data = null): string
    {
        $response = [
            'status' => $status,
            'message' => $message
        ];
        if (null !== $data) {
            $response['data'] = $data;
        }

        return json_encode($response);
    }
}
