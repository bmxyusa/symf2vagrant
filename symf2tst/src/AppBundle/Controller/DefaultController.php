<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Message;
use Sensio\Bundle\BuzzBundle\SensioBuzzBundle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use AppBundle\Form\NewUserType;
use AppBundle\Form\NewMessageType;

/**
 * Class DefaultController
 * @package AppBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {

        $userId = $this->container->get('session')->get('userId', null);

        if (is_null($userId)) {
            return $this->redirect($this->generateUrl('new_user'));
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->findOneById($userId);

        $newMessage = new Message();
        $newMessage->setUser($user);

        $form = $this->createNewMessageForm($newMessage);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($newMessage);
            $em->flush();
        }
        $messages = $em->getRepository('AppBundle:Message')->findBy(array(), array('created' => 'ASC'));

        return $this->render(
          'AppBundle::messages.html.twig',
          array(
            'messages' => $messages,
            'user' => $user,
            'messageForm' => $form->createView(),
          )
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newUserAction(Request $request)
    {
        $form = $this->createNewUserForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $name = $form->get('name')->getData();
            $em = $this->getDoctrine()->getManager();
            /** @var User $user */
            $user = $em->getRepository('AppBundle:User')->findOneByName($name);
            if (!$user) {
                $user = new User();
                $user->setName($name);
                $em->persist($user);
                $em->flush();
            }
            $this->container->get('session')->set('userId', $user->getId());

            return $this->redirect($this->generateUrl('messages'));
        }

        return $this->render(
          'AppBundle:User:new-user.html.twig',
          array(
            'form' => $form->createView(),
          )
        );
    }
    public function parseAction() {
        /** @var SensioBuzzBundle $buzz */
        $buzz = $this->container->get('buzz');
        /** @var \DOMDocument $response */
        $response = $buzz->get('http://www.bank.gov.ua/control/uk/curmetal/currency/search?formType=searchPeriodForm&time_step=daily&currency=169&periodStartTime=01.01.2008&periodEndTime=01.01.2015&outer=table&execute=%D0%92%D0%B8%D0%BA%D0%BE%D0%BD%D0%B0%D1%82%D0%B8')->toDomDocument();
        $table = $response->getElementById('results0');
        $trs = $table->getElementsByTagName('tr');
        $months = array();
        foreach($trs as $tr) {
            $tds = $tr->getElementsByTagName('td');
            $date = $tds->item(0)->nodeValue;
            $month = substr($date, 3);
            if (empty($months[$month])) {
                $months[$month] = array('sum' => 0, 'daycount' => 0, 'number' => $month);
            }
            $value = (float) $tds->item(3)->nodeValue;
            $months[$month]['sum'] += $value;
            $months[$month]['daycount']++;
        }
        foreach ($months as &$month) {
            $month['average'] = (float) $month['sum'] / $month['daycount'];
        }

        return $this->render('AppBundle::parse.html.twig', array('months' => $months));
    }
    /**
     * @return \Symfony\Component\Form\Form
     */
    private function createNewUserForm()
    {
        $form = $this->createForm(
          new NewUserType(),
          null,
          array(
            'action' => $this->generateUrl('new_user'),
            'method' => 'POST',
          )
        );

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * @param Message $message
     * @return \Symfony\Component\Form\Form
     */
    private function createNewMessageForm(Message $message)
    {

        $form = $this->createForm(
          new NewMessageType(),
          $message,
          array(
            'data_class' => 'AppBundle\Entity\Message',
            'action' => $this->generateUrl('messages'),
            'method' => 'POST',
          )
        );

        $form->add('submit', 'submit', array('label' => 'Post'));

        return $form;
    }
}
