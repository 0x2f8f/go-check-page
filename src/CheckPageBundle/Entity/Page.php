<?php

namespace CheckPageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Page
 *
 * @ORM\Table(name="pages")
 * @ORM\Entity(repositoryClass="CheckPageBundle\Repository\PageRepository")
 */
class Page
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
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="selector", type="string", length=255, nullable=true)
     */
    private $selector;

    /**
     * @var string
     *
     * @ORM\Column(name="hash_must", type="string", length=255, nullable=true)
     */
    private $hashMust;

    /**
     * @var string
     *
     * @ORM\Column(name="hash_last", type="string", length=255, nullable=true)
     */
    private $hashLast;

    /**
     * @ORM\ManyToOne(targetEntity="Type", inversedBy="pages")
     * @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     */
    protected $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="prerender", type="boolean", nullable=true)
     */
    private $prerender;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disable", type="boolean", nullable=true)
     */
    private $disable;

    public function __toString()
    {
        return $this->getTitle() ? : 'Новый адрес';
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
     * @param string $url
     * @return Page
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $value
     * @return Page
     */
    public function setSelector($value)
    {
        $this->selector = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getSelector()
    {
        return $this->selector;
    }

    /**
     * @param string $value
     * @return Page
     */
    public function setHashMust($value)
    {
        $this->hashMust = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getHashMust()
    {
        return $this->hashMust;
    }

    /**
     * @param string $value
     * @return Page
     */
    public function setHashLast($value)
    {
        $this->hashLast = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getHashLast()
    {
        return $this->hashLast;
    }

    /**
     * Set type
     *
     * @param CheckPageBundle\Entity\Type $type
     * @return Page
     */
    public function setType(Type $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return CheckPageBundle\Entity\Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isPrerender()
    {
        return $this->prerender;
    }

    /**
     * @param bool $prerender
     * @return Page
     */
    public function setPrerender(bool $prerender)
    {
        $this->prerender = $prerender;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDisable()
    {
        return $this->disable;
    }

    /**
     * @param bool $disable
     * @return Page
     */
    public function setDisable(bool $disable)
    {
        $this->disable = $disable;
        return $this;
    }

    /**
     * Массив для вывода в табличном виде в консоли
     *
     * @return array
     */
    public function toConsoleTable()
    {
        return [
            $this->getId(),
            $this->getTitle(),
            $this->getUrl()
        ];
    }
}