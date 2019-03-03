<?php

namespace CheckPageBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CheckPageBundle\Command\Base\EntityManagerAwareCommand;
use Symfony\Component\Console\Helper\Table;

class PagesListCommand extends  EntityManagerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('check-page:pages:list')
            ->setDescription('Список страниц на проверку')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $pages = $this->getPages();
        $rows = [];
        foreach ($pages as $page) {
            $rows[]=[
                $page->getId(),
                $page->getTitle(),
                $page->getUrl(),
            ];
        }
        $table = new Table($output);
        $table
            ->setHeaders(array('Id', 'Title', 'Url'))
            ->setRows($rows)
        ;
        $table->render();
    }

    /**
     * Список страниц
     *
     * @return mixed
     */
    private function getPages()
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('CheckPageBundle:Page');
        $pages = $repo->createQueryBuilder('p')
            ->getQuery()
            ->getResult();
        return $pages;
    }
}