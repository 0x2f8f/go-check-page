<?php

namespace CheckPageBundle\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use CheckPageBundle\Command\Base\EntityManagerAwareCommand;
use CheckPageBundle\Entity\Page;
use \GuzzleHttp\Client as Client;
use CheckPageBundle\Service\CheckPageService;
use CheckPageBundle\Service\MailService;
use DiffMatchPatch\DiffMatchPatch;

class CheckPagesCommand extends  EntityManagerAwareCommand
{

    private $errorPages = [];
    private $needSend = false;

    protected function configure()
    {
        $this
            ->setName('check-page:check:pages')
            ->setDescription('Проверка всех страниц на изменение')
            ->addOption('send', 's', InputOption::VALUE_NONE, 'Send report to e-mail')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'Id страницы')
            ->addOption('show-html', null, InputOption::VALUE_NONE, 'Show html')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $pageId = $input->getOption('id');
        $showHtml = (bool)$input->getOption('show-html');
        $this->needSend = $input->getOption('send');

        //1. Получаем список всех страниц
        $pages = $this->getPages($pageId);
        $pagesChanged = [];
        $progress = new ProgressBar($output, count($pages));
        $checkPageService = $this->getCheckPageService();

        foreach ($pages as $page) {
            /** @var Page $page */
            $progress->advance();

            //2. Запрашиваем страницу
            $html = $checkPageService->getHtmlPage($page->getUrl(), $page->isPrerender());
            if ($showHtml) {
                $output->writeln($html);
            }


            if ($html === null) {
                //пытаемся отправить запрос ещё раз
                if ($page->getType() && $page->getType()->getTitle() == 'Проверка кэша') {} else {
                    $html = $checkPageService->getHtmlPage($page->getUrl(), $page->isPrerender());
                }
            }

            if ($html === null) {
                if ($page->getType() && $page->getType()->getTitle() == 'Проверка кэша') {
                    //НИЧЕГОШЕНЬКИ НЕ ДЕЛАЕМ. ТАМ ПРОГРЕВ КЕША МЕРСА НА tr4 ИДЁТ
                } else {
                    $this->errorPages[] = $page;
                    $this->output->writeln('<error>ERROR</error> Не удалось получить страницу ' . $page->getUrl());
                }
            } else {

                //3. Ищем кусок страницы по селектору
                $htmlPart = $page->getSelector() ? $this->findBySelector($html, $page->getSelector()) : $html;

                //4. Сравниваем хеши
                $hashOld = $page->getHashMust();
                $hashNew = md5($htmlPart);

                if (!$hashNew) {
                    //отправляем письмо админу о том что хеш пустой. Что то пошло не так
                    $this->sendEmptyNewHashMailToAdmin($page);
                } else {
                    //5. Если хеш изменился - запоминаем новый хеш и отправляем письмо на support
                    if ($hashOld != $hashNew) {
                        $pagesChanged[] = $page->toConsoleTable();
                        $this->saveHtmlHash($htmlPart, $hashNew);   //сохряняем новую html'ку
                        $this->updatePageHash($page, $hashNew);     //запоминаем хеш
                        $this->saveDiffHtmlHash($page);             //сохраняем html'ку с выделенными изменениями
                        if ($this->needSend) {
                            $this->sendMailToSupport($page);        //отправляем письмо на саппорт
                            $this->sendMailToAdmin($page);          //отправляем письмо админу
                            $this->saveCurrentHash($page);          //после того как отправили письмо, можно запомнить новый хеш
                        }
                    }
                }
            }
        }
        $progress->finish();
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

