<?php

namespace CheckPageBundle\Command\Base;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class EntityManagerAwareCommand extends ContainerAwareCommand
{
    /**
     * @return Registry
     */
    private function getDoctrine()
    {
        return $doctrine = $this->getContainer()->get('doctrine');
    }

    /**
     * EntityManager для работы с основной трейдоввской базой
     *
     * @return \Doctrine\Common\Persistence\ObjectManager|object
     */
    protected function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }
}