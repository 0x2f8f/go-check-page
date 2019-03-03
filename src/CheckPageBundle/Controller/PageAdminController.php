<?php

namespace CheckPageBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use CheckPageBundle\Entity\Page;

class PageAdminController extends CRUDController
{
    /**
     * Обновление эталонного хеша у страницы
     *
     * @return RedirectResponse
     */
    public function updatehashAction()
    {
        $request = $this->getRequest();
        $id = $request->get($this->admin->getIdParameter());

        /** @var Page $object */
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id : %s', $id));
        }
/*
        //удаляем старую html'ку
        $this->removeHtmlHash($object->getHashMust());
        $this->removeHtmlHash($object->getHashMust().'_diff');
*/
        //запоминаем новое состояние
        $em = $this->get('doctrine.orm.entity_manager');
        $object->setHashMust($object->getHashLast());
        $em->persist($object);
        $em->flush();

        $this->admin->setSubject($object);
        $this->addFlash('sonata_flash_success', 'Новое состояние страницы сохранено');

        return new RedirectResponse($request->headers->get('referer'));
    }

    private function removeHtmlHash($hash)
    {
        $file = $this->getHtmlDir().$hash.'.html';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Путь до папки с html'ками
     *
     * @return string
     */
    private function getHtmlDir()
    {
        return $this->get('kernel')->getRootDir() . '/../web/html/';
    }
}