        //отправляем письмо с битыми ссылками
        $this->sendMailErrorPages();
    }

    /**
     * Список страниц на проверку
     *
     * @param null $pageId
     * @return mixed
     */
    private function getPages($pageId = null)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('CheckPageBundle:Page');
        $queryBuilder = $repo->createQueryBuilder('p');

        $queryBuilder->where('p.disable is NULL or p.disable = 0');

        if ($pageId) {
            $queryBuilder
                ->andWhere('p.id = :page_id')
                ->setParameter('page_id', $pageId)
                ;
        }
        $pages = $queryBuilder
            ->getQuery()
            ->getResult();
        return $pages;
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
     * Содержимое письма об изменении страницы
     *
     * @param Page $page
     * @return string
     */
    private function getMailBody(Page $page)
    {
        $body = 'Изменилось содержимое страницы "'.$page->getTitle().'"<br>';
        $body.='Адрес страницы '.$page->getUrl().'<br>';

        $oldHash = $page->getHashMust();
        $newHash = $page->getHashLast();

        //Было
        $fileOld = $this->getHtmlFilePath($oldHash);
        $body.="Было: ".(file_exists($fileOld) ? $this->getHtmlFileLink($oldHash) : 'не указано').'<br>';


        //Стало
        $fileNew = $this->getHtmlFilePath($newHash);
        $body.="Стало: ".(file_exists($fileNew) ? $this->getHtmlFileLink($newHash) : 'не указано').'<br>';

        //что изменилось
        if (file_exists($fileNew)) {
            $hash = $newHash.'_diff';
            $fileDiff = $this->getHtmlFilePath($hash);
            if (file_exists($fileDiff)) {
                $body.="Что изменилось: ".$this->getHtmlFileLink($hash).'<br>';
            }
        }

        return $body;
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
        $ecipientMail = $page->getType() ? $page->getType()->getMailRecipient() : null;
        $to = $ecipientMail ? : $mailService->getSupportEmail();
        $title = 'Изменилось содержимое страницы "'.$page->getTitle().'"';
        $body = $this->getMailBody($page);

        return $mailService->send($to, $title, $body);
    }

    /**
     * Отправляем письмо админу
     *
     * @param Page $page
     * @return int
     */
    private function sendMailToAdmin(Page $page)
    {
        /** @var MailService $mailService */
        $mailService = $this->getContainer()->get('mail.service');
        $to = $mailService->getDevelEmail();
        $title = 'Изменилось содержимое страницы "'.$page->getTitle().'"';
        $body = $this->getMailBody($page);

        return $mailService->send($to, $title, $body);
    }

    /**
     * Отправляем письмо админу о том что хеш пустой. Что то пошло не так
     *
     * @param $page
     * @return int
     */
    private function sendEmptyNewHashMailToAdmin($page)
    {
        /** @var MailService $mailService */
        $mailService = $this->getContainer()->get('mail.service');
        $to = $mailService->getDevelEmail();
        $title = 'ОШИБКА. Пустое содержимое страницы "'.$page->getTitle().'"';
        $body = $title.' '.$page->getUrl();

        return $mailService->send($to, $title, $body);
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
     * @param bool|true $withHtmlTags
     */
    private function saveHtmlHash($htmlPart, $hash, $withHtmlTags = true)
    {
        $file = $this->getHtmlFilePath($hash);
        $content = $withHtmlTags ?
            '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body>'.$htmlPart.'</body></html>' :
            $htmlPart;
        file_put_contents($file, $content);
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

    /**
     * Ссылка на файл с html'кой
     *
     * @param $hash
     * @return string
     */
    private function getHtmlFileLink($hash)
    {
        return 'http://'.$this->getContainer()->getParameter('domain').'/html/'.$hash.'.html';
    }

    /**
     * Сохраняем отдельную html с выделенными кусками что изменилось
     *
     * @param Page $page
     */
    private function saveDiffHtmlHash(Page $page)
    {
        $oldHash = $page->getHashMust();
        $newHash = $page->getHashLast();

        $oldFile = $this->getHtmlFilePath($oldHash);
        $newFile = $this->getHtmlFilePath($newHash);

        $oldText = file_exists($oldFile) ? file_get_contents($oldFile) : '';
        $newText = file_exists($newFile) ? file_get_contents($newFile) : '';

        $dmp = new DiffMatchPatch();
        $diffs = $dmp->diff_main($oldText, $newText, false);

        $result = '';
        $colors = [
            DiffMatchPatch::DIFF_EQUAL  => "#FFF",
            DiffMatchPatch::DIFF_INSERT => "#a6f3a6",
            DiffMatchPatch::DIFF_DELETE => "#f8cbcb"

        ];
        foreach ($diffs as $item) {
            $text = $item[1];
            $type = $item[0];
            $color = $colors[$type];
            $result.='<span style="background-color: '.$color.';">'.htmlspecialchars($text).'</span>';
        }
        $result = "<div style='font-family: monospace;'>".$result."</div>";
        $this->saveHtmlHash($result, $newHash.'_diff');
    }

    /**
     * после того как отправили письмо, можно запомнить новый хеш
     *
     * @param Page $page
     */
    private function saveCurrentHash(Page $page)
    {
        $em = $this->getEntityManager();
        $page->setHashMust($page->getHashLast());
        $em->persist($page);
        $em->flush();
    }

    /**
     * Отправляем письмо с битыми ссылками
     *
     * @return int
     */
    private function sendMailErrorPages()
    {
        if (count($this->errorPages) && $this->needSend) {
            $this->output->writeln('Отправляем письмо с битыми ссылками');
            $title = "CheckPage. Не удалось получить содержимое следующих страниц";
            $body = "Не удалось получить содержимое следующих страниц:
";
            foreach ($this->errorPages as $page) {
                /** @var Page $page */
                $body.= "http://check-page.ru/checkpage/page/".$page->getId()."/edit - ".$page->getUrl()."

";
            }

            /** @var MailService $mailService */
            $mailService = $this->getContainer()->get('mail.service');
            $to = $mailService->getSupportEmail();

            return $mailService->send($to, $title, $body);
        }
        return 0;
    }

    /**
     * @return CheckPageService
     */
    private function getCheckPageService()
    {
        return $this->getContainer()->get('check.page.service');
    }
}
