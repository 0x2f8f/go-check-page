<?php

namespace CheckPageBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sonata\AdminBundle\Controller\CoreController;
use CheckPageBundle\CheckHypothesisType;
use CheckPageBundle\Service\CheckPageService;

/**
 * @Route("/hypothesis")
 */

class CheckHypothesisController extends CoreController
{
    /**
     * Страница "Проверка гипотезы".
     * На этой странице можно указать ссылку, селектор, нажать на кнопку "Проверить"
     * и увидеть что в результате найдёт Crawler по селектору
     *
     * @Route("", name="check-hypothesis")
     *
     * @param Request $request
     * @return Response
     */
    public function checkHypothesisAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('url', TextType::class, array('label' => 'Ссылка'))
            ->add('selector', TextType::class, array('label' => 'Селектор'))
            ->add('prerender', CheckboxType::class, array('label' => 'Через пререндер', 'required' => false))
            ->add('save', SubmitType::class, array('label' => 'Проверить'))
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();
                $checkPageService = $this->getCheckPageService();
                $htmlPart = $checkPageService->getHtmlPart($data['url'], $data['selector'], $data['prerender']);
                $result = $htmlPart ? $htmlPart : 'Ничего не найдено';
            } catch (\Exception $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        } else {
            $result = null;
        }

        return $this->render('CheckPageBundle:Admin:check_hypothesis.html.twig', array(
            'base_template' => $this->getBaseTemplate(),
            'admin_pool'    => $this->container->get('sonata.admin.pool'),
            'blocks'        => $this->container->getParameter('sonata.admin.configuration.dashboard_blocks'),
            'form'          => $form->createView(),
            'result'        => $result
        ));
    }

    /**
     * @return CheckPageService
     */
    private function getCheckPageService()
    {
        return $this->container->get('check.page.service');
    }
}