<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use AppBundle\Form\NewUserType;

class DefaultController extends Controller {
  public function indexAction() {

    $userId = $this->container->get('session')->get('userId', NULL);

    if (is_null($userId)) {
      return $this->redirect($this->generateUrl('new_user'));
    }
    return $this->render('AppBundle::messages.html.twig');
  }

  public function newUserAction(Request $request) {
    $form = $this->createNewUserForm();
    $form->handleRequest($request);

    if ($form->isValid()) {
      $name = $form->get('name')->getData();
      $em = $this->getDoctrine()->getManager();
      /** @var User $user */
      $user = $em->getRepository('AppBundle:User')->findByName($name);
      if ($user) {
        var_dump($user->getId());
      }
      else {
        $user = new User();
        $user->setName($name);
        $em->persist($user);
        $em->flush();
      }
      //return $this->redirect($this->generateUrl('user_show', array('id' => $entity->getId())));
    }
    return $this->render('AppBundle:User:new-user.html.twig', array(
      'form' => $form->createView(),
    ));
  }

  private function createNewUserForm() {
    $form = $this->createForm(new NewUserType(), null, array(
      'action' => $this->generateUrl('new_user'),
      'method' => 'POST',
    ));

    $form->add('submit', 'submit', array('label' => 'Create'));

    return $form;
  }
}
