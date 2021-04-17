<?php

namespace App\Document;

use App\Document\Tag;
use App\Repository\FriendRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass=FriendRepository::class)
 */
class Friend
{
    const TYPE_UNICORN = "UNICORN";
    const TYPE_GOD  = "GOD";
    const TYPE_HOOMAN = "HOOMAN";
    const TYPE_NOOB = "NOOB";

    const ALLOWED_TYPES = [
        self::TYPE_UNICORN,
        self::TYPE_GOD,
        self::TYPE_HOOMAN,
        self::TYPE_NOOB
    ];

    /**
     * @MongoDB\Id
     * @Groups({"list"})
     */
    protected $id;

    /**
     * @MongoDB\Field(type="integer")
     * @Groups({"list"})
     */
    protected $active = 1;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"list"})
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"list"})
     */
    protected $type;

    /** 
     * @MongoDB\Field(type="integer")
     * @Groups({"list"})
     */
    protected $friendship;

    /**
     * @MongoDB\ReferenceMany(targetDocument=Tag::class)
     * @Groups({"list"})
     */
    protected $tags;

    public function __construct()
    {
        $this->tags = [];
    }
    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set the value of active
     *
     * @return  self
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of friendship
     */
    public function getFriendship()
    {
        return $this->friendship;
    }

    /**
     * Set the value of friendship
     *
     * @return  self
     */
    public function setFriendship($friendship)
    {
        $this->friendship = $friendship;

        return $this;
    }


    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;;

        return $this;
    }

    public function removeTag(Tag $tag)
    {
        if ($tag->getFriend() === $this) {
            $tag->setFriend(null);
        }

        return $this;
    }

    public function getTags()
    {
        return $this->tags;
    }
}
