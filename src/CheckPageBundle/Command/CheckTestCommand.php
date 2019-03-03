<?php

namespace CheckPageBundle\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use CheckPageBundle\Command\Base\EntityManagerAwareCommand;
use CheckPageBundle\Entity\Page;
use \GuzzleHttp\Client as Client;
use CheckPageBundle\Service\MailService;

class CheckTestCommand extends  EntityManagerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('check-page:check:test')
            ->setDescription('Проверка всех страниц на изменение')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = "yandex.ru";

        $selector = '.comparateur-slice';

        //1. Получаем список всех страниц
        $page = new Page();
        $page
            ->setTitle('TEST')
            ->setUrl($url)
            ->setSelector($selector);

        $pages = [$page];
        $pagesChanged = [];

        $this->output = $output;

        foreach ($pages as $page) {

            //2. Запрашиваем страницу страницу
            $html = $this->getHtmlPage($page);

            //3. Ищем кусок страницы по селектору
            $htmlPart = $page->getSelector() ? $this->findBySelector($html, $page->getSelector()) : $html;
            dump($htmlPart); die();

            //4. Сравниваем хеши
            $hashOld = $page->getHashMust();
            $hashNew = md5($htmlPart);

            //5. Если хеш изменился - запоминаем новый хеш и отправляем письмо на support
            if ($hashOld != $hashNew) {
                $pagesChanged[]=$page->toConsoleTable();
//                $this->updatePageHash($page, $hashNew); //запоминаем хеш
//                $this->sendMailToSupport($page); //отправляем письмо
            }
        }
        $output->writeln('');

        //если что то изменилось, выводим список
        if (count($pagesChanged)) {
            $output->writeln("Изменилось страниц: ".count($pagesChanged));
            $table = new Table($output);
            $table
                ->setHeaders(array('Id', 'Title', 'Url'))
                ->setRows($pagesChanged)
                ->render();
        }
    }

    /**
     * Список страниц на проверку
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

    /**
     * Получаем html страницы
     *
     * @param Page $page
     * @return string
     */
    private function getHtmlPage(Page $page)
    {
        $client = new Client([
            'base_uri' => $page->getUrl(),
            'headers' => [
'User-Agent' =>  'check-page'
//                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3'
            ]
        ]);
        try {
            $response = $client->request('GET', '');
            $html = $response->getBody()->getContents();
            return $html;
        } catch (\Exception $e) {
            $this->output->writeln('<error>ERROR</error> Не удалось получить страницу '.$page->getUrl());
        }
    }

    /**
     * Ищем по селектору html-кусок в переданной html-страничке
     *
     * @param $html
     * @param $selector
     * @return null
     */
    private function findBySelector($html, $selector)
    {
        $crawler = new Crawler($html);
        $filter = $crawler->filter($selector);
        if (iterator_count($filter)) {
            $descr = $filter->html();
            return $descr;
        }
        return null;
    }

    /**
     * Запоминаем новый хеш
     *
     * @param Page $page
     * @param $hashNew
     */
    private function updatePageHash(Page $page, $hashNew)
    {
        $em = $this->getEntityManager();
        $page->setHashLast($hashNew);

        $em->persist($page);
        $em->flush();
    }

    /**
     * Отправляем письмо на саппорт
     *
     * @param Page $page
     * @return int
     */
    private function sendMailToSupport(Page $page)
    {
        /** @var MailService $mailService */
        $mailService = $this->getContainer()->get('mail.service');
        $to = $mailService->getSupportEmail();
        $title = 'Изменилось содержимое страницы "'.$page->getTitle().'"';
        $body = 'Изменилось содержимое страницы "'.$page->getTitle().'"<br>';
        $body.='Адрес страницы '.$page->getUrl();

        return $mailService->send($to, $title, $body);

    }
}
