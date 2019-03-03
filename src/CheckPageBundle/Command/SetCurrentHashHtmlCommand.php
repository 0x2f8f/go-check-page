<?php

namespace CheckPageBundle\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use CheckPageBundle\Command\Base\EntityManagerAwareCommand;
use CheckPageBundle\Entity\Page;
use \GuzzleHttp\Client as Client;
use CheckPageBundle\Service\MailService;

class SetCurrentHashHtmlCommand extends  EntityManagerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('check-page:set-current-hash-html')
            ->setDescription('Создаем html-ки по текущим хешам')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $pages = $this->getPages();
        $progress = new ProgressBar($output, count($pages));
        foreach ($pages as $page) {
            $progress->advance();

            //2. Запрашиваем страницу страницу
            $html = $this->getHtmlPage($page);

            //3. Ищем кусок страницы по селектору
            $htmlPart = $page->getSelector() ? $this->findBySelector($html, $page->getSelector()) : $html;

            //4. берем хеш
            $hash = md5($htmlPart);

            //5. Сохраняем html-ку на диск
            $this->saveHtmlHash($htmlPart, $hash);
        }
        $output->writeln('');
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
                'User-Agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3'
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
     * Путь до папки с html'ками
     *
     * @return string
     */
    private function getHtmlDir()
    {
        return $this->getContainer()->get('kernel')->getRootDir() . '/../web/html/';
    }

    /**
     * Сохраняем новую html'ку
     *
     * @param $htmlPart
     * @param $hash
     */
    private function saveHtmlHash($htmlPart, $hash)
    {
        $file = $this->getHtmlFilePath($hash);
        file_put_contents($file, '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body>'.$htmlPart.'</body></html>');
    }

    /**
     * Путь на сервере до файла с html'кой
     *
     * @param $hash
     * @return string
     */
    private function getHtmlFilePath($hash)
    {
        return $this->getHtmlDir().$hash.'.html';
    }
}