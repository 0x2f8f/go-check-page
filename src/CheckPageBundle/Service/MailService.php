<?php
namespace CheckPageBundle\Service;

class MailService
{
    private $from;
    private $mailer;
    private $supportEmail;
    private $develEmail;

    public function __construct($mailer, $from, $supportEmail, $develEmail)
    {
        $this->mailer       = $mailer;
        $this->from         = $from;
        $this->supportEmail = $supportEmail;
        $this->develEmail   = $develEmail;
    }

    /**
     * Отправитель
     *
     * @return string
     */
    private function getFrom()
    {
        return $this->from;
    }

    /**
     * Сервис отправки сообщений
     *
     * @return \Swift_Mailer
     */
    private function getMailer()
    {
        return $this->mailer;
    }

    /**
     * E-mail техподдержки
     *
     * @return string
     */
    public function getSupportEmail()
    {
        return $this->supportEmail;
    }

    /**
     * E-mail ответственного разработчика
     *
     * @return string
     */
    public function getDevelEmail()
    {
        return $this->develEmail;
    }

    /**
     * Отправвка сообщения
     *
     * @param $to
     * @param $subject
     * @param $body
     * @return int
     */
    public function send($to, $subject, $body)
    {
        $message = (new \Swift_Message($subject))
            ->setFrom($this->getFrom())
            ->setTo($to)
            ->setBody($body, 'text/html')
        ;

        return $this->getMailer()->send($message);
    }
}