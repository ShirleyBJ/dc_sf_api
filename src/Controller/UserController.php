<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;


class UserController extends AbstractController
{
    #[Route('/login', name: 'login', methods:['POST'])]
    public function index(
        Request $r, 
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $userPasswordHasher
    ): Response
    {
        // On tente de récupérer un utilisateur grace à son email
        $user = $em->getRepository(User::class)->findOneBy(['email' => $r->get('email')]);
        
        if($user == null){
            return new JsonResponse('Utilisateur introuvable', 404);
        }

        if($r->get('pwd') == null || !$userPasswordHasher->isPasswordValid($user, $r->get('pwd'))){
            return new JsonResponse('Identifiants invalides', 400);
        }

        return new JsonResponse('Email et mdp ok', 200);
    }
}
