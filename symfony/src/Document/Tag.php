<?php

namespace App\Document;


use App\Document\Friend;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document
 */
class Tag
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"list"})
     */
    protected $label;

    /**
     * @MongoDB\ReferenceOne(targetDocument=Friend::class) 
     */
    protected $friend;

    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the value of label
     *
     * @return  self
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of friend
     */
    public function getFriend()
    {
        return $this->friend;
    }

    public function setFriend(?Friend $friend)
    {
        $this->friend = $friend;

        return $this;
    }
}
