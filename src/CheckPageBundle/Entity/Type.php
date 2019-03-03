<?php

namespace CheckPageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Type
 *
 * @ORM\Table(name="types")
 * @ORM\Entity(repositoryClass="CheckPageBundle\Repository\TypeRepository")
 */
class Type
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_recipient", type="string", length=255, nullable=true)
     */
    private $mailRecipient;

    public function __toString()
    {
        return $this->getTitle() ?: 'Новый тип';
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     * @return Page
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getMailRecipient()
    {
        return $this->mailRecipient;
    }

    /**
     * @param string $mailRecipient
     * @return Type
     */
    public function setMailRecipient($mailRecipient)
    {
        $this->mailRecipient = $mailRecipient;
        return $this;
    }
}