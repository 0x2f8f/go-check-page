<?php

namespace CheckPageBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

class PageAdmin extends AbstractAdmin
{
    /**
     * Фильтры
     *
     * @param DatagridMapper $datagrid
     */
    protected function configureDatagridFilters(DatagridMapper $datagrid)
    {
        $datagrid
            ->add('title', null, [
                'label'         => 'Название',
                'show_filter'   => true,
            ])
            ->add('url', null, [
                'label'         => 'Ссылка',
                'show_filter'   => true,
            ])
            ->add('type', null, [
                'label'         => 'Тип',
                'show_filter'   => true,
            ])
            ->add('prerender', null, [
                'label'         => 'Через пререндер',
                'show_filter'   => false,
            ])
            ->add('disable', null, [
                'label'         => 'Отключен',
                'show_filter'   => false,
            ])
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('title', null, [
                'label' => 'Название'
            ])
            ->add('url', null, [
                'label' => 'Ссылка'
            ])
            ->add('selector', null, [
                'label' => 'Селектор'
            ])
            ->add('type', null, [
                'label' => 'Тип'
            ])
            ->add('disable', null, [
                'label' => 'Отключен'
            ])
            ->add('_action', null, array(
                'actions' => array(
                    'show' => array(),
                    'edit' => array(),
                    'delete' => array(),
                    'updatehash' => array(
                        'template' => 'CheckPageBundle:Admin:update_hash.html.twig'
                    )
                ),
            ))
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title', null, [
                'label'     => 'Название',
                'required'  => true
            ])
            ->add('url', null, [
                'label'     => 'Ссылка',
                'required'  => true
            ])
            ->add('selector', null, [
                'label'     => 'Селектор',
            ])
            ->add('type', null, [
                'label'         => 'Тип',
                'required'      => false,
            ])
            ->add('prerender', null, [
                'label'         => 'Через пререндер',
                'required'      => false,
            ])
            ->add('disable', null, [
                'label'         => 'Отключен',
                'required'      => false,
            ])
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('title', null, [
                'label' => 'Название'
            ])
            ->add('url', null, [
                'label' => 'Ссылка'
            ])
            ->add('selector', null, [
                'label' => 'Селектор'
            ])
            ->add('type', null, [
                'label' => 'Тип'
            ])
            ->add('prerender', null, [
                'label' => 'Через пререндер'
            ])
            ->add('disable', null, [
                'label' => 'Отключен'
            ])
            ->add('hashMust', null, [
                'label' => 'Hash - должно быть'
            ])
            ->add('hashLast', null, [
                'label' => 'Hash - с последней проверки'
            ])
        ;
    }

    /**
     * Кастомные роуты
     *
     * @param RouteCollection $collection
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->add('updatehash', $this->getRouterIdParameter() . '/updatehash');
    }
}